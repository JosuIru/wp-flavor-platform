<?php
/**
 * Frontend Controller para Multimedia
 *
 * @package FlavorChatIA
 * @subpackage Modules\Multimedia
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend para Multimedia
 */
class Flavor_Multimedia_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Multimedia_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Tabla principal
     * @var string
     */
    private $tabla_multimedia;

    /**
     * Tabla de álbumes
     * @var string
     */
    private $tabla_albumes;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
        $this->tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Multimedia_Frontend_Controller
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
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);

        // Shortcodes
        if (!shortcode_exists('flavor_multimedia_galeria')) {
            add_shortcode('flavor_multimedia_galeria', [$this, 'shortcode_galeria']);
        }
        if (!shortcode_exists('flavor_multimedia_mis_fotos')) {
            add_shortcode('flavor_multimedia_mis_fotos', [$this, 'shortcode_mis_fotos']);
        }
        if (!shortcode_exists('flavor_multimedia_subir')) {
            add_shortcode('flavor_multimedia_subir', [$this, 'shortcode_subir']);
        }
        if (!shortcode_exists('flavor_multimedia_albumes')) {
            add_shortcode('flavor_multimedia_albumes', [$this, 'shortcode_albumes']);
        }
        if (!shortcode_exists('flavor_multimedia_album')) {
            add_shortcode('flavor_multimedia_album', [$this, 'shortcode_album']);
        }
        if (!shortcode_exists('flavor_multimedia_visor')) {
            add_shortcode('flavor_multimedia_visor', [$this, 'shortcode_visor']);
        }
        if (!shortcode_exists('flavor_multimedia_dashboard')) {
            add_shortcode('flavor_multimedia_dashboard', [$this, 'shortcode_dashboard']);
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_multimedia_subir', [$this, 'ajax_subir']);
        add_action('wp_ajax_flavor_multimedia_eliminar', [$this, 'ajax_eliminar']);
        add_action('wp_ajax_flavor_multimedia_like', [$this, 'ajax_like']);
        add_action('wp_ajax_nopriv_flavor_multimedia_like', [$this, 'ajax_like']);
        add_action('wp_ajax_flavor_multimedia_crear_album', [$this, 'ajax_crear_album']);
        add_action('wp_ajax_flavor_multimedia_agregar_album', [$this, 'ajax_agregar_album']);
        add_action('wp_ajax_flavor_multimedia_dashboard_data', [$this, 'ajax_dashboard_data']);
    }

    /**
     * Registra los assets del módulo
     */
    public function register_assets() {
        wp_register_style(
            'flavor-multimedia-frontend',
            plugin_dir_url(__FILE__) . '../assets/css/multimedia-frontend.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_script(
            'flavor-multimedia-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/multimedia-frontend.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );
    }

    /**
     * Encola los assets
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-multimedia-frontend');
        wp_enqueue_script('flavor-multimedia-frontend');

        wp_localize_script('flavor-multimedia-frontend', 'flavorMultimediaConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_multimedia_nonce'),
            'maxUploadSize' => wp_max_upload_size(),
            'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'audio/mpeg'],
            'strings' => [
                'subiendo' => __('Subiendo...', 'flavor-chat-ia'),
                'subido' => __('Archivo subido correctamente', 'flavor-chat-ia'),
                'error' => __('Error al procesar', 'flavor-chat-ia'),
                'confirmar_eliminar' => __('¿Eliminar este archivo?', 'flavor-chat-ia'),
                'eliminado' => __('Archivo eliminado', 'flavor-chat-ia'),
                'sin_archivos' => __('No hay archivos', 'flavor-chat-ia'),
                'arrastra_aqui' => __('Arrastra archivos aquí o haz clic', 'flavor-chat-ia'),
                'archivo_grande' => __('El archivo es demasiado grande', 'flavor-chat-ia'),
                'tipo_no_permitido' => __('Tipo de archivo no permitido', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Registra los tabs del dashboard
     *
     * @param array $tabs
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['multimedia'] = [
            'label' => __('Multimedia', 'flavor-chat-ia'),
            'icon' => 'images',
            'callback' => [$this, 'render_tab_multimedia'],
            'orden' => 75,
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de multimedia en dashboard
     */
    public function render_tab_multimedia() {
        $this->enqueue_assets();
        $user_id = get_current_user_id();

        if (!$user_id) {
            echo '<p class="flavor-login-required">' . esc_html__('Debes iniciar sesión para ver tu contenido multimedia.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $mis_fotos = 0;
        $mis_videos = 0;
        $mis_likes = 0;
        $total_vistas = 0;

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_multimedia)) {
            $mis_fotos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_multimedia} WHERE usuario_id = %d AND tipo = 'imagen'",
                $user_id
            ));

            $mis_videos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_multimedia} WHERE usuario_id = %d AND tipo = 'video'",
                $user_id
            ));

            $mis_likes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(me_gusta), 0) FROM {$this->tabla_multimedia} WHERE usuario_id = %d",
                $user_id
            ));

            $total_vistas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(vistas), 0) FROM {$this->tabla_multimedia} WHERE usuario_id = %d",
                $user_id
            ));
        }

        $mis_albumes = 0;
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_albumes)) {
            $mis_albumes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_albumes} WHERE usuario_id = %d",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-multimedia-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-format-gallery"></span> <?php esc_html_e('Mi Multimedia', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Gestiona tus fotos, videos y álbumes', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-camera"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_fotos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Fotos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-video-alt3"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_videos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Videos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-images-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_albumes); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Álbumes', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-accent">
                    <span class="flavor-kpi-icon dashicons dashicons-heart"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_likes); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Me gusta recibidos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php $this->render_contenido_reciente($user_id); ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/subir/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Subir', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/mi-galeria/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <?php esc_html_e('Mis Fotos', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/mi-galeria/?tab=albumes')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-images-alt"></span>
                    <?php esc_html_e('Mis Álbumes', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/galeria/')); ?>" class="flavor-btn flavor-btn-outline">
                    <span class="dashicons dashicons-admin-site"></span>
                    <?php esc_html_e('Galería', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza contenido reciente del usuario
     */
    private function render_contenido_reciente($user_id) {
        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_multimedia)) {
            return;
        }

        $contenido_reciente = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_multimedia}
             WHERE usuario_id = %d AND estado IN ('publico', 'comunidad')
             ORDER BY fecha_creacion DESC LIMIT 8",
            $user_id
        ));

        if (empty($contenido_reciente)) {
            echo '<div class="flavor-empty-state">';
            echo '<span class="dashicons dashicons-format-image"></span>';
            echo '<p>' . esc_html__('Aún no has subido contenido multimedia.', 'flavor-chat-ia') . '</p>';
            echo '<a href="' . esc_url(home_url('/mi-portal/multimedia/subir/')) . '" class="flavor-btn flavor-btn-primary">';
            echo esc_html__('Subir mi primera foto', 'flavor-chat-ia') . '</a>';
            echo '</div>';
            return;
        }

        ?>
        <div class="flavor-panel-section">
            <h3><?php esc_html_e('Mi contenido reciente', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-multimedia-grid flavor-multimedia-grid-sm">
                <?php foreach ($contenido_reciente as $item): ?>
                    <div class="flavor-multimedia-item" data-id="<?php echo esc_attr($item->id); ?>">
                        <?php if ($item->tipo === 'imagen'): ?>
                            <img src="<?php echo esc_url($item->url_thumbnail ?: $item->url); ?>" alt="<?php echo esc_attr($item->titulo); ?>" loading="lazy">
                        <?php elseif ($item->tipo === 'video'): ?>
                            <div class="flavor-multimedia-video-thumb">
                                <span class="dashicons dashicons-video-alt3"></span>
                            </div>
                        <?php else: ?>
                            <div class="flavor-multimedia-audio-thumb">
                                <span class="dashicons dashicons-format-audio"></span>
                            </div>
                        <?php endif; ?>
                        <div class="flavor-multimedia-overlay">
                            <span class="flavor-multimedia-likes"><span class="dashicons dashicons-heart"></span> <?php echo intval($item->likes); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Galería pública
     */
    public function shortcode_galeria($atts) {
        $this->enqueue_assets();

        $atts = shortcode_atts([
            'categoria' => '',
            'tipo' => '',
            'limite' => 24,
            'columnas' => 4,
            'entidad' => '',
            'entidad_id' => '',
        ], $atts);

        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_multimedia)) {
            return '<p class="flavor-notice">' . esc_html__('Galería no disponible.', 'flavor-chat-ia') . '</p>';
        }

        $where_clauses = ["estado IN ('publico', 'comunidad')"];
        $params = [];

        if (!empty($atts['categoria'])) {
            $where_clauses[] = "categoria = %s";
            $params[] = sanitize_text_field($atts['categoria']);
        }

        if (!empty($atts['tipo'])) {
            $where_clauses[] = "tipo = %s";
            $params[] = sanitize_text_field($atts['tipo']);
        }

        if (!empty($atts['entidad']) && !empty($atts['entidad_id'])) {
            $where_clauses[] = "entidad_tipo = %s AND entidad_id = %d";
            $params[] = sanitize_key($atts['entidad']);
            $params[] = intval($atts['entidad_id']);
        }

        $where_sql = implode(' AND ', $where_clauses);
        $limite = min(100, max(1, intval($atts['limite'])));
        $columnas = min(6, max(2, intval($atts['columnas'])));

        $query = "SELECT * FROM {$this->tabla_multimedia} WHERE {$where_sql} ORDER BY fecha_creacion DESC LIMIT %d";
        $params[] = $limite;

        $items = $wpdb->get_results($wpdb->prepare($query, ...$params));

        ob_start();
        ?>
        <div class="flavor-multimedia-galeria" data-columnas="<?php echo esc_attr($columnas); ?>">
            <?php if (empty($items)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <p><?php esc_html_e('No hay contenido multimedia disponible.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-multimedia-grid flavor-multimedia-grid-<?php echo esc_attr($columnas); ?>">
                    <?php foreach ($items as $item): ?>
                        <div class="flavor-multimedia-item" data-id="<?php echo esc_attr($item->id); ?>" data-tipo="<?php echo esc_attr($item->tipo); ?>">
                            <?php if ($item->tipo === 'imagen'): ?>
                                <img src="<?php echo esc_url($item->url_thumbnail ?: $item->url); ?>" alt="<?php echo esc_attr($item->titulo); ?>" loading="lazy">
                            <?php elseif ($item->tipo === 'video'): ?>
                                <div class="flavor-multimedia-video-thumb">
                                    <?php if ($item->url_thumbnail): ?>
                                        <img src="<?php echo esc_url($item->url_thumbnail); ?>" alt="">
                                    <?php endif; ?>
                                    <span class="flavor-play-icon dashicons dashicons-controls-play"></span>
                                </div>
                            <?php else: ?>
                                <div class="flavor-multimedia-audio-thumb">
                                    <span class="dashicons dashicons-format-audio"></span>
                                    <span class="flavor-audio-title"><?php echo esc_html($item->titulo); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-multimedia-overlay">
                                <?php if (!empty($item->titulo)): ?>
                                    <span class="flavor-multimedia-title"><?php echo esc_html($item->titulo); ?></span>
                                <?php endif; ?>
                                <div class="flavor-multimedia-meta">
                                    <span><span class="dashicons dashicons-heart"></span> <?php echo intval($item->likes); ?></span>
                                    <span><span class="dashicons dashicons-visibility"></span> <?php echo intval($item->vistas); ?></span>
                                </div>
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
     * Shortcode: Mis fotos
     */
    public function shortcode_mis_fotos($atts) {
        $this->enqueue_assets();

        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . esc_html__('Debes iniciar sesión para ver tu contenido.', 'flavor-chat-ia') . '</p>';
        }

        $user_id = get_current_user_id();
        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_multimedia)) {
            return '<p class="flavor-notice">' . esc_html__('Multimedia no disponible.', 'flavor-chat-ia') . '</p>';
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_multimedia}
             WHERE usuario_id = %d
             ORDER BY fecha_creacion DESC",
            $user_id
        ));

        ob_start();
        ?>
        <div class="flavor-mis-multimedia">
            <div class="flavor-panel-header">
                <h2><?php esc_html_e('Mi Multimedia', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/subir/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Subir', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($items)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-format-image"></span>
                    <p><?php esc_html_e('No has subido contenido aún.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-multimedia-grid">
                    <?php foreach ($items as $item): ?>
                        <div class="flavor-multimedia-item flavor-multimedia-item-editable" data-id="<?php echo esc_attr($item->id); ?>">
                            <?php if ($item->tipo === 'imagen'): ?>
                                <img src="<?php echo esc_url($item->url_thumbnail ?: $item->url); ?>" alt="<?php echo esc_attr($item->titulo); ?>">
                            <?php elseif ($item->tipo === 'video'): ?>
                                <div class="flavor-multimedia-video-thumb">
                                    <span class="dashicons dashicons-video-alt3"></span>
                                </div>
                            <?php else: ?>
                                <div class="flavor-multimedia-audio-thumb">
                                    <span class="dashicons dashicons-format-audio"></span>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-multimedia-estado">
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($item->estado); ?>">
                                    <?php echo esc_html(ucfirst($item->estado)); ?>
                                </span>
                            </div>
                            <div class="flavor-multimedia-actions">
                                <button type="button" class="flavor-btn-icon flavor-btn-editar" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="flavor-btn-icon flavor-btn-eliminar" title="<?php esc_attr_e('Eliminar', 'flavor-chat-ia'); ?>">
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
     * Shortcode: Formulario de subida
     */
    public function shortcode_subir($atts) {
        $this->enqueue_assets();

        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . esc_html__('Debes iniciar sesión para subir contenido.', 'flavor-chat-ia') . '</p>';
        }

        $atts = shortcode_atts([
            'tipo' => '',
            'entidad' => '',
            'entidad_id' => '',
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-multimedia-subir">
            <form id="form-subir-multimedia" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_multimedia_nonce', 'multimedia_nonce'); ?>

                <?php if (!empty($atts['entidad']) && !empty($atts['entidad_id'])): ?>
                    <input type="hidden" name="entidad_tipo" value="<?php echo esc_attr($atts['entidad']); ?>">
                    <input type="hidden" name="entidad_id" value="<?php echo esc_attr($atts['entidad_id']); ?>">
                <?php endif; ?>

                <div class="flavor-dropzone" id="dropzone-multimedia">
                    <div class="flavor-dropzone-content">
                        <span class="dashicons dashicons-cloud-upload"></span>
                        <p><?php esc_html_e('Arrastra archivos aquí o haz clic para seleccionar', 'flavor-chat-ia'); ?></p>
                        <span class="flavor-dropzone-info"><?php printf(esc_html__('Máximo %s', 'flavor-chat-ia'), size_format(wp_max_upload_size())); ?></span>
                    </div>
                    <input type="file" name="archivos[]" id="input-archivos" multiple accept="image/*,video/*,audio/*" style="display:none;">
                </div>

                <div id="preview-archivos" class="flavor-preview-grid"></div>

                <div class="flavor-form-row">
                    <label for="titulo"><?php esc_html_e('Título', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="titulo" id="titulo" class="flavor-input">
                </div>

                <div class="flavor-form-row">
                    <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                    <textarea name="descripcion" id="descripcion" class="flavor-textarea" rows="3"></textarea>
                </div>

                <div class="flavor-form-row">
                    <label for="categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label>
                    <select name="categoria" id="categoria" class="flavor-select">
                        <option value=""><?php esc_html_e('Sin categoría', 'flavor-chat-ia'); ?></option>
                        <option value="eventos"><?php esc_html_e('Eventos', 'flavor-chat-ia'); ?></option>
                        <option value="comunidad"><?php esc_html_e('Comunidad', 'flavor-chat-ia'); ?></option>
                        <option value="naturaleza"><?php esc_html_e('Naturaleza', 'flavor-chat-ia'); ?></option>
                        <option value="cultura"><?php esc_html_e('Cultura', 'flavor-chat-ia'); ?></option>
                        <option value="otros"><?php esc_html_e('Otros', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Subir', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Álbumes
     */
    public function shortcode_albumes($atts) {
        $this->enqueue_assets();

        $atts = shortcode_atts([
            'autor' => '',
            'limite' => 12,
        ], $atts);

        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_albumes)) {
            return '<p class="flavor-notice">' . esc_html__('Álbumes no disponibles.', 'flavor-chat-ia') . '</p>';
        }

        $where = "WHERE 1=1";
        $params = [];

        if (!empty($atts['autor'])) {
            if ($atts['autor'] === 'current' && is_user_logged_in()) {
                $where .= " AND usuario_id = %d";
                $params[] = get_current_user_id();
            }
        }

        $limite = min(50, max(1, intval($atts['limite'])));
        $where .= " ORDER BY fecha_creacion DESC LIMIT %d";
        $params[] = $limite;

        $albumes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_albumes} {$where}",
            ...$params
        ));

        ob_start();
        ?>
        <div class="flavor-multimedia-albumes">
            <?php if (empty($albumes)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-images-alt"></span>
                    <p><?php esc_html_e('No hay álbumes disponibles.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-albumes-grid">
                    <?php foreach ($albumes as $album): ?>
                        <a href="<?php echo esc_url(add_query_arg('album_id', $album->id, home_url('/mi-portal/multimedia/mi-galeria/'))); ?>" class="flavor-album-card">
                            <div class="flavor-album-cover">
                                <?php if ($album->portada_url): ?>
                                    <img src="<?php echo esc_url($album->portada_url); ?>" alt="">
                                <?php else: ?>
                                    <span class="dashicons dashicons-images-alt"></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-album-info">
                                <h3><?php echo esc_html($album->titulo); ?></h3>
                                <span class="flavor-album-count"><?php echo intval($album->total_archivos ?? 0); ?> <?php esc_html_e('archivos', 'flavor-chat-ia'); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Álbum individual
     */
    public function shortcode_album($atts) {
        $this->enqueue_assets();

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $album_id = intval($atts['id']);
        if (!$album_id && isset($_GET['album_id'])) {
            $album_id = intval($_GET['album_id']);
        }

        if (!$album_id) {
            return '<p class="flavor-notice">' . esc_html__('Álbum no especificado.', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;

        $album = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_albumes} WHERE id = %d",
            $album_id
        ));

        if (!$album) {
            return '<p class="flavor-notice">' . esc_html__('Álbum no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_multimedia}
             WHERE album_id = %d AND estado IN ('publico', 'comunidad')
             ORDER BY fecha_creacion DESC",
            $album_id
        ));

        ob_start();
        ?>
        <div class="flavor-album-detalle">
            <div class="flavor-album-header">
                <h1><?php echo esc_html($album->titulo); ?></h1>
                <?php if (!empty($album->descripcion)): ?>
                    <p><?php echo esc_html($album->descripcion); ?></p>
                <?php endif; ?>
            </div>

            <?php if (empty($items)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-format-image"></span>
                    <p><?php esc_html_e('Este álbum está vacío.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-multimedia-grid">
                    <?php foreach ($items as $item): ?>
                        <div class="flavor-multimedia-item" data-id="<?php echo esc_attr($item->id); ?>">
                            <?php if ($item->tipo === 'imagen'): ?>
                                <img src="<?php echo esc_url($item->url_thumbnail ?: $item->url); ?>" alt="<?php echo esc_attr($item->titulo); ?>">
                            <?php elseif ($item->tipo === 'video'): ?>
                                <div class="flavor-multimedia-video-thumb">
                                    <span class="dashicons dashicons-video-alt3"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Visor lightbox
     */
    public function shortcode_visor($atts) {
        $this->enqueue_assets();

        ob_start();
        ?>
        <div id="flavor-multimedia-visor" class="flavor-lightbox" style="display:none;">
            <div class="flavor-lightbox-overlay"></div>
            <div class="flavor-lightbox-content">
                <button class="flavor-lightbox-close">&times;</button>
                <button class="flavor-lightbox-prev"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
                <button class="flavor-lightbox-next"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
                <div class="flavor-lightbox-media"></div>
                <div class="flavor-lightbox-info">
                    <h3 class="flavor-lightbox-title"></h3>
                    <p class="flavor-lightbox-description"></p>
                    <div class="flavor-lightbox-actions">
                        <button class="flavor-btn-like"><span class="dashicons dashicons-heart"></span> <span class="like-count">0</span></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard completo
     */
    public function shortcode_dashboard($atts) {
        $this->enqueue_assets();
        ob_start();
        $this->render_tab_multimedia();
        return ob_get_clean();
    }

    /**
     * AJAX: Subir archivo
     */
    public function ajax_subir() {
        check_ajax_referer('flavor_multimedia_nonce', 'multimedia_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        if (empty($_FILES['archivos'])) {
            wp_send_json_error(['message' => __('No se recibió ningún archivo', 'flavor-chat-ia')]);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $user_id = get_current_user_id();
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_key($_POST['categoria'] ?? '');
        $entidad_tipo = sanitize_key($_POST['entidad_tipo'] ?? '');
        $entidad_id = intval($_POST['entidad_id'] ?? 0);

        global $wpdb;
        $archivos_subidos = [];

        $files = $_FILES['archivos'];
        $file_count = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $file_count; $i++) {
            $file = [
                'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                'size' => is_array($files['size']) ? $files['size'][$i] : $files['size'],
            ];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Determinar tipo
            $tipo = 'imagen';
            if (strpos($file['type'], 'video/') === 0) {
                $tipo = 'video';
            } elseif (strpos($file['type'], 'audio/') === 0) {
                $tipo = 'audio';
            }

            // Subir a WordPress
            $_FILES['upload'] = $file;
            $attachment_id = media_handle_upload('upload', 0);

            if (is_wp_error($attachment_id)) {
                continue;
            }

            $url = wp_get_attachment_url($attachment_id);
            $url_thumbnail = '';

            if ($tipo === 'imagen') {
                $thumbnail = wp_get_attachment_image_src($attachment_id, 'medium');
                $url_thumbnail = $thumbnail ? $thumbnail[0] : $url;
            }

            // Insertar en tabla de multimedia
            $wpdb->insert($this->tabla_multimedia, [
                'usuario_id' => $user_id,
                'attachment_id' => $attachment_id,
                'titulo' => $titulo ?: $file['name'],
                'descripcion' => $descripcion,
                'tipo' => $tipo,
                'url' => $url,
                'url_thumbnail' => $url_thumbnail,
                'categoria' => $categoria,
                'entidad_tipo' => $entidad_tipo,
                'entidad_id' => $entidad_id,
                'estado' => 'aprobado',
                'fecha_creacion' => current_time('mysql'),
            ]);

            $archivos_subidos[] = [
                'id' => $wpdb->insert_id,
                'url' => $url,
                'tipo' => $tipo,
            ];
        }

        if (empty($archivos_subidos)) {
            wp_send_json_error(['message' => __('No se pudo subir ningún archivo', 'flavor-chat-ia')]);
        }

        wp_send_json_success([
            'message' => sprintf(__('%d archivo(s) subido(s) correctamente', 'flavor-chat-ia'), count($archivos_subidos)),
            'archivos' => $archivos_subidos,
        ]);
    }

    /**
     * AJAX: Eliminar archivo
     */
    public function ajax_eliminar() {
        check_ajax_referer('flavor_multimedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $archivo_id = intval($_POST['archivo_id'] ?? 0);
        if (!$archivo_id) {
            wp_send_json_error(['message' => __('ID no válido', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $user_id = get_current_user_id();

        $archivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_multimedia} WHERE id = %d AND usuario_id = %d",
            $archivo_id, $user_id
        ));

        if (!$archivo) {
            wp_send_json_error(['message' => __('Archivo no encontrado o no tienes permiso', 'flavor-chat-ia')]);
        }

        // Eliminar attachment de WordPress
        if ($archivo->attachment_id) {
            wp_delete_attachment($archivo->attachment_id, true);
        }

        // Eliminar de tabla
        $wpdb->delete($this->tabla_multimedia, ['id' => $archivo_id]);

        wp_send_json_success(['message' => __('Archivo eliminado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Like/Unlike
     */
    public function ajax_like() {
        check_ajax_referer('flavor_multimedia_nonce', 'nonce');

        $archivo_id = intval($_POST['archivo_id'] ?? 0);
        if (!$archivo_id) {
            wp_send_json_error(['message' => __('ID no válido', 'flavor-chat-ia')]);
        }

        global $wpdb;

        // Incrementar likes
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_multimedia} SET likes = likes + 1 WHERE id = %d",
            $archivo_id
        ));

        $likes = $wpdb->get_var($wpdb->prepare(
            "SELECT likes FROM {$this->tabla_multimedia} WHERE id = %d",
            $archivo_id
        ));

        wp_send_json_success(['likes' => intval($likes)]);
    }

    /**
     * AJAX: Crear álbum
     */
    public function ajax_crear_album() {
        check_ajax_referer('flavor_multimedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        if (empty($titulo)) {
            wp_send_json_error(['message' => __('El título es obligatorio', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $user_id = get_current_user_id();

        $wpdb->insert($this->tabla_albumes, [
            'usuario_id' => $user_id,
            'titulo' => $titulo,
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'fecha_creacion' => current_time('mysql'),
        ]);

        wp_send_json_success([
            'message' => __('Álbum creado', 'flavor-chat-ia'),
            'album_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Agregar archivo a álbum
     */
    public function ajax_agregar_album() {
        check_ajax_referer('flavor_multimedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $archivo_id = intval($_POST['archivo_id'] ?? 0);
        $album_id = intval($_POST['album_id'] ?? 0);

        if (!$archivo_id || !$album_id) {
            wp_send_json_error(['message' => __('Parámetros inválidos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $user_id = get_current_user_id();

        // Verificar propiedad
        $archivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_multimedia} WHERE id = %d AND usuario_id = %d",
            $archivo_id, $user_id
        ));

        if (!$archivo) {
            wp_send_json_error(['message' => __('No tienes permiso', 'flavor-chat-ia')]);
        }

        $wpdb->update(
            $this->tabla_multimedia,
            ['album_id' => $album_id],
            ['id' => $archivo_id]
        );

        // Actualizar contador del álbum
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_albumes} SET total_archivos = (
                SELECT COUNT(*) FROM {$this->tabla_multimedia} WHERE album_id = %d
             ) WHERE id = %d",
            $album_id, $album_id
        ));

        wp_send_json_success(['message' => __('Archivo agregado al álbum', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Dashboard data
     */
    public function ajax_dashboard_data() {
        check_ajax_referer('flavor_multimedia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $user_id = get_current_user_id();

        $stats = [
            'fotos' => 0,
            'videos' => 0,
            'albumes' => 0,
            'likes' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_multimedia)) {
            $stats['fotos'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_multimedia} WHERE usuario_id = %d AND tipo = 'imagen'",
                $user_id
            ));
            $stats['videos'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_multimedia} WHERE usuario_id = %d AND tipo = 'video'",
                $user_id
            ));
            $stats['likes'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(me_gusta), 0) FROM {$this->tabla_multimedia} WHERE usuario_id = %d",
                $user_id
            ));
        }

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_albumes)) {
            $stats['albumes'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_albumes} WHERE usuario_id = %d",
                $user_id
            ));
        }

        wp_send_json_success($stats);
    }
}

// Inicializar
Flavor_Multimedia_Frontend_Controller::get_instance();
