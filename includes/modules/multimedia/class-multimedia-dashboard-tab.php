<?php
/**
 * Dashboard Tab para Multimedia
 *
 * Gestiona los tabs del dashboard de usuario para el modulo multimedia:
 * - Mis Fotos: Galeria de fotos subidas por el usuario
 * - Mis Albumes: Albumes creados por el usuario
 * - Favoritos: Contenido marcado como favorito (likes)
 * - Estadisticas: Metricas de vistas y likes del contenido
 *
 * @package FlavorChatIA
 * @subpackage Modules\Multimedia
 * @since 4.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario para Multimedia
 */
class Flavor_Multimedia_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Multimedia_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Prefijo de tablas
     * @var string
     */
    private $tabla_multimedia;

    /**
     * Tabla de albumes
     * @var string
     */
    private $tabla_albumes;

    /**
     * Tabla de likes
     * @var string
     */
    private $tabla_likes;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
        $this->tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';
        $this->tabla_likes = $wpdb->prefix . 'flavor_multimedia_likes';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Multimedia_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Registra los tabs del modulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['multimedia-mis-fotos'] = [
            'label'    => __('Mis Fotos', 'flavor-chat-ia'),
            'icon'     => 'camera',
            'callback' => [$this, 'render_tab_mis_fotos'],
            'orden'    => 60,
        ];

        $tabs['multimedia-mis-albumes'] = [
            'label'    => __('Mis Albumes', 'flavor-chat-ia'),
            'icon'     => 'images',
            'callback' => [$this, 'render_tab_mis_albumes'],
            'orden'    => 61,
        ];

        $tabs['multimedia-favoritos'] = [
            'label'    => __('Favoritos', 'flavor-chat-ia'),
            'icon'     => 'heart',
            'callback' => [$this, 'render_tab_favoritos'],
            'orden'    => 62,
        ];

        $tabs['multimedia-estadisticas'] = [
            'label'    => __('Estadisticas', 'flavor-chat-ia'),
            'icon'     => 'chart-bar',
            'callback' => [$this, 'render_tab_estadisticas'],
            'orden'    => 63,
        ];

        return $tabs;
    }

    /**
     * Verifica si las tablas necesarias existen
     *
     * @return bool
     */
    private function tablas_existen() {
        return Flavor_Chat_Helpers::tabla_existe($this->tabla_multimedia);
    }

    /**
     * Renderiza el tab de Mis Fotos con galeria de imagenes
     */
    public function render_tab_mis_fotos() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $this->render_login_requerido();
            return;
        }

        if (!$this->tablas_existen()) {
            $this->render_modulo_no_disponible();
            return;
        }

        global $wpdb;

        // Obtener fotos del usuario
        $fotos = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, a.nombre as album_nombre
             FROM {$this->tabla_multimedia} m
             LEFT JOIN {$this->tabla_albumes} a ON m.album_id = a.id
             WHERE m.usuario_id = %d AND m.tipo = 'imagen'
             ORDER BY m.fecha_creacion DESC
             LIMIT 50",
            $usuario_id
        ));

        // Contadores
        $total_fotos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_multimedia} WHERE usuario_id = %d AND tipo = 'imagen'",
            $usuario_id
        ));

        $total_videos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_multimedia} WHERE usuario_id = %d AND tipo = 'video'",
            $usuario_id
        ));

        $total_audio = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_multimedia} WHERE usuario_id = %d AND tipo = 'audio'",
            $usuario_id
        ));

        ?>
        <div class="flavor-panel flavor-multimedia-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-format-gallery"></span>
                    <?php esc_html_e('Mis Fotos', 'flavor-chat-ia'); ?>
                </h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/subir/')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Subir Foto', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <div class="flavor-panel-kpis flavor-kpis-mini">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-format-image"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_fotos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Fotos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-video-alt3"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_videos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Videos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-format-audio"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_audio); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Audios', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($fotos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <p><?php esc_html_e('Aun no has subido ninguna foto.', 'flavor-chat-ia'); ?></p>
                    <p class="flavor-text-muted"><?php esc_html_e('Comparte tus mejores momentos con la comunidad.', 'flavor-chat-ia'); ?></p>
                    <div class="flavor-empty-actions">
                        <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/subir/')); ?>" class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Subir mi primera foto', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="flavor-galeria-grid">
                    <?php foreach ($fotos as $foto): ?>
                        <div class="flavor-galeria-item" data-id="<?php echo esc_attr($foto->id); ?>">
                            <div class="flavor-galeria-thumbnail">
                                <?php
                                $thumbnail_url = !empty($foto->thumbnail_url) ? $foto->thumbnail_url : $foto->archivo_url;
                                ?>
                                <img src="<?php echo esc_url($thumbnail_url); ?>"
                                     alt="<?php echo esc_attr($foto->titulo ?: __('Imagen', 'flavor-chat-ia')); ?>"
                                     loading="lazy" />

                                <div class="flavor-galeria-overlay">
                                    <div class="flavor-galeria-stats">
                                        <span title="<?php esc_attr_e('Vistas', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                            <?php echo number_format_i18n($foto->vistas); ?>
                                        </span>
                                        <span title="<?php esc_attr_e('Me gusta', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-heart"></span>
                                            <?php echo number_format_i18n($foto->me_gusta); ?>
                                        </span>
                                    </div>
                                    <div class="flavor-galeria-actions">
                                        <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/ver/' . $foto->id)); ?>"
                                           class="flavor-btn-icon" title="<?php esc_attr_e('Ver', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>
                                        <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/editar/' . $foto->id)); ?>"
                                           class="flavor-btn-icon" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="flavor-galeria-info">
                                <?php if (!empty($foto->titulo)): ?>
                                    <h4><?php echo esc_html(wp_trim_words($foto->titulo, 5)); ?></h4>
                                <?php endif; ?>
                                <span class="flavor-galeria-meta">
                                    <?php if (!empty($foto->album_nombre)): ?>
                                        <span class="dashicons dashicons-portfolio"></span>
                                        <?php echo esc_html($foto->album_nombre); ?>
                                    <?php else: ?>
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($foto->fecha_creacion))); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($foto->estado); ?>">
                                    <?php echo esc_html($this->get_estado_label($foto->estado)); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_fotos > 50): ?>
                    <div class="flavor-panel-footer">
                        <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/galeria/')); ?>" class="flavor-btn flavor-btn-outline">
                            <?php printf(esc_html__('Ver todas (%d)', 'flavor-chat-ia'), $total_fotos); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Mis Albumes
     */
    public function render_tab_mis_albumes() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $this->render_login_requerido();
            return;
        }

        if (!$this->tablas_existen()) {
            $this->render_modulo_no_disponible();
            return;
        }

        global $wpdb;

        // Obtener albumes del usuario con conteo de archivos
        $albumes = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM {$this->tabla_multimedia} m WHERE m.album_id = a.id) as total_archivos,
                    (SELECT m2.thumbnail_url FROM {$this->tabla_multimedia} m2 WHERE m2.album_id = a.id AND m2.tipo = 'imagen' ORDER BY m2.fecha_creacion DESC LIMIT 1) as portada_url
             FROM {$this->tabla_albumes} a
             WHERE a.usuario_id = %d
             ORDER BY a.fecha_creacion DESC",
            $usuario_id
        ));

        $total_albumes = count($albumes);

        ?>
        <div class="flavor-panel flavor-albumes-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-portfolio"></span>
                    <?php esc_html_e('Mis Albumes', 'flavor-chat-ia'); ?>
                </h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/crear-album/')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Crear Album', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($albumes)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-portfolio"></span>
                    <p><?php esc_html_e('Aun no has creado ningun album.', 'flavor-chat-ia'); ?></p>
                    <p class="flavor-text-muted"><?php esc_html_e('Organiza tus fotos en albumes tematicos.', 'flavor-chat-ia'); ?></p>
                    <div class="flavor-empty-actions">
                        <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/crear-album/')); ?>" class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e('Crear mi primer album', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid flavor-cards-grid-3">
                    <?php foreach ($albumes as $album): ?>
                        <div class="flavor-card flavor-album-card">
                            <div class="flavor-album-portada">
                                <?php if (!empty($album->portada_url)): ?>
                                    <img src="<?php echo esc_url($album->portada_url); ?>"
                                         alt="<?php echo esc_attr($album->nombre); ?>"
                                         loading="lazy" />
                                <?php else: ?>
                                    <div class="flavor-album-placeholder">
                                        <span class="dashicons dashicons-portfolio"></span>
                                    </div>
                                <?php endif; ?>
                                <span class="flavor-album-count">
                                    <?php echo number_format_i18n($album->total_archivos); ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                </span>
                            </div>
                            <div class="flavor-card-body">
                                <h4><?php echo esc_html($album->nombre); ?></h4>
                                <?php if (!empty($album->descripcion)): ?>
                                    <p class="flavor-text-muted"><?php echo esc_html(wp_trim_words($album->descripcion, 10)); ?></p>
                                <?php endif; ?>
                                <div class="flavor-album-meta">
                                    <span class="flavor-badge flavor-badge-<?php echo esc_attr($album->privacidad); ?>">
                                        <?php echo esc_html($this->get_privacidad_label($album->privacidad)); ?>
                                    </span>
                                    <span class="flavor-text-muted">
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($album->fecha_creacion))); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/album/' . $album->id)); ?>"
                                   class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php esc_html_e('Ver Album', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/editar-album/' . $album->id)); ?>"
                                   class="flavor-btn-icon" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Favoritos (contenido con likes del usuario)
     */
    public function render_tab_favoritos() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $this->render_login_requerido();
            return;
        }

        if (!$this->tablas_existen() || !Flavor_Chat_Helpers::tabla_existe($this->tabla_likes)) {
            $this->render_modulo_no_disponible();
            return;
        }

        global $wpdb;

        // Obtener archivos con like del usuario
        $favoritos = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, l.fecha as fecha_like, u.display_name as autor_nombre
             FROM {$this->tabla_likes} l
             INNER JOIN {$this->tabla_multimedia} m ON l.archivo_id = m.id
             LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE l.usuario_id = %d AND m.estado IN ('publico', 'comunidad')
             ORDER BY l.fecha DESC
             LIMIT 50",
            $usuario_id
        ));

        $total_favoritos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$this->tabla_likes} l
             INNER JOIN {$this->tabla_multimedia} m ON l.archivo_id = m.id
             WHERE l.usuario_id = %d AND m.estado IN ('publico', 'comunidad')",
            $usuario_id
        ));

        ?>
        <div class="flavor-panel flavor-favoritos-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Mis Favoritos', 'flavor-chat-ia'); ?>
                </h2>
                <span class="flavor-badge"><?php echo number_format_i18n($total_favoritos); ?></span>
            </div>

            <?php if (empty($favoritos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-heart"></span>
                    <p><?php esc_html_e('Aun no has marcado contenido como favorito.', 'flavor-chat-ia'); ?></p>
                    <p class="flavor-text-muted"><?php esc_html_e('Explora la galeria y dale "me gusta" al contenido que te interese.', 'flavor-chat-ia'); ?></p>
                    <div class="flavor-empty-actions">
                        <a href="<?php echo esc_url(home_url('/multimedia/')); ?>" class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-search"></span>
                            <?php esc_html_e('Explorar Galeria', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="flavor-galeria-grid">
                    <?php foreach ($favoritos as $archivo): ?>
                        <div class="flavor-galeria-item flavor-favorito-item" data-id="<?php echo esc_attr($archivo->id); ?>">
                            <div class="flavor-galeria-thumbnail">
                                <?php if ($archivo->tipo === 'imagen'): ?>
                                    <?php $thumbnail_url = !empty($archivo->thumbnail_url) ? $archivo->thumbnail_url : $archivo->archivo_url; ?>
                                    <img src="<?php echo esc_url($thumbnail_url); ?>"
                                         alt="<?php echo esc_attr($archivo->titulo ?: __('Imagen', 'flavor-chat-ia')); ?>"
                                         loading="lazy" />
                                <?php elseif ($archivo->tipo === 'video'): ?>
                                    <div class="flavor-media-placeholder flavor-video-placeholder">
                                        <span class="dashicons dashicons-video-alt3"></span>
                                    </div>
                                <?php else: ?>
                                    <div class="flavor-media-placeholder flavor-audio-placeholder">
                                        <span class="dashicons dashicons-format-audio"></span>
                                    </div>
                                <?php endif; ?>

                                <span class="flavor-tipo-badge">
                                    <?php echo esc_html($this->get_tipo_icon($archivo->tipo)); ?>
                                </span>

                                <div class="flavor-galeria-overlay">
                                    <div class="flavor-galeria-stats">
                                        <span><span class="dashicons dashicons-visibility"></span> <?php echo number_format_i18n($archivo->vistas); ?></span>
                                        <span><span class="dashicons dashicons-heart"></span> <?php echo number_format_i18n($archivo->me_gusta); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flavor-galeria-info">
                                <?php if (!empty($archivo->titulo)): ?>
                                    <h4><?php echo esc_html(wp_trim_words($archivo->titulo, 5)); ?></h4>
                                <?php endif; ?>
                                <span class="flavor-galeria-autor">
                                    <?php printf(esc_html__('por %s', 'flavor-chat-ia'), esc_html($archivo->autor_nombre)); ?>
                                </span>
                                <span class="flavor-galeria-meta">
                                    <span class="dashicons dashicons-heart"></span>
                                    <?php echo esc_html(human_time_diff(strtotime($archivo->fecha_like), current_time('timestamp'))); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_favoritos > 50): ?>
                    <div class="flavor-panel-footer">
                        <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/favoritos/')); ?>" class="flavor-btn flavor-btn-outline">
                            <?php printf(esc_html__('Ver todos (%d)', 'flavor-chat-ia'), $total_favoritos); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Estadisticas del contenido del usuario
     */
    public function render_tab_estadisticas() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $this->render_login_requerido();
            return;
        }

        if (!$this->tablas_existen()) {
            $this->render_modulo_no_disponible();
            return;
        }

        global $wpdb;

        // Estadisticas generales
        $estadisticas_generales = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_archivos,
                SUM(CASE WHEN tipo = 'imagen' THEN 1 ELSE 0 END) as total_imagenes,
                SUM(CASE WHEN tipo = 'video' THEN 1 ELSE 0 END) as total_videos,
                SUM(CASE WHEN tipo = 'audio' THEN 1 ELSE 0 END) as total_audios,
                SUM(vistas) as total_vistas,
                SUM(me_gusta) as total_likes,
                SUM(comentarios_count) as total_comentarios,
                SUM(descargas) as total_descargas
             FROM {$this->tabla_multimedia}
             WHERE usuario_id = %d",
            $usuario_id
        ));

        // Top 5 archivos mas vistos
        $archivos_populares = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, tipo, archivo_url, thumbnail_url, vistas, me_gusta
             FROM {$this->tabla_multimedia}
             WHERE usuario_id = %d
             ORDER BY vistas DESC
             LIMIT 5",
            $usuario_id
        ));

        // Top 5 archivos con mas likes
        $archivos_gustados = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, tipo, archivo_url, thumbnail_url, vistas, me_gusta
             FROM {$this->tabla_multimedia}
             WHERE usuario_id = %d
             ORDER BY me_gusta DESC
             LIMIT 5",
            $usuario_id
        ));

        // Actividad reciente (ultimos 30 dias)
        $actividad_mensual = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as cantidad
             FROM {$this->tabla_multimedia}
             WHERE usuario_id = %d AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(fecha_creacion)
             ORDER BY fecha ASC",
            $usuario_id
        ));

        // Likes recibidos este mes
        $likes_mes = 0;
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_likes)) {
            $likes_mes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$this->tabla_likes} l
                 INNER JOIN {$this->tabla_multimedia} m ON l.archivo_id = m.id
                 WHERE m.usuario_id = %d AND l.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                $usuario_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-estadisticas-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Estadisticas de mi Contenido', 'flavor-chat-ia'); ?>
                </h2>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-format-gallery"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_generales->total_archivos ?: 0); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Archivos Totales', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-visibility"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_generales->total_vistas ?: 0); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Vistas Totales', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-heart"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_generales->total_likes ?: 0); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Me Gusta Totales', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-info">
                    <span class="flavor-kpi-icon dashicons dashicons-admin-comments"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_generales->total_comentarios ?: 0); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Comentarios', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-kpis flavor-kpis-secondary">
                <div class="flavor-kpi-card flavor-kpi-mini">
                    <span class="flavor-kpi-icon dashicons dashicons-format-image"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_generales->total_imagenes ?: 0); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Imagenes', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-mini">
                    <span class="flavor-kpi-icon dashicons dashicons-video-alt3"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_generales->total_videos ?: 0); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Videos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-mini">
                    <span class="flavor-kpi-icon dashicons dashicons-format-audio"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_generales->total_audios ?: 0); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Audios', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-mini">
                    <span class="flavor-kpi-icon dashicons dashicons-download"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_generales->total_descargas ?: 0); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Descargas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($likes_mes > 0): ?>
                <div class="flavor-stat-highlight">
                    <span class="dashicons dashicons-heart"></span>
                    <span>
                        <?php printf(
                            esc_html__('Has recibido %s likes en los ultimos 30 dias', 'flavor-chat-ia'),
                            '<strong>' . number_format_i18n($likes_mes) . '</strong>'
                        ); ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-grid flavor-panel-grid-2">
                <!-- Top archivos mas vistos -->
                <div class="flavor-panel-section">
                    <h3>
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Mas Vistos', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (!empty($archivos_populares)): ?>
                        <div class="flavor-stats-list">
                            <?php foreach ($archivos_populares as $indice => $archivo): ?>
                                <div class="flavor-stats-item">
                                    <span class="flavor-stats-rank"><?php echo ($indice + 1); ?></span>
                                    <div class="flavor-stats-thumb">
                                        <?php if ($archivo->tipo === 'imagen'): ?>
                                            <img src="<?php echo esc_url($archivo->thumbnail_url ?: $archivo->archivo_url); ?>"
                                                 alt="" loading="lazy" />
                                        <?php else: ?>
                                            <span class="dashicons dashicons-<?php echo $archivo->tipo === 'video' ? 'video-alt3' : 'format-audio'; ?>"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flavor-stats-info">
                                        <span class="flavor-stats-title"><?php echo esc_html($archivo->titulo ?: __('Sin titulo', 'flavor-chat-ia')); ?></span>
                                        <span class="flavor-stats-meta">
                                            <span class="dashicons dashicons-visibility"></span>
                                            <?php echo number_format_i18n($archivo->vistas); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="flavor-text-muted"><?php esc_html_e('Sin datos aun', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Top archivos con mas likes -->
                <div class="flavor-panel-section">
                    <h3>
                        <span class="dashicons dashicons-heart"></span>
                        <?php esc_html_e('Mas Gustados', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (!empty($archivos_gustados)): ?>
                        <div class="flavor-stats-list">
                            <?php foreach ($archivos_gustados as $indice => $archivo): ?>
                                <div class="flavor-stats-item">
                                    <span class="flavor-stats-rank"><?php echo ($indice + 1); ?></span>
                                    <div class="flavor-stats-thumb">
                                        <?php if ($archivo->tipo === 'imagen'): ?>
                                            <img src="<?php echo esc_url($archivo->thumbnail_url ?: $archivo->archivo_url); ?>"
                                                 alt="" loading="lazy" />
                                        <?php else: ?>
                                            <span class="dashicons dashicons-<?php echo $archivo->tipo === 'video' ? 'video-alt3' : 'format-audio'; ?>"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flavor-stats-info">
                                        <span class="flavor-stats-title"><?php echo esc_html($archivo->titulo ?: __('Sin titulo', 'flavor-chat-ia')); ?></span>
                                        <span class="flavor-stats-meta">
                                            <span class="dashicons dashicons-heart"></span>
                                            <?php echo number_format_i18n($archivo->me_gusta); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="flavor-text-muted"><?php esc_html_e('Sin datos aun', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($actividad_mensual)): ?>
                <div class="flavor-panel-section">
                    <h3>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Actividad Ultimos 30 Dias', 'flavor-chat-ia'); ?>
                    </h3>
                    <div class="flavor-activity-chart">
                        <?php
                        // Crear array de todos los dias del mes
                        $dias_mes = [];
                        for ($i = 29; $i >= 0; $i--) {
                            $fecha = date('Y-m-d', strtotime("-{$i} days"));
                            $dias_mes[$fecha] = 0;
                        }
                        // Rellenar con datos reales
                        foreach ($actividad_mensual as $actividad) {
                            if (isset($dias_mes[$actividad->fecha])) {
                                $dias_mes[$actividad->fecha] = (int) $actividad->cantidad;
                            }
                        }
                        $max_valor = max($dias_mes) ?: 1;
                        ?>
                        <div class="flavor-chart-bars">
                            <?php foreach ($dias_mes as $fecha => $cantidad): ?>
                                <?php $altura_porcentaje = ($cantidad / $max_valor) * 100; ?>
                                <div class="flavor-chart-bar"
                                     style="--bar-height: <?php echo $altura_porcentaje; ?>%"
                                     title="<?php echo esc_attr(date_i18n(get_option('date_format'), strtotime($fecha)) . ': ' . $cantidad); ?>">
                                    <span class="flavor-chart-bar-inner"></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flavor-chart-labels">
                            <span><?php echo esc_html(date_i18n('d M', strtotime('-29 days'))); ?></span>
                            <span><?php esc_html_e('Hoy', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtiene el label del estado
     *
     * @param string $estado Estado del archivo
     * @return string
     */
    private function get_estado_label($estado) {
        $estados = [
            'pendiente' => __('Pendiente', 'flavor-chat-ia'),
            'publico'   => __('Publico', 'flavor-chat-ia'),
            'privado'   => __('Privado', 'flavor-chat-ia'),
            'comunidad' => __('Comunidad', 'flavor-chat-ia'),
            'rechazado' => __('Rechazado', 'flavor-chat-ia'),
        ];
        return $estados[$estado] ?? $estado;
    }

    /**
     * Obtiene el label de privacidad
     *
     * @param string $privacidad Nivel de privacidad
     * @return string
     */
    private function get_privacidad_label($privacidad) {
        $privacidades = [
            'publico'   => __('Publico', 'flavor-chat-ia'),
            'privado'   => __('Privado', 'flavor-chat-ia'),
            'comunidad' => __('Comunidad', 'flavor-chat-ia'),
        ];
        return $privacidades[$privacidad] ?? $privacidad;
    }

    /**
     * Obtiene el icono del tipo de archivo
     *
     * @param string $tipo Tipo de archivo
     * @return string
     */
    private function get_tipo_icon($tipo) {
        $iconos = [
            'imagen' => 'camera',
            'video'  => 'video-alt3',
            'audio'  => 'format-audio',
        ];
        return $iconos[$tipo] ?? 'media-default';
    }

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_login_requerido() {
        ?>
        <div class="flavor-panel flavor-panel-warning">
            <p><?php esc_html_e('Debes iniciar sesion para ver este contenido.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-btn flavor-btn-primary">
                <?php esc_html_e('Iniciar Sesion', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Renderiza mensaje de modulo no disponible
     */
    private function render_modulo_no_disponible() {
        ?>
        <div class="flavor-panel flavor-panel-info">
            <p><?php esc_html_e('El modulo de Multimedia no esta configurado correctamente.', 'flavor-chat-ia'); ?></p>
            <p class="flavor-text-muted"><?php esc_html_e('Contacta al administrador del sitio.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php
    }

    /**
     * Enqueue de assets para el dashboard
     */
    public function enqueue_assets() {
        if (!is_page() || !is_user_logged_in()) {
            return;
        }

        // CSS inline para la galeria del dashboard
        $css_galeria = "
            .flavor-multimedia-panel .flavor-galeria-grid,
            .flavor-favoritos-panel .flavor-galeria-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 16px;
                margin-top: 20px;
            }
            .flavor-galeria-item {
                position: relative;
                border-radius: 8px;
                overflow: hidden;
                background: #f5f5f5;
            }
            .flavor-galeria-thumbnail {
                position: relative;
                aspect-ratio: 1;
            }
            .flavor-galeria-thumbnail img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .flavor-galeria-overlay {
                position: absolute;
                inset: 0;
                background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%);
                opacity: 0;
                transition: opacity 0.3s;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                padding: 10px;
            }
            .flavor-galeria-item:hover .flavor-galeria-overlay {
                opacity: 1;
            }
            .flavor-galeria-stats {
                display: flex;
                gap: 12px;
                color: #fff;
                font-size: 12px;
            }
            .flavor-galeria-stats span {
                display: flex;
                align-items: center;
                gap: 4px;
            }
            .flavor-galeria-actions {
                display: flex;
                gap: 8px;
                margin-top: 8px;
            }
            .flavor-galeria-info {
                padding: 10px;
            }
            .flavor-galeria-info h4 {
                margin: 0 0 5px;
                font-size: 13px;
            }
            .flavor-galeria-meta {
                font-size: 11px;
                color: #666;
            }
            .flavor-album-portada {
                position: relative;
                aspect-ratio: 16/9;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .flavor-album-portada img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .flavor-album-placeholder {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: rgba(255,255,255,0.5);
            }
            .flavor-album-placeholder .dashicons {
                font-size: 48px;
                width: 48px;
                height: 48px;
            }
            .flavor-album-count {
                position: absolute;
                bottom: 10px;
                right: 10px;
                background: rgba(0,0,0,0.7);
                color: #fff;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                display: flex;
                align-items: center;
                gap: 4px;
            }
            .flavor-stats-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .flavor-stats-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 8px;
                background: #f9f9f9;
                border-radius: 6px;
            }
            .flavor-stats-rank {
                width: 24px;
                height: 24px;
                background: #667eea;
                color: #fff;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: 600;
            }
            .flavor-stats-thumb {
                width: 40px;
                height: 40px;
                border-radius: 4px;
                overflow: hidden;
                background: #eee;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .flavor-stats-thumb img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .flavor-stats-info {
                flex: 1;
            }
            .flavor-stats-title {
                display: block;
                font-size: 13px;
                font-weight: 500;
            }
            .flavor-stats-meta {
                font-size: 11px;
                color: #666;
                display: flex;
                align-items: center;
                gap: 4px;
            }
            .flavor-activity-chart {
                margin-top: 15px;
            }
            .flavor-chart-bars {
                display: flex;
                align-items: flex-end;
                height: 80px;
                gap: 2px;
            }
            .flavor-chart-bar {
                flex: 1;
                height: 100%;
                display: flex;
                align-items: flex-end;
            }
            .flavor-chart-bar-inner {
                width: 100%;
                height: var(--bar-height, 0%);
                min-height: 2px;
                background: linear-gradient(to top, #667eea, #764ba2);
                border-radius: 2px 2px 0 0;
                transition: height 0.3s;
            }
            .flavor-chart-bar:hover .flavor-chart-bar-inner {
                opacity: 0.8;
            }
            .flavor-chart-labels {
                display: flex;
                justify-content: space-between;
                margin-top: 8px;
                font-size: 11px;
                color: #666;
            }
            .flavor-stat-highlight {
                background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
                color: #fff;
                padding: 12px 16px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 20px 0;
            }
            .flavor-stat-highlight .dashicons {
                font-size: 24px;
            }
            .flavor-kpis-secondary {
                margin-top: 10px;
            }
            .flavor-kpi-mini {
                padding: 12px !important;
            }
            .flavor-kpi-mini .flavor-kpi-value {
                font-size: 18px !important;
            }
            .flavor-tipo-badge {
                position: absolute;
                top: 8px;
                left: 8px;
                background: rgba(0,0,0,0.6);
                color: #fff;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 11px;
            }
            .flavor-media-placeholder {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: rgba(255,255,255,0.8);
            }
            .flavor-media-placeholder .dashicons {
                font-size: 32px;
                width: 32px;
                height: 32px;
            }
        ";

        wp_add_inline_style('flavor-frontend', $css_galeria);
    }
}
