<?php
/**
 * Módulo de Multimedia para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Multimedia - Galería y contenidos audiovisuales comunitarios
 */
class Flavor_Chat_Multimedia_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Provider;

    /**
     * {@inheritdoc}
     */
    public function get_integration_content_type() {
        return [
            'id' => 'multimedia',
            'label' => __('Multimedia', 'flavor-chat-ia'),
            'icon' => 'dashicons-format-gallery',
            'table' => 'flavor_multimedia',
        ];
    }

    /**
     * Obtener items de multimedia disponibles para vincular
     * Sobrescribe el método del trait para usar tabla personalizada
     *
     * @return array
     */
    protected function get_available_items_for_integration() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return [];
        }

        $items = $wpdb->get_results(
            "SELECT id, titulo, tipo, archivo_url
             FROM {$tabla}
             WHERE estado IN ('publico', 'comunidad')
             ORDER BY fecha_creacion DESC
             LIMIT 100"
        );

        return $items ?: [];
    }

    /**
     * Renderizar caja de integración para multimedia
     * Sobrescribe el método del trait para usar tabla personalizada
     *
     * @param int    $object_id   ID del objeto
     * @param string $object_type Tipo de objeto
     * @param array  $context     Contexto adicional
     */
    public function render_integration_box($object_id, $object_type, $context = []) {
        $content_type = $this->get_integration_content_type();
        $meta_key = '_flavor_rel_' . $content_type['id'];

        // Obtener IDs relacionados
        $related_ids = get_metadata($object_type === 'user' ? 'user' : 'post', $object_id, $meta_key, true);
        if (!is_array($related_ids)) {
            $related_ids = [];
        }

        // Obtener items disponibles
        $available_items = $this->get_available_items_for_integration();

        ?>
        <div class="flavor-integration-box" data-type="<?php echo esc_attr($content_type['id']); ?>">
            <h4>
                <span class="dashicons <?php echo esc_attr($content_type['icon']); ?>"></span>
                <?php echo esc_html($content_type['label']); ?>
            </h4>

            <div class="flavor-integration-items" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <?php if (!empty($related_ids)):
                    global $wpdb;
                    $tabla = $wpdb->prefix . 'flavor_multimedia';
                    foreach ($related_ids as $item_id):
                        $item = $wpdb->get_row($wpdb->prepare(
                            "SELECT id, titulo, tipo, archivo_url FROM {$tabla} WHERE id = %d",
                            $item_id
                        ));
                        if (!$item) continue;
                ?>
                <div class="flavor-integration-item" style="display: flex; justify-content: space-between; align-items: center; padding: 5px; border-bottom: 1px solid #eee;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <?php if ($item->tipo === 'imagen' && $item->archivo_url): ?>
                            <img src="<?php echo esc_url($item->archivo_url); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;" />
                        <?php else: ?>
                            <span class="dashicons dashicons-<?php echo $item->tipo === 'video' ? 'video-alt3' : 'format-audio'; ?>" style="font-size: 24px;"></span>
                        <?php endif; ?>
                        <span><?php echo esc_html($item->titulo); ?></span>
                    </div>
                    <button type="button" class="button-link flavor-remove-integration" data-id="<?php echo esc_attr($item_id); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <input type="hidden" name="<?php echo esc_attr($meta_key); ?>[]" value="<?php echo esc_attr($item_id); ?>" />
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                    <p class="flavor-no-items" style="color: #666; font-style: italic; margin: 0;">
                        <?php _e('Sin multimedia vinculado', 'flavor-chat-ia'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 5px;">
                <select class="flavor-integration-selector widefat" style="flex: 1;">
                    <option value=""><?php _e('Seleccionar multimedia...', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($available_items as $item):
                        if (in_array($item->id, $related_ids)) continue;
                        $icono = $item->tipo === 'imagen' ? '📷' : ($item->tipo === 'video' ? '🎬' : '🎵');
                    ?>
                    <option value="<?php echo esc_attr($item->id); ?>"><?php echo esc_html($icono . ' ' . $item->titulo); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button flavor-add-integration">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                </button>
            </div>

            <p style="margin-top: 10px;">
                <a href="<?php echo admin_url('admin.php?page=multimedia'); ?>" class="button button-small" target="_blank">
                    <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                    <?php _e('Subir Multimedia', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'multimedia';
        $this->name = 'Multimedia'; // Translation loaded on init
        $this->description = 'Galería de fotos, videos y contenidos audiovisuales de la comunidad.'; // Translation loaded on init

        parent::__construct();

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
        // Auto-registered AJAX handlers
        add_action('wp_ajax_multimedia_subir_archivo', [$this, 'ajax_subir_archivo']);
        add_action('wp_ajax_multimedia_eliminar_archivo', [$this, 'ajax_eliminar_archivo']);
        add_action('wp_ajax_multimedia_editar_archivo', [$this, 'ajax_editar_archivo']);
        add_action('wp_ajax_multimedia_reportar_archivo', [$this, 'ajax_reportar_archivo']);
        add_action('wp_ajax_multimedia_galeria_publica', [$this, 'ajax_galeria_publica']);
        add_action('wp_ajax_nopriv_multimedia_galeria_publica', [$this, 'ajax_galeria_publica']);
        add_action('wp_ajax_multimedia_detalle_archivo', [$this, 'ajax_detalle_archivo']);
        add_action('wp_ajax_nopriv_multimedia_detalle_archivo', [$this, 'ajax_detalle_archivo']);

    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
        return Flavor_Chat_Helpers::tabla_existe($tabla_multimedia);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Multimedia no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
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
            'disponible_app' => 'ambas',
            'permite_subir' => true,
            'requiere_moderacion' => false,
            'formatos_imagen' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'formatos_video' => ['mp4', 'mov', 'avi', 'webm'],
            'formatos_audio' => ['mp3', 'wav', 'ogg', 'm4a'],
            'max_tamano_imagen_mb' => 10,
            'max_tamano_video_mb' => 100,
            'max_tamano_audio_mb' => 50,
            'genera_thumbnails' => true,
            'permite_albumes' => true,
            'permite_geolocalizacion' => true,
            'permite_comentarios' => true,
            'permite_descargas' => true,
            'max_archivos_album' => 500,
            'puntos_subir_foto' => 5,
            'puntos_subir_video' => 10,
            'puntos_recibir_like' => 2,
            'marca_agua' => false,
            'calidad_jpeg' => 85,
            'thumbnail_width' => 400,
            'thumbnail_height' => 300,
        ];
    }

    /**
     * Define los tabs que este módulo inyecta en otros módulos
     *
     * Cuando multimedia está activo, puede mostrar un tab de "Galería"
     * en los dashboards de grupos de consumo, eventos, comunidades, etc.
     *
     * @return array Configuración de tabs por módulo destino
     */
    public function get_tab_integrations() {
        return [
            // Tab de galería para Grupos de Consumo
            'grupos_consumo' => [
                'id'       => 'galeria-grupo',
                'label'    => __('Galería', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="grupo_consumo" entidad_id="{entity_id}"]',
                'priority' => 110,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('grupo_consumo', $contexto['entity_id']);
                },
            ],

            // Tab de galería para Eventos
            'eventos' => [
                'id'       => 'galeria-evento',
                'label'    => __('Fotos', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="evento" entidad_id="{entity_id}"]',
                'priority' => 110,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('evento', $contexto['entity_id']);
                },
            ],

            // Tab de galería para Comunidades
            'comunidades' => [
                'id'       => 'galeria-comunidad',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="comunidad" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('comunidad', $contexto['entity_id']);
                },
            ],

            'incidencias' => [
                'id'       => 'galeria-incidencia',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="incidencia" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('incidencia', $contexto['entity_id']);
                },
            ],

            'documentacion_legal' => [
                'id'       => 'galeria-documento-legal',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="documento_legal" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('documento_legal', $contexto['entity_id']);
                },
            ],

            'presupuestos_participativos' => [
                'id'       => 'galeria-pp-proyecto',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="pp_proyecto" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('pp_proyecto', $contexto['entity_id']);
                },
            ],

            'saberes_ancestrales' => [
                'id'       => 'galeria-saber-ancestral',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="saber_ancestral" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('saber_ancestral', $contexto['entity_id']);
                },
            ],

            'transparencia' => [
                'id'       => 'galeria-documento-transparencia',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="documento_transparencia" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('documento_transparencia', $contexto['entity_id']);
                },
            ],

            'avisos_municipales' => [
                'id'       => 'galeria-aviso-municipal',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="aviso_municipal" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('aviso_municipal', $contexto['entity_id']);
                },
            ],

            'economia_don' => [
                'id'       => 'galeria-economia-don',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="economia_don" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('economia_don', $contexto['entity_id']);
                },
            ],

            'advertising' => [
                'id'       => 'galeria-advertising-ad',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="advertising_ad" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('advertising_ad', $contexto['entity_id']);
                },
            ],

            'radio' => [
                'id'       => 'galeria-radio-programa',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="radio_programa" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('radio_programa', $contexto['entity_id']);
                },
            ],

            'energia_comunitaria' => [
                'id'       => 'galeria-energia-comunidad',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="energia_comunidad" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('energia_comunidad', $contexto['entity_id']);
                },
            ],

            // Tab de galería para Cursos
            'cursos' => [
                'id'       => 'recursos-curso',
                'label'    => __('Recursos', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="curso" entidad_id="{entity_id}"]',
                'priority' => 110,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('curso', $contexto['entity_id']);
                },
            ],

            // Tab de recursos para Talleres
            'talleres' => [
                'id'       => 'recursos-taller',
                'label'    => __('Recursos', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="taller" entidad_id="{entity_id}"]',
                'priority' => 110,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('taller', $contexto['entity_id']);
                },
            ],

            // Tab de multimedia para Trabajo Digno
            'trabajo_digno' => [
                'id'       => 'recursos-oferta-trabajo',
                'label'    => __('Recursos', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="trabajo_digno_oferta" entidad_id="{entity_id}"]',
                'priority' => 110,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('trabajo_digno_oferta', $contexto['entity_id']);
                },
            ],

            // Tab de galería para Colectivos
            'colectivos' => [
                'id'       => 'galeria-colectivo',
                'label'    => __('Galería', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="colectivo" entidad_id="{entity_id}"]',
                'priority' => 110,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('colectivo', $contexto['entity_id']);
                },
            ],

            // Tab para Huertos Urbanos
            'huertos_urbanos' => [
                'id'       => 'fotos-huerto',
                'label'    => __('Fotos', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="huerto" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('huerto', $contexto['entity_id']);
                },
            ],
            'participacion' => [
                'id'       => 'multimedia-propuesta',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="participacion_propuesta" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('participacion_propuesta', $contexto['entity_id']);
                },
            ],
            'economia_suficiencia' => [
                'id'       => 'multimedia-recurso-suficiencia',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="es_recurso" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('es_recurso', $contexto['entity_id']);
                },
            ],
            'justicia_restaurativa' => [
                'id'       => 'multimedia-proceso-restaurativo',
                'label'    => __('Multimedia', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="jr_proceso" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('jr_proceso', $contexto['entity_id']);
                },
            ],

            // Tab para Banco de Tiempo
            'banco_tiempo' => [
                'id'       => 'galeria-servicio',
                'label'    => __('Galería', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-gallery',
                'content'  => '[flavor_multimedia_galeria entidad="servicio_bt" entidad_id="{entity_id}"]',
                'priority' => 110,
                'badge'    => function($contexto) {
                    return $this->contar_multimedia_entidad('servicio_bt', $contexto['entity_id']);
                },
            ],
        ];
    }

    /**
     * Cuenta archivos multimedia asociados a una entidad
     *
     * @param string $tipo_entidad Tipo de entidad
     * @param int    $entidad_id   ID de la entidad
     * @return int Número de archivos
     */
    public function contar_multimedia_entidad($tipo_entidad, $entidad_id) {
        global $wpdb;

        if (!$entidad_id) {
            return 0;
        }

        $tabla = $wpdb->prefix . 'flavor_multimedia';

        // Verificar si la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return 0;
        }

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla}
             WHERE entidad_tipo = %s AND entidad_id = %d AND estado IN ('publico', 'comunidad')",
            $tipo_entidad,
            $entidad_id
        ));

        return intval($total);
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Registrar como proveedor de integración
        $this->register_as_integration_provider();

        add_action('init', [$this, 'maybe_create_pages']);
        // Registrar en panel de administración unificado
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        add_action('init', [$this, 'maybe_create_tables']);

        // Register REST API routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_mm_subir', [$this, 'ajax_subir_archivo']);
        add_action('wp_ajax_flavor_mm_eliminar', [$this, 'ajax_eliminar_archivo']);
        add_action('wp_ajax_flavor_mm_editar', [$this, 'ajax_editar_archivo']);
        add_action('wp_ajax_flavor_mm_me_gusta', [$this, 'ajax_me_gusta']);
        add_action('wp_ajax_flavor_mm_comentar', [$this, 'ajax_comentar']);
        add_action('wp_ajax_flavor_mm_crear_album', [$this, 'ajax_crear_album']);
        add_action('wp_ajax_flavor_mm_editar_album', [$this, 'ajax_editar_album']);
        add_action('wp_ajax_flavor_mm_eliminar_album', [$this, 'ajax_eliminar_album']);
        add_action('wp_ajax_flavor_mm_mover_archivo', [$this, 'ajax_mover_archivo']);
        add_action('wp_ajax_flavor_mm_reportar', [$this, 'ajax_reportar_archivo']);

        // Public AJAX
        add_action('wp_ajax_nopriv_flavor_mm_galeria', [$this, 'ajax_galeria_publica']);
        add_action('wp_ajax_flavor_mm_galeria', [$this, 'ajax_galeria_publica']);
        add_action('wp_ajax_flavor_mm_detalle', [$this, 'ajax_detalle_archivo']);
        add_action('wp_ajax_nopriv_flavor_mm_detalle', [$this, 'ajax_detalle_archivo']);

        // Admin AJAX
        add_action('wp_ajax_flavor_mm_admin_moderar', [$this, 'ajax_admin_moderar']);
        add_action('wp_ajax_flavor_mm_admin_destacar', [$this, 'ajax_admin_destacar']);
        add_action('wp_ajax_flavor_mm_admin_stats', [$this, 'ajax_admin_stats']);

        // Shortcodes
        $this->register_shortcodes();

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Dashboard tabs para usuarios (frontend)
        $this->init_dashboard_tabs();

        // Cron para limpieza
        add_action('flavor_multimedia_cleanup', [$this, 'cron_cleanup_archivos']);
        if (!wp_next_scheduled('flavor_multimedia_cleanup')) {
            wp_schedule_event(time(), 'daily', 'flavor_multimedia_cleanup');
        }
    }

    /**
     * Inicializa los tabs del dashboard de usuario
     */
    private function init_dashboard_tabs() {
        $tab_file = dirname(__FILE__) . '/class-multimedia-dashboard-tab.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
            if (class_exists('Flavor_Multimedia_Dashboard_Tab')) {
                Flavor_Multimedia_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        $shortcodes = [
            'flavor_galeria' => 'shortcode_galeria',
            'flavor_albumes' => 'shortcode_albumes',
            'flavor_subir_multimedia' => 'shortcode_subir',
            'flavor_mi_galeria' => 'shortcode_mi_galeria',
            'flavor_carousel' => 'shortcode_carousel',
            // Shortcode de integración para tabs de otros módulos
            'flavor_multimedia_galeria' => 'shortcode_galeria_integrada',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    public function maybe_create_tables() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $this->create_tables();
        }
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
        $tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';
        $tabla_likes = $wpdb->prefix . 'flavor_multimedia_likes';
        $tabla_comentarios = $wpdb->prefix . 'flavor_multimedia_comentarios';
        $tabla_tags = $wpdb->prefix . 'flavor_multimedia_tags';
        $tabla_reportes = $wpdb->prefix . 'flavor_multimedia_reportes';

        $sql_multimedia = "CREATE TABLE IF NOT EXISTS $tabla_multimedia (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            album_id bigint(20) unsigned DEFAULT NULL,
            titulo varchar(255) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('imagen','video','audio') DEFAULT 'imagen',
            archivo_url varchar(500) NOT NULL,
            archivo_path varchar(500) DEFAULT NULL,
            thumbnail_url varchar(500) DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            tamano_bytes bigint(20) DEFAULT NULL,
            ancho int(11) DEFAULT NULL,
            alto int(11) DEFAULT NULL,
            duracion_segundos int(11) DEFAULT NULL,
            ubicacion_lat decimal(10,7) DEFAULT NULL,
            ubicacion_lng decimal(10,7) DEFAULT NULL,
            ubicacion_nombre varchar(255) DEFAULT NULL,
            vistas int(11) DEFAULT 0,
            me_gusta int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            descargas int(11) DEFAULT 0,
            estado enum('pendiente','publico','privado','comunidad','rechazado') DEFAULT 'comunidad',
            destacado tinyint(1) DEFAULT 0,
            permite_descargas tinyint(1) DEFAULT 1,
            permite_comentarios tinyint(1) DEFAULT 1,
            metadata JSON DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY album_id (album_id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY destacado (destacado),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        $sql_albumes = "CREATE TABLE IF NOT EXISTS $tabla_albumes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            nombre varchar(255) NOT NULL,
            slug varchar(255) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            portada_id bigint(20) unsigned DEFAULT NULL,
            privacidad enum('publico','privado','comunidad') DEFAULT 'comunidad',
            archivos_count int(11) DEFAULT 0,
            orden int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY slug (slug),
            KEY privacidad (privacidad)
        ) $charset_collate;";

        $sql_likes = "CREATE TABLE IF NOT EXISTS $tabla_likes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            archivo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY archivo_usuario (archivo_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        $sql_comentarios = "CREATE TABLE IF NOT EXISTS $tabla_comentarios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            archivo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            comentario text NOT NULL,
            parent_id bigint(20) unsigned DEFAULT NULL,
            estado enum('visible','oculto','eliminado') DEFAULT 'visible',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY archivo_id (archivo_id),
            KEY usuario_id (usuario_id),
            KEY parent_id (parent_id)
        ) $charset_collate;";

        $sql_tags = "CREATE TABLE IF NOT EXISTS $tabla_tags (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            archivo_id bigint(20) unsigned NOT NULL,
            tag varchar(100) NOT NULL,
            PRIMARY KEY (id),
            KEY archivo_id (archivo_id),
            KEY tag (tag)
        ) $charset_collate;";

        $sql_reportes = "CREATE TABLE IF NOT EXISTS $tabla_reportes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            archivo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            motivo varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            estado enum('pendiente','revisado','resuelto') DEFAULT 'pendiente',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY archivo_id (archivo_id),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_multimedia);
        dbDelta($sql_albumes);
        dbDelta($sql_likes);
        dbDelta($sql_comentarios);
        dbDelta($sql_tags);
        dbDelta($sql_reportes);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Galería pública
        register_rest_route($namespace, '/multimedia/galeria', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_galeria'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Detalle de archivo
        register_rest_route($namespace, '/multimedia/archivo/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_archivo'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Subir archivo
        register_rest_route($namespace, '/multimedia/subir', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_subir_archivo'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Editar archivo
        register_rest_route($namespace, '/multimedia/archivo/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'rest_editar_archivo'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Eliminar archivo
        register_rest_route($namespace, '/multimedia/archivo/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'rest_eliminar_archivo'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Me gusta
        register_rest_route($namespace, '/multimedia/archivo/(?P<id>\d+)/like', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_toggle_like'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Comentarios
        register_rest_route($namespace, '/multimedia/archivo/(?P<id>\d+)/comentarios', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_comentarios'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/multimedia/archivo/(?P<id>\d+)/comentar', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_comentar'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Álbumes
        register_rest_route($namespace, '/multimedia/albumes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_albumes'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/multimedia/album/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_album'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/multimedia/album', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_album'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Mis archivos
        register_rest_route($namespace, '/multimedia/mis-archivos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_archivos'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Mis álbumes
        register_rest_route($namespace, '/multimedia/mis-albumes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_albumes'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Descargar
        register_rest_route($namespace, '/multimedia/archivo/(?P<id>\d+)/descargar', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_descargar'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Tags populares
        register_rest_route($namespace, '/multimedia/tags', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_tags'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Reportar
        register_rest_route($namespace, '/multimedia/archivo/(?P<id>\d+)/reportar', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_reportar'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);
    }

    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * REST: Obtener galería
     */
    public function rest_get_galeria($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        $tipo = sanitize_text_field($request->get_param('tipo') ?: '');
        $album_id = absint($request->get_param('album_id') ?: 0);
        $tag = sanitize_text_field($request->get_param('tag') ?: '');
        $busqueda = sanitize_text_field($request->get_param('busqueda') ?: '');
        $limite = absint($request->get_param('limite') ?: 20);
        $pagina = absint($request->get_param('pagina') ?: 1);
        $orden = sanitize_text_field($request->get_param('orden') ?: 'recientes');
        $destacados = $request->get_param('destacados') === 'true';

        $where = ["m.estado IN ('publico', 'comunidad')"];
        $params = [];

        if ($tipo && in_array($tipo, ['imagen', 'video', 'audio'])) {
            $where[] = 'm.tipo = %s';
            $params[] = $tipo;
        }

        if ($album_id > 0) {
            $where[] = 'm.album_id = %d';
            $params[] = $album_id;
        }

        if ($tag) {
            $tabla_tags = $wpdb->prefix . 'flavor_multimedia_tags';
            $where[] = "m.id IN (SELECT archivo_id FROM $tabla_tags WHERE tag = %s)";
            $params[] = $tag;
        }

        if ($busqueda) {
            $where[] = "(m.titulo LIKE %s OR m.descripcion LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        }

        if ($destacados) {
            $where[] = 'm.destacado = 1';
        }

        $order_by = match ($orden) {
            'populares' => 'm.me_gusta DESC, m.vistas DESC',
            'vistas' => 'm.vistas DESC',
            'comentados' => 'm.comentarios_count DESC',
            default => 'm.fecha_creacion DESC',
        };

        $offset = ($pagina - 1) * $limite;

        // Total
        $sql_count = "SELECT COUNT(*) FROM $tabla m WHERE " . implode(' AND ', $where);
        $total = $wpdb->get_var($params ? $wpdb->prepare($sql_count, ...$params) : $sql_count);

        // Archivos
        $sql = "SELECT m.*, u.display_name as autor_nombre
                FROM $tabla m
                LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY $order_by
                LIMIT %d OFFSET %d";

        $params[] = $limite;
        $params[] = $offset;

        $archivos = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $data = [];
        foreach ($archivos as $archivo) {
            $data[] = $this->format_archivo($archivo);
        }

        $respuesta = [
            'success' => true,
            'archivos' => $data,
            'total' => (int) $total,
            'paginas' => ceil($total / $limite),
            'pagina_actual' => $pagina,
        ];

        return new WP_REST_Response($this->sanitize_public_multimedia_response($respuesta), 200);
    }

    /**
     * REST: Obtener archivo
     */
    public function rest_get_archivo($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        $archivo_id = absint($request->get_param('id'));

        $archivo = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, u.display_name as autor_nombre
             FROM $tabla m
             LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.id = %d",
            $archivo_id
        ));

        if (!$archivo) {
            return new WP_REST_Response(['error' => __('Archivo no encontrado', 'flavor-chat-ia')], 404);
        }

        // Verificar privacidad
        if ($archivo->estado === 'privado' && get_current_user_id() !== (int) $archivo->usuario_id) {
            return new WP_REST_Response(['error' => __('No tienes permiso para ver este archivo', 'flavor-chat-ia')], 403);
        }

        // Incrementar vistas
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla SET vistas = vistas + 1 WHERE id = %d",
            $archivo_id
        ));

        // Obtener tags
        $tabla_tags = $wpdb->prefix . 'flavor_multimedia_tags';
        $tags = $wpdb->get_col($wpdb->prepare(
            "SELECT tag FROM $tabla_tags WHERE archivo_id = %d",
            $archivo_id
        ));

        // Verificar si usuario actual dio like
        $usuario_dio_like = false;
        if (is_user_logged_in()) {
            $tabla_likes = $wpdb->prefix . 'flavor_multimedia_likes';
            $usuario_dio_like = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_likes WHERE archivo_id = %d AND usuario_id = %d",
                $archivo_id, get_current_user_id()
            ));
        }

        $data = $this->format_archivo($archivo);
        $data['tags'] = $tags;
        $data['usuario_dio_like'] = $usuario_dio_like;
        $data['vistas'] = $archivo->vistas + 1;

        $respuesta = ['success' => true, 'archivo' => $data];

        return new WP_REST_Response($this->sanitize_public_multimedia_response($respuesta), 200);
    }

    /**
     * REST: Subir archivo
     */
    public function rest_subir_archivo($request) {
        $settings = $this->get_settings();

        if (!$settings['permite_subir']) {
            return new WP_REST_Response(['error' => __('La subida de archivos está deshabilitada', 'flavor-chat-ia')], 403);
        }

        $files = $request->get_file_params();
        if (empty($files['archivo'])) {
            return new WP_REST_Response(['error' => __('No se recibió ningún archivo', 'flavor-chat-ia')], 400);
        }

        $file = $files['archivo'];
        $titulo = sanitize_text_field($request->get_param('titulo') ?: '');
        $descripcion = sanitize_textarea_field($request->get_param('descripcion') ?: '');
        $album_id = absint($request->get_param('album_id') ?: 0);
        $privacidad = sanitize_text_field($request->get_param('privacidad') ?: 'comunidad');
        $tags = array_map('sanitize_text_field', (array) $request->get_param('tags'));
        $lat = floatval($request->get_param('ubicacion_lat') ?: 0);
        $lng = floatval($request->get_param('ubicacion_lng') ?: 0);
        $ubicacion_nombre = sanitize_text_field($request->get_param('ubicacion_nombre') ?: '');

        // Determinar tipo
        $mime = $file['type'];
        $tipo = $this->get_tipo_from_mime($mime);

        if (!$tipo) {
            return new WP_REST_Response(['error' => __('Tipo de archivo no permitido', 'flavor-chat-ia')], 400);
        }

        // Verificar tamaño
        $max_size = $this->get_max_size($tipo);
        if ($file['size'] > $max_size) {
            return new WP_REST_Response([
                'error' => sprintf('El archivo excede el tamaño máximo permitido (%s MB)', $max_size / (1024 * 1024))
            ], 400);
        }

        // Subir archivo usando WordPress
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($file, ['test_form' => false]);

        if (isset($upload['error'])) {
            return new WP_REST_Response(['error' => $upload['error']], 500);
        }

        $archivo_url = $upload['url'];
        $archivo_path = $upload['file'];

        // Generar thumbnail si es imagen
        $thumbnail_url = null;
        $ancho = null;
        $alto = null;
        $duracion = null;

        if ($tipo === 'imagen') {
            $image_info = getimagesize($archivo_path);
            if ($image_info) {
                $ancho = $image_info[0];
                $alto = $image_info[1];
            }

            if ($settings['genera_thumbnails']) {
                $thumbnail_url = $this->generate_thumbnail($archivo_path, $archivo_url);
            }
        }

        // Determinar estado
        $estado = $privacidad;
        if ($settings['requiere_moderacion'] && $privacidad !== 'privado') {
            $estado = 'pendiente';
        }

        // Guardar en BD
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';
        $usuario_id = get_current_user_id();

        $inserted = $wpdb->insert($tabla, [
            'usuario_id' => $usuario_id,
            'album_id' => $album_id ?: null,
            'titulo' => $titulo ?: pathinfo($file['name'], PATHINFO_FILENAME),
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'archivo_url' => $archivo_url,
            'archivo_path' => $archivo_path,
            'thumbnail_url' => $thumbnail_url,
            'mime_type' => $mime,
            'tamano_bytes' => $file['size'],
            'ancho' => $ancho,
            'alto' => $alto,
            'duracion_segundos' => $duracion,
            'ubicacion_lat' => $lat ?: null,
            'ubicacion_lng' => $lng ?: null,
            'ubicacion_nombre' => $ubicacion_nombre ?: null,
            'estado' => $estado,
        ], ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%f', '%f', '%s', '%s']);

        if (!$inserted) {
            return new WP_REST_Response(['error' => __('Error al guardar en base de datos', 'flavor-chat-ia')], 500);
        }

        $archivo_id = $wpdb->insert_id;

        // Guardar tags
        if (!empty($tags)) {
            $tabla_tags = $wpdb->prefix . 'flavor_multimedia_tags';
            foreach ($tags as $tag) {
                if ($tag) {
                    $wpdb->insert($tabla_tags, [
                        'archivo_id' => $archivo_id,
                        'tag' => $tag,
                    ], ['%d', '%s']);
                }
            }
        }

        // Actualizar contador del álbum
        if ($album_id) {
            $this->update_album_count($album_id);
        }

        // Gamificación
        $puntos = $tipo === 'video' ? $settings['puntos_subir_video'] : $settings['puntos_subir_foto'];
        do_action('flavor_gamificacion_agregar_puntos', $usuario_id, $puntos, 'subir_multimedia');

        // Notificación si requiere moderación
        if ($estado === 'pendiente') {
            do_action('flavor_notificacion_enviar', 0, 'admin_mm_pendiente', [
                'archivo_id' => $archivo_id,
                'tipo' => $tipo,
                'usuario' => wp_get_current_user()->display_name,
            ]);
        }

        return new WP_REST_Response([
            'success' => true,
            'archivo_id' => $archivo_id,
            'url' => $archivo_url,
            'thumbnail' => $thumbnail_url,
            'estado' => $estado,
            'mensaje' => $estado === 'pendiente'
                ? 'Tu archivo ha sido enviado para moderación'
                : 'Archivo subido correctamente',
        ], 201);
    }

    /**
     * REST: Toggle like
     */
    public function rest_toggle_like($request) {
        global $wpdb;
        $tabla_likes = $wpdb->prefix . 'flavor_multimedia_likes';
        $tabla_mm = $wpdb->prefix . 'flavor_multimedia';

        $archivo_id = absint($request->get_param('id'));
        $usuario_id = get_current_user_id();

        // Verificar que archivo existe
        $archivo = $wpdb->get_row($wpdb->prepare(
            "SELECT id, usuario_id, me_gusta FROM $tabla_mm WHERE id = %d",
            $archivo_id
        ));

        if (!$archivo) {
            return new WP_REST_Response(['error' => __('Archivo no encontrado', 'flavor-chat-ia')], 404);
        }

        // Verificar si ya dio like
        $like_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_likes WHERE archivo_id = %d AND usuario_id = %d",
            $archivo_id, $usuario_id
        ));

        if ($like_existente) {
            // Quitar like
            $wpdb->delete($tabla_likes, ['id' => $like_existente], ['%d']);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mm SET me_gusta = GREATEST(0, me_gusta - 1) WHERE id = %d",
                $archivo_id
            ));
            $liked = false;
        } else {
            // Dar like
            $wpdb->insert($tabla_likes, [
                'archivo_id' => $archivo_id,
                'usuario_id' => $usuario_id,
            ], ['%d', '%d']);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mm SET me_gusta = me_gusta + 1 WHERE id = %d",
                $archivo_id
            ));
            $liked = true;

            // Gamificación al autor
            $settings = $this->get_settings();
            if ((int) $archivo->usuario_id !== $usuario_id) {
                do_action('flavor_gamificacion_agregar_puntos', $archivo->usuario_id, $settings['puntos_recibir_like'], 'recibir_like_mm');
            }
        }

        $nuevo_count = $wpdb->get_var($wpdb->prepare(
            "SELECT me_gusta FROM $tabla_mm WHERE id = %d",
            $archivo_id
        ));

        return new WP_REST_Response([
            'success' => true,
            'liked' => $liked,
            'me_gusta' => (int) $nuevo_count,
        ], 200);
    }

    /**
     * REST: Obtener comentarios
     */
    public function rest_get_comentarios($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia_comentarios';

        $archivo_id = absint($request->get_param('id'));
        $limite = absint($request->get_param('limite') ?: 50);
        $offset = absint($request->get_param('offset') ?: 0);

        $comentarios = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name as autor_nombre
             FROM $tabla c
             LEFT JOIN {$wpdb->users} u ON c.usuario_id = u.ID
             WHERE c.archivo_id = %d AND c.estado = 'visible'
             ORDER BY c.fecha DESC
             LIMIT %d OFFSET %d",
            $archivo_id, $limite, $offset
        ));

        $data = [];
        foreach ($comentarios as $c) {
            $data[] = [
                'id' => $c->id,
                'comentario' => esc_html($c->comentario),
                'autor' => [
                    'id' => $c->usuario_id,
                    'nombre' => $c->autor_nombre,
                    'avatar' => get_avatar_url($c->usuario_id, ['size' => 48]),
                ],
                'parent_id' => $c->parent_id,
                'fecha' => $c->fecha,
                'fecha_humana' => human_time_diff(strtotime($c->fecha)) . ' ago',
            ];
        }

        $respuesta = ['success' => true, 'comentarios' => $data];

        return new WP_REST_Response($this->sanitize_public_multimedia_response($respuesta), 200);
    }

    /**
     * REST: Comentar
     */
    public function rest_comentar($request) {
        global $wpdb;
        $tabla_mm = $wpdb->prefix . 'flavor_multimedia';
        $tabla_com = $wpdb->prefix . 'flavor_multimedia_comentarios';

        $archivo_id = absint($request->get_param('id'));
        $comentario = sanitize_textarea_field($request->get_param('comentario'));
        $parent_id = absint($request->get_param('parent_id') ?: 0);

        if (empty($comentario)) {
            return new WP_REST_Response(['error' => __('El comentario no puede estar vacío', 'flavor-chat-ia')], 400);
        }

        // Verificar archivo
        $archivo = $wpdb->get_row($wpdb->prepare(
            "SELECT id, usuario_id, permite_comentarios FROM $tabla_mm WHERE id = %d",
            $archivo_id
        ));

        if (!$archivo) {
            return new WP_REST_Response(['error' => __('Archivo no encontrado', 'flavor-chat-ia')], 404);
        }

        if (!$archivo->permite_comentarios) {
            return new WP_REST_Response(['error' => __('Los comentarios están deshabilitados', 'flavor-chat-ia')], 403);
        }

        $usuario_id = get_current_user_id();

        $wpdb->insert($tabla_com, [
            'archivo_id' => $archivo_id,
            'usuario_id' => $usuario_id,
            'comentario' => $comentario,
            'parent_id' => $parent_id ?: null,
        ], ['%d', '%d', '%s', '%d']);

        $comentario_id = $wpdb->insert_id;

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_mm SET comentarios_count = comentarios_count + 1 WHERE id = %d",
            $archivo_id
        ));

        // Notificar al autor
        if ((int) $archivo->usuario_id !== $usuario_id) {
            do_action('flavor_notificacion_enviar', $archivo->usuario_id, 'mm_nuevo_comentario', [
                'archivo_id' => $archivo_id,
                'comentario_por' => wp_get_current_user()->display_name,
            ]);
        }

        $user = wp_get_current_user();

        return new WP_REST_Response([
            'success' => true,
            'comentario' => [
                'id' => $comentario_id,
                'comentario' => esc_html($comentario),
                'autor' => [
                    'id' => $usuario_id,
                    'nombre' => $user->display_name,
                    'avatar' => get_avatar_url($usuario_id, ['size' => 48]),
                ],
                'fecha' => current_time('mysql'),
                'fecha_humana' => 'Ahora',
            ],
        ], 201);
    }

    /**
     * REST: Obtener álbumes
     */
    public function rest_get_albumes($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia_albumes';
        $tabla_mm = $wpdb->prefix . 'flavor_multimedia';

        $usuario_id = absint($request->get_param('usuario_id') ?: 0);
        $limite = absint($request->get_param('limite') ?: 20);

        $where = ["a.privacidad IN ('publico', 'comunidad')"];
        $params = [];

        if ($usuario_id > 0) {
            $where[] = 'a.usuario_id = %d';
            $params[] = $usuario_id;
        }

        $sql = "SELECT a.*, u.display_name as autor_nombre,
                       (SELECT archivo_url FROM $tabla_mm WHERE id = a.portada_id) as portada_url,
                       (SELECT thumbnail_url FROM $tabla_mm WHERE id = a.portada_id) as portada_thumbnail
                FROM $tabla a
                LEFT JOIN {$wpdb->users} u ON a.usuario_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.orden ASC, a.fecha_creacion DESC
                LIMIT %d";

        $params[] = $limite;

        $albumes = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $data = [];
        foreach ($albumes as $album) {
            $data[] = [
                'id' => $album->id,
                'nombre' => $album->nombre,
                'slug' => $album->slug,
                'descripcion' => $album->descripcion,
                'portada' => $album->portada_thumbnail ?: $album->portada_url ?: null,
                'archivos_count' => (int) $album->archivos_count,
                'autor' => [
                    'id' => $album->usuario_id,
                    'nombre' => $album->autor_nombre,
                ],
                'privacidad' => $album->privacidad,
                'fecha' => $album->fecha_creacion,
            ];
        }

        $respuesta = ['success' => true, 'albumes' => $data];

        return new WP_REST_Response($this->sanitize_public_multimedia_response($respuesta), 200);
    }

    /**
     * REST: Crear álbum
     */
    public function rest_crear_album($request) {
        $settings = $this->get_settings();

        if (!$settings['permite_albumes']) {
            return new WP_REST_Response(['error' => __('La creación de álbumes está deshabilitada', 'flavor-chat-ia')], 403);
        }

        $nombre = sanitize_text_field($request->get_param('nombre'));
        $descripcion = sanitize_textarea_field($request->get_param('descripcion') ?: '');
        $privacidad = sanitize_text_field($request->get_param('privacidad') ?: 'comunidad');

        if (empty($nombre)) {
            return new WP_REST_Response(['error' => __('El nombre es obligatorio', 'flavor-chat-ia')], 400);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia_albumes';
        $usuario_id = get_current_user_id();

        $slug = sanitize_title($nombre);
        $slug_base = $slug;
        $counter = 1;
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $tabla WHERE slug = %s", $slug))) {
            $slug = $slug_base . '-' . $counter++;
        }

        $wpdb->insert($tabla, [
            'usuario_id' => $usuario_id,
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => $descripcion,
            'privacidad' => $privacidad,
        ], ['%d', '%s', '%s', '%s', '%s']);

        return new WP_REST_Response([
            'success' => true,
            'album_id' => $wpdb->insert_id,
            'slug' => $slug,
            'mensaje' => __('Álbum creado correctamente', 'flavor-chat-ia'),
        ], 201);
    }

    /**
     * REST: Mis archivos
     */
    public function rest_mis_archivos($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        $usuario_id = get_current_user_id();
        $tipo = sanitize_text_field($request->get_param('tipo') ?: '');
        $album_id = absint($request->get_param('album_id') ?: 0);
        $limite = absint($request->get_param('limite') ?: 20);
        $pagina = absint($request->get_param('pagina') ?: 1);

        $where = ['m.usuario_id = %d'];
        $params = [$usuario_id];

        if ($tipo && in_array($tipo, ['imagen', 'video', 'audio'])) {
            $where[] = 'm.tipo = %s';
            $params[] = $tipo;
        }

        if ($album_id > 0) {
            $where[] = 'm.album_id = %d';
            $params[] = $album_id;
        }

        $offset = ($pagina - 1) * $limite;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla m WHERE " . implode(' AND ', $where),
            ...$params
        ));

        $params[] = $limite;
        $params[] = $offset;

        $archivos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla m WHERE " . implode(' AND ', $where) . " ORDER BY fecha_creacion DESC LIMIT %d OFFSET %d",
            ...$params
        ));

        $data = [];
        foreach ($archivos as $archivo) {
            $data[] = $this->format_archivo($archivo);
        }

        return new WP_REST_Response([
            'success' => true,
            'archivos' => $data,
            'total' => (int) $total,
            'paginas' => ceil($total / $limite),
        ], 200);
    }

    /**
     * REST: Tags populares
     */
    public function rest_get_tags($request) {
        global $wpdb;
        $tabla_tags = $wpdb->prefix . 'flavor_multimedia_tags';
        $tabla_mm = $wpdb->prefix . 'flavor_multimedia';

        $limite = absint($request->get_param('limite') ?: 30);

        $tags = $wpdb->get_results($wpdb->prepare(
            "SELECT t.tag, COUNT(*) as count
             FROM $tabla_tags t
             INNER JOIN $tabla_mm m ON t.archivo_id = m.id
             WHERE m.estado IN ('publico', 'comunidad')
             GROUP BY t.tag
             ORDER BY count DESC
             LIMIT %d",
            $limite
        ));

        return new WP_REST_Response(['success' => true, 'tags' => $tags], 200);
    }

    /**
     * REST: Reportar archivo
     */
    public function rest_reportar($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia_reportes';

        $archivo_id = absint($request->get_param('id'));
        $motivo = sanitize_text_field($request->get_param('motivo'));
        $descripcion = sanitize_textarea_field($request->get_param('descripcion') ?: '');

        if (!$motivo) {
            return new WP_REST_Response(['error' => __('Debes indicar un motivo', 'flavor-chat-ia')], 400);
        }

        $usuario_id = get_current_user_id();

        // Verificar si ya reportó
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE archivo_id = %d AND usuario_id = %d",
            $archivo_id, $usuario_id
        ));

        if ($existente) {
            return new WP_REST_Response(['error' => __('Ya has reportado este contenido', 'flavor-chat-ia')], 400);
        }

        $wpdb->insert($tabla, [
            'archivo_id' => $archivo_id,
            'usuario_id' => $usuario_id,
            'motivo' => $motivo,
            'descripcion' => $descripcion,
        ], ['%d', '%d', '%s', '%s']);

        // Notificar a admins
        do_action('flavor_notificacion_enviar', 0, 'admin_mm_reporte', [
            'archivo_id' => $archivo_id,
            'motivo' => $motivo,
            'reportado_por' => wp_get_current_user()->display_name,
        ]);

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Reporte enviado. Lo revisaremos pronto.', 'flavor-chat-ia'),
        ], 200);
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * AJAX: Subir archivo
     */
    public function ajax_subir_archivo() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        // Crear request simulado para REST
        $_REQUEST['titulo'] = sanitize_text_field($_POST['titulo'] ?? '');
        $_REQUEST['descripcion'] = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $_REQUEST['album_id'] = absint($_POST['album_id'] ?? 0);
        $_REQUEST['privacidad'] = sanitize_text_field($_POST['privacidad'] ?? 'comunidad');
        $_REQUEST['tags'] = isset($_POST['tags']) ? array_map('sanitize_text_field', (array) $_POST['tags']) : [];

        $request = new WP_REST_Request('POST');
        $request->set_file_params($_FILES);
        $request->set_param('titulo', $_REQUEST['titulo']);
        $request->set_param('descripcion', $_REQUEST['descripcion']);
        $request->set_param('album_id', $_REQUEST['album_id']);
        $request->set_param('privacidad', $_REQUEST['privacidad']);
        $request->set_param('tags', $_REQUEST['tags']);

        $response = $this->rest_subir_archivo($request);
        $data = $response->get_data();

        if (isset($data['success']) && $data['success']) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data['error'] ?? __('Error al subir archivo', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Eliminar archivo
     */
    public function ajax_eliminar_archivo() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        $archivo_id = absint($_POST['archivo_id']);
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        $archivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $archivo_id
        ));

        if (!$archivo) {
            wp_send_json_error(__('Archivo no encontrado', 'flavor-chat-ia'));
        }

        if ((int) $archivo->usuario_id !== $usuario_id && !current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permiso para eliminar este archivo', 'flavor-chat-ia'));
        }

        // Eliminar archivo físico
        if ($archivo->archivo_path && file_exists($archivo->archivo_path)) {
            unlink($archivo->archivo_path);
        }

        // Eliminar thumbnail
        if ($archivo->thumbnail_url) {
            $thumb_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $archivo->thumbnail_url);
            if (file_exists($thumb_path)) {
                unlink($thumb_path);
            }
        }

        // Eliminar de BD
        $wpdb->delete($tabla, ['id' => $archivo_id], ['%d']);

        // Eliminar tags
        $wpdb->delete($wpdb->prefix . 'flavor_multimedia_tags', ['archivo_id' => $archivo_id], ['%d']);

        // Eliminar likes
        $wpdb->delete($wpdb->prefix . 'flavor_multimedia_likes', ['archivo_id' => $archivo_id], ['%d']);

        // Eliminar comentarios
        $wpdb->delete($wpdb->prefix . 'flavor_multimedia_comentarios', ['archivo_id' => $archivo_id], ['%d']);

        // Actualizar contador del álbum
        if ($archivo->album_id) {
            $this->update_album_count($archivo->album_id);
        }

        wp_send_json_success(['mensaje' => __('Archivo eliminado correctamente', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Editar archivo
     */
    public function ajax_editar_archivo() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        $archivo_id = absint($_POST['archivo_id']);
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        $archivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $archivo_id
        ));

        if (!$archivo) {
            wp_send_json_error(__('Archivo no encontrado', 'flavor-chat-ia'));
        }

        if ((int) $archivo->usuario_id !== $usuario_id && !current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permiso', 'flavor-chat-ia'));
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? $archivo->titulo);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? $archivo->descripcion);
        $privacidad = sanitize_text_field($_POST['privacidad'] ?? $archivo->estado);
        $permite_comentarios = isset($_POST['permite_comentarios']) ? absint($_POST['permite_comentarios']) : $archivo->permite_comentarios;
        $permite_descargas = isset($_POST['permite_descargas']) ? absint($_POST['permite_descargas']) : $archivo->permite_descargas;

        $wpdb->update($tabla, [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'estado' => $privacidad,
            'permite_comentarios' => $permite_comentarios,
            'permite_descargas' => $permite_descargas,
        ], ['id' => $archivo_id], ['%s', '%s', '%s', '%d', '%d'], ['%d']);

        // Actualizar tags si se enviaron
        if (isset($_POST['tags'])) {
            $tabla_tags = $wpdb->prefix . 'flavor_multimedia_tags';
            $wpdb->delete($tabla_tags, ['archivo_id' => $archivo_id], ['%d']);

            $tags = array_map('sanitize_text_field', (array) $_POST['tags']);
            foreach ($tags as $tag) {
                if ($tag) {
                    $wpdb->insert($tabla_tags, ['archivo_id' => $archivo_id, 'tag' => $tag], ['%d', '%s']);
                }
            }
        }

        wp_send_json_success(['mensaje' => __('Archivo actualizado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Me gusta
     */
    public function ajax_me_gusta() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('id', absint($_POST['archivo_id']));

        $response = $this->rest_toggle_like($request);
        $data = $response->get_data();

        wp_send_json_success($data);
    }

    /**
     * AJAX: Comentar
     */
    public function ajax_comentar() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('id', absint($_POST['archivo_id']));
        $request->set_param('comentario', sanitize_textarea_field($_POST['comentario']));
        $request->set_param('parent_id', absint($_POST['parent_id'] ?? 0));

        $response = $this->rest_comentar($request);
        $data = $response->get_data();

        if (isset($data['success']) && $data['success']) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data['error'] ?? __('Error al comentar', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Crear álbum
     */
    public function ajax_crear_album() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('nombre', sanitize_text_field($_POST['nombre']));
        $request->set_param('descripcion', sanitize_textarea_field($_POST['descripcion'] ?? ''));
        $request->set_param('privacidad', sanitize_text_field($_POST['privacidad'] ?? 'comunidad'));

        $response = $this->rest_crear_album($request);
        $data = $response->get_data();

        if (isset($data['success']) && $data['success']) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data['error'] ?? __('Error al crear álbum', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Editar álbum
     */
    public function ajax_editar_album() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        $album_id = absint($_POST['album_id']);
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia_albumes';

        $album = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $album_id
        ));

        if (!$album || ((int) $album->usuario_id !== $usuario_id && !current_user_can('manage_options'))) {
            wp_send_json_error(__('No tienes permiso', 'flavor-chat-ia'));
        }

        $nombre = sanitize_text_field($_POST['nombre']);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $privacidad = sanitize_text_field($_POST['privacidad'] ?? 'comunidad');
        $portada_id = absint($_POST['portada_id'] ?? 0);

        $wpdb->update($tabla, [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'privacidad' => $privacidad,
            'portada_id' => $portada_id ?: null,
        ], ['id' => $album_id], ['%s', '%s', '%s', '%d'], ['%d']);

        wp_send_json_success(['mensaje' => __('Álbum actualizado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Eliminar álbum
     */
    public function ajax_eliminar_album() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        $album_id = absint($_POST['album_id']);
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia_albumes';
        $tabla_mm = $wpdb->prefix . 'flavor_multimedia';

        $album = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $album_id
        ));

        if (!$album || ((int) $album->usuario_id !== $usuario_id && !current_user_can('manage_options'))) {
            wp_send_json_error(__('No tienes permiso', 'flavor-chat-ia'));
        }

        // Mover archivos a sin álbum
        $wpdb->update($tabla_mm, ['album_id' => null], ['album_id' => $album_id], ['%d'], ['%d']);

        // Eliminar álbum
        $wpdb->delete($tabla, ['id' => $album_id], ['%d']);

        wp_send_json_success(['mensaje' => __('Álbum eliminado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Mover archivo a otro álbum
     */
    public function ajax_mover_archivo() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        $archivo_id = absint($_POST['archivo_id']);
        $nuevo_album_id = absint($_POST['album_id'] ?? 0);
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        $archivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $archivo_id
        ));

        if (!$archivo || ((int) $archivo->usuario_id !== $usuario_id && !current_user_can('manage_options'))) {
            wp_send_json_error(__('No tienes permiso', 'flavor-chat-ia'));
        }

        $album_anterior = $archivo->album_id;

        $wpdb->update($tabla,
            ['album_id' => $nuevo_album_id ?: null],
            ['id' => $archivo_id],
            ['%d'],
            ['%d']
        );

        // Actualizar contadores
        if ($album_anterior) {
            $this->update_album_count($album_anterior);
        }
        if ($nuevo_album_id) {
            $this->update_album_count($nuevo_album_id);
        }

        wp_send_json_success(['mensaje' => __('Archivo movido', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Reportar archivo
     */
    public function ajax_reportar_archivo() {
        check_ajax_referer('flavor_mm_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('id', absint($_POST['archivo_id']));
        $request->set_param('motivo', sanitize_text_field($_POST['motivo']));
        $request->set_param('descripcion', sanitize_textarea_field($_POST['descripcion'] ?? ''));

        $response = $this->rest_reportar($request);
        $data = $response->get_data();

        if (isset($data['success']) && $data['success']) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data['error'] ?? __('Error al reportar', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Galería pública
     */
    public function ajax_galeria_publica() {
        $request = new WP_REST_Request('GET');
        $request->set_param('tipo', sanitize_text_field($_GET['tipo'] ?? ''));
        $request->set_param('album_id', absint($_GET['album_id'] ?? 0));
        $request->set_param('tag', sanitize_text_field($_GET['tag'] ?? ''));
        $request->set_param('busqueda', sanitize_text_field($_GET['busqueda'] ?? ''));
        $request->set_param('limite', absint($_GET['limite'] ?? 20));
        $request->set_param('pagina', absint($_GET['pagina'] ?? 1));
        $request->set_param('orden', sanitize_text_field($_GET['orden'] ?? 'recientes'));

        $response = $this->rest_get_galeria($request);
        wp_send_json_success($response->get_data());
    }

    /**
     * AJAX: Detalle archivo
     */
    public function ajax_detalle_archivo() {
        $request = new WP_REST_Request('GET');
        $request->set_param('id', absint($_GET['archivo_id']));

        $response = $this->rest_get_archivo($request);
        $data = $response->get_data();

        if ($response->get_status() === 200) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data['error'] ?? __('Error', 'flavor-chat-ia'));
        }
    }

    // =========================================================================
    // Admin AJAX
    // =========================================================================

    /**
     * Admin AJAX: Moderar archivo
     */
    public function ajax_admin_moderar() {
        check_ajax_referer('flavor_mm_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        $archivo_id = absint($_POST['archivo_id']);
        $accion = sanitize_text_field($_POST['accion_moderacion']); // aprobar, rechazar

        $nuevo_estado = $accion === 'aprobar' ? 'comunidad' : 'rechazado';

        $wpdb->update($tabla,
            ['estado' => $nuevo_estado],
            ['id' => $archivo_id],
            ['%s'],
            ['%d']
        );

        // Notificar al usuario
        $archivo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $archivo_id));
        if ($archivo) {
            do_action('flavor_notificacion_enviar', $archivo->usuario_id, 'mm_moderacion', [
                'resultado' => $accion,
                'archivo_titulo' => $archivo->titulo,
            ]);
        }

        $estado_archivo = $accion === 'aprobar' ? __('aprobado', 'flavor-chat-ia') : __('rechazado', 'flavor-chat-ia');
        wp_send_json_success(['mensaje' => sprintf(__('Archivo %s', 'flavor-chat-ia'), $estado_archivo)]);
    }

    /**
     * Admin AJAX: Destacar archivo
     */
    public function ajax_admin_destacar() {
        check_ajax_referer('flavor_mm_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        $archivo_id = absint($_POST['archivo_id']);

        $actual = $wpdb->get_var($wpdb->prepare(
            "SELECT destacado FROM $tabla WHERE id = %d",
            $archivo_id
        ));

        $nuevo = $actual ? 0 : 1;

        $wpdb->update($tabla, ['destacado' => $nuevo], ['id' => $archivo_id], ['%d'], ['%d']);

        wp_send_json_success([
            'destacado' => (bool) $nuevo,
            'mensaje' => $nuevo ? 'Marcado como destacado' : 'Quitado de destacados',
        ]);
    }

    /**
     * Admin AJAX: Estadísticas
     */
    public function ajax_admin_stats() {
        check_ajax_referer('flavor_mm_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';
        $tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';
        $tabla_reportes = $wpdb->prefix . 'flavor_multimedia_reportes';

        // Consolidar 7 queries a tabla multimedia en 1 query
        $mm_stats = $wpdb->get_row(
            "SELECT
                COUNT(*) AS total_archivos,
                SUM(CASE WHEN tipo = 'imagen' THEN 1 ELSE 0 END) AS imagenes,
                SUM(CASE WHEN tipo = 'video' THEN 1 ELSE 0 END) AS videos,
                SUM(CASE WHEN tipo = 'audio' THEN 1 ELSE 0 END) AS audios,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes_moderacion,
                COALESCE(SUM(vistas), 0) AS total_vistas,
                COALESCE(SUM(me_gusta), 0) AS total_likes,
                COALESCE(SUM(tamano_bytes), 0) AS espacio_usado_bytes
            FROM $tabla"
        );

        $stats = [
            'total_archivos' => (int) ($mm_stats->total_archivos ?? 0),
            'imagenes' => (int) ($mm_stats->imagenes ?? 0),
            'videos' => (int) ($mm_stats->videos ?? 0),
            'audios' => (int) ($mm_stats->audios ?? 0),
            'total_albumes' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_albumes"),
            'pendientes_moderacion' => (int) ($mm_stats->pendientes_moderacion ?? 0),
            'reportes_pendientes' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_reportes WHERE estado = 'pendiente'"),
            'total_vistas' => (int) ($mm_stats->total_vistas ?? 0),
            'total_likes' => (int) ($mm_stats->total_likes ?? 0),
            'espacio_usado_bytes' => (int) ($mm_stats->espacio_usado_bytes ?? 0),
        ];

        // Top archivos
        $stats['top_archivos'] = $wpdb->get_results(
            "SELECT id, titulo, vistas, me_gusta, thumbnail_url
             FROM $tabla
             WHERE estado IN ('publico', 'comunidad')
             ORDER BY vistas DESC
             LIMIT 5"
        );

        // Archivos por mes (últimos 6 meses)
        $stats['por_mes'] = $wpdb->get_results(
            "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total
             FROM $tabla
             WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY mes
             ORDER BY mes"
        );

        wp_send_json_success($stats);
    }

    // =========================================================================
    // Shortcodes
    // =========================================================================

    /**
     * Shortcode: Galería
     */
    public function shortcode_galeria($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'album_id' => 0,
            'limite' => 20,
            'columnas' => 4,
            'orden' => 'recientes',
            'mostrar_filtros' => 'true',
            'lightbox' => 'true',
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-mm-galeria"
             data-tipo="<?php echo esc_attr($atts['tipo']); ?>"
             data-album="<?php echo esc_attr($atts['album_id']); ?>"
             data-limite="<?php echo esc_attr($atts['limite']); ?>"
             data-columnas="<?php echo esc_attr($atts['columnas']); ?>"
             data-orden="<?php echo esc_attr($atts['orden']); ?>"
             data-filtros="<?php echo esc_attr($atts['mostrar_filtros']); ?>"
             data-lightbox="<?php echo esc_attr($atts['lightbox']); ?>">

            <?php if ($atts['mostrar_filtros'] === 'true'): ?>
            <div class="mm-filtros">
                <div class="mm-filtros-tipo">
                    <button class="mm-filtro-btn active" data-tipo=""><?php _e('Todos', 'flavor-chat-ia'); ?></button>
                    <button class="mm-filtro-btn" data-tipo="imagen"><?php _e('Fotos', 'flavor-chat-ia'); ?></button>
                    <button class="mm-filtro-btn" data-tipo="video"><?php _e('Videos', 'flavor-chat-ia'); ?></button>
                </div>
                <div class="mm-filtros-orden">
                    <select class="mm-orden-select">
                        <option value="<?php echo esc_attr__('recientes', 'flavor-chat-ia'); ?>"><?php _e('Más recientes', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('populares', 'flavor-chat-ia'); ?>"><?php _e('Más populares', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('vistas', 'flavor-chat-ia'); ?>"><?php _e('Más vistas', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <div class="mm-filtros-busqueda">
                    <input type="text" class="mm-busqueda-input" placeholder="<?php esc_attr_e('Buscar...', 'flavor-chat-ia'); ?>">
                </div>
            </div>
            <?php endif; ?>

            <div class="mm-grid mm-cols-<?php echo esc_attr($atts['columnas']); ?>">
                <div class="mm-loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
            </div>

            <div class="mm-paginacion"></div>
        </div>

        <!-- Lightbox -->
        <div class="mm-lightbox" style="display: none;">
            <div class="mm-lightbox-overlay"></div>
            <div class="mm-lightbox-content">
                <button class="mm-lightbox-close"><?php echo esc_html__('&times;', 'flavor-chat-ia'); ?></button>
                <button class="mm-lightbox-prev"><?php echo esc_html__('&lsaquo;', 'flavor-chat-ia'); ?></button>
                <button class="mm-lightbox-next"><?php echo esc_html__('&rsaquo;', 'flavor-chat-ia'); ?></button>
                <div class="mm-lightbox-media"></div>
                <div class="mm-lightbox-info">
                    <h3 class="mm-lightbox-titulo"></h3>
                    <p class="mm-lightbox-descripcion"></p>
                    <div class="mm-lightbox-meta">
                        <span class="mm-lightbox-autor"></span>
                        <span class="mm-lightbox-fecha"></span>
                    </div>
                    <div class="mm-lightbox-acciones">
                        <button class="mm-btn-like"><span class="dashicons dashicons-heart"></span> <span class="mm-like-count">0</span></button>
                        <button class="mm-btn-comentarios"><span class="dashicons dashicons-admin-comments"></span> <span class="mm-comentarios-count">0</span></button>
                        <button class="mm-btn-descargar"><span class="dashicons dashicons-download"></span></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Álbumes
     */
    public function shortcode_albumes($atts) {
        $atts = shortcode_atts([
            'limite' => 12,
            'columnas' => 3,
            'usuario_id' => 0,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-mm-albumes"
             data-limite="<?php echo esc_attr($atts['limite']); ?>"
             data-columnas="<?php echo esc_attr($atts['columnas']); ?>"
             data-usuario="<?php echo esc_attr($atts['usuario_id']); ?>">
            <div class="mm-albumes-grid mm-cols-<?php echo esc_attr($atts['columnas']); ?>">
                <div class="mm-loading"><?php _e('Cargando álbumes...', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Subir multimedia
     */
    public function shortcode_subir($atts) {
        if (!is_user_logged_in()) {
            return '<p class="mm-aviso">' . __('Debes iniciar sesión para subir contenido.', 'flavor-chat-ia') . '</p>';
        }

        $settings = $this->get_settings();
        if (!$settings['permite_subir']) {
            return '<p class="mm-aviso">' . __('La subida de archivos está deshabilitada.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-mm-subir">
            <form id="mm-form-subir" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_mm_nonce', 'mm_nonce'); ?>

                <div class="mm-dropzone" id="mm-dropzone">
                    <div class="mm-dropzone-content">
                        <span class="dashicons dashicons-cloud-upload"></span>
                        <p><?php _e('Arrastra archivos aquí o haz clic para seleccionar', 'flavor-chat-ia'); ?></p>
                        <p class="mm-formatos">
                            <?php printf(__('Formatos: %s', 'flavor-chat-ia'),
                                implode(', ', array_merge($settings['formatos_imagen'], $settings['formatos_video']))); ?>
                        </p>
                        <input type="file" id="mm-archivo-input" name="archivo" accept="image/*,video/*,audio/*" style="display: none;">
                    </div>
                    <div class="mm-preview" style="display: none;"></div>
                </div>

                <div class="mm-form-campos" style="display: none;">
                    <div class="mm-campo">
                        <label for="mm-titulo"><?php _e('Título', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="mm-titulo" name="titulo" placeholder="<?php esc_attr_e('Nombre del archivo', 'flavor-chat-ia'); ?>">
                    </div>

                    <div class="mm-campo">
                        <label for="mm-descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
                        <textarea id="mm-descripcion" name="descripcion" rows="3" placeholder="<?php esc_attr_e('Añade una descripción...', 'flavor-chat-ia'); ?>"></textarea>
                    </div>

                    <?php if ($settings['permite_albumes']): ?>
                    <div class="mm-campo">
                        <label for="mm-album"><?php _e('Álbum', 'flavor-chat-ia'); ?></label>
                        <select id="mm-album" name="album_id">
                            <option value=""><?php _e('Sin álbum', 'flavor-chat-ia'); ?></option>
                        </select>
                        <button type="button" class="mm-btn-nuevo-album"><?php _e('Crear álbum', 'flavor-chat-ia'); ?></button>
                    </div>
                    <?php endif; ?>

                    <div class="mm-campo">
                        <label for="mm-tags"><?php _e('Etiquetas', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="mm-tags" name="tags" placeholder="<?php esc_attr_e('etiqueta1, etiqueta2...', 'flavor-chat-ia'); ?>">
                    </div>

                    <div class="mm-campo">
                        <label for="mm-privacidad"><?php _e('Privacidad', 'flavor-chat-ia'); ?></label>
                        <select id="mm-privacidad" name="privacidad">
                            <option value="<?php echo esc_attr__('comunidad', 'flavor-chat-ia'); ?>"><?php _e('Comunidad', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('publico', 'flavor-chat-ia'); ?>"><?php _e('Público', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('privado', 'flavor-chat-ia'); ?>"><?php _e('Privado', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="mm-campo mm-acciones">
                        <button type="submit" class="btn btn-primary mm-btn-subir">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Subir', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="btn btn-outline mm-btn-cancelar">
                            <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>

                <div class="mm-progress" style="display: none;">
                    <div class="mm-progress-bar"></div>
                    <span class="mm-progress-text">0%</span>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi galería
     */
    public function shortcode_mi_galeria($atts) {
        if (!is_user_logged_in()) {
            return '<p class="mm-aviso">' . __('Debes iniciar sesión para ver tu galería.', 'flavor-chat-ia') . '</p>';
        }

        $atts = shortcode_atts([
            'limite' => 20,
            'columnas' => 4,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-mm-mi-galeria">
            <div class="mm-mi-galeria-header">
                <h3><?php _e('Mi Galería', 'flavor-chat-ia'); ?></h3>
                <div class="mm-mi-galeria-tabs">
                    <button class="mm-tab active" data-tab="archivos"><?php _e('Archivos', 'flavor-chat-ia'); ?></button>
                    <button class="mm-tab" data-tab="albumes"><?php _e('Álbumes', 'flavor-chat-ia'); ?></button>
                </div>
            </div>

            <div class="mm-mi-galeria-content">
                <div class="mm-tab-content active" data-tab="archivos">
                    <div class="mm-mis-filtros">
                        <select class="mm-filtro-tipo">
                            <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('imagen', 'flavor-chat-ia'); ?>"><?php _e('Fotos', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('video', 'flavor-chat-ia'); ?>"><?php _e('Videos', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('audio', 'flavor-chat-ia'); ?>"><?php _e('Audios', 'flavor-chat-ia'); ?></option>
                        </select>
                        <select class="mm-filtro-album">
                            <option value=""><?php _e('Todos los álbumes', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                    <div class="mm-mis-archivos-grid mm-cols-<?php echo esc_attr($atts['columnas']); ?>"></div>
                    <div class="mm-paginacion"></div>
                </div>

                <div class="mm-tab-content" data-tab="albumes">
                    <button class="btn btn-primary mm-btn-crear-album">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Crear álbum', 'flavor-chat-ia'); ?>
                    </button>
                    <div class="mm-mis-albumes-grid mm-cols-3"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Carousel
     */
    public function shortcode_carousel($atts) {
        $atts = shortcode_atts([
            'album_id' => 0,
            'limite' => 10,
            'autoplay' => 'true',
            'intervalo' => 5,
            'destacados' => 'false',
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-mm-carousel"
             data-album="<?php echo esc_attr($atts['album_id']); ?>"
             data-limite="<?php echo esc_attr($atts['limite']); ?>"
             data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>"
             data-intervalo="<?php echo esc_attr($atts['intervalo']); ?>"
             data-destacados="<?php echo esc_attr($atts['destacados']); ?>">
            <div class="mm-carousel-slides"></div>
            <button class="mm-carousel-prev"><?php echo esc_html__('&lsaquo;', 'flavor-chat-ia'); ?></button>
            <button class="mm-carousel-next"><?php echo esc_html__('&rsaquo;', 'flavor-chat-ia'); ?></button>
            <div class="mm-carousel-dots"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Galería integrada para tabs de otros módulos
     *
     * Muestra multimedia asociada a una entidad específica (grupo, evento, etc.)
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_galeria_integrada($atts) {
        $atts = shortcode_atts([
            'entidad'      => '',           // Tipo de entidad (grupo_consumo, evento, comunidad, etc.)
            'entidad_id'   => 0,            // ID de la entidad
            'limite'       => 12,
            'columnas'     => 4,
            'permite_subir' => 'true',
            'mostrar_filtros' => 'true',
        ], $atts);

        $entidad_tipo = sanitize_key($atts['entidad']);
        $entidad_id = absint($atts['entidad_id']);

        if (!$entidad_tipo || !$entidad_id) {
            return $this->shortcode_galeria([
                'tipo' => '',
                'album_id' => 0,
                'limite' => $atts['limite'],
                'columnas' => $atts['columnas'],
                'orden' => 'recientes',
                'mostrar_filtros' => $atts['mostrar_filtros'],
                'lightbox' => 'true',
            ]);
        }

        // Verificar si el usuario puede subir
        $puede_subir = is_user_logged_in() && $atts['permite_subir'] === 'true';

        ob_start();
        $this->enqueue_frontend_assets();
        ?>
        <div class="flavor-mm-galeria-integrada"
             data-entidad="<?php echo esc_attr($entidad_tipo); ?>"
             data-entidad-id="<?php echo esc_attr($entidad_id); ?>"
             data-limite="<?php echo esc_attr($atts['limite']); ?>"
             data-columnas="<?php echo esc_attr($atts['columnas']); ?>">

            <?php if ($puede_subir): ?>
            <div class="mm-acciones-header">
                <button type="button" class="mm-btn-subir-rapido" data-entidad="<?php echo esc_attr($entidad_tipo); ?>" data-entidad-id="<?php echo esc_attr($entidad_id); ?>">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Subir foto/video', 'flavor-chat-ia'); ?>
                </button>
            </div>
            <?php endif; ?>

            <?php if ($atts['mostrar_filtros'] === 'true'): ?>
            <div class="mm-filtros mm-filtros-compactos">
                <div class="mm-filtros-tipo">
                    <button class="mm-filtro-btn active" data-tipo=""><?php _e('Todos', 'flavor-chat-ia'); ?></button>
                    <button class="mm-filtro-btn" data-tipo="imagen"><?php _e('Fotos', 'flavor-chat-ia'); ?></button>
                    <button class="mm-filtro-btn" data-tipo="video"><?php _e('Videos', 'flavor-chat-ia'); ?></button>
                </div>
            </div>
            <?php endif; ?>

            <div class="mm-grid mm-cols-<?php echo esc_attr($atts['columnas']); ?>">
                <div class="mm-loading"><?php _e('Cargando galería...', 'flavor-chat-ia'); ?></div>
            </div>

            <div class="mm-paginacion"></div>
        </div>

        <!-- Modal de subida rápida -->
        <?php if ($puede_subir): ?>
        <div class="mm-modal-subir" style="display: none;">
            <div class="mm-modal-overlay"></div>
            <div class="mm-modal-content">
                <button class="mm-modal-cerrar">&times;</button>
                <h3><?php _e('Subir multimedia', 'flavor-chat-ia'); ?></h3>
                <form class="mm-form-subir-rapido" enctype="multipart/form-data">
                    <?php wp_nonce_field('flavor_mm_nonce', 'mm_nonce'); ?>
                    <input type="hidden" name="entidad_tipo" value="<?php echo esc_attr($entidad_tipo); ?>">
                    <input type="hidden" name="entidad_id" value="<?php echo esc_attr($entidad_id); ?>">

                    <div class="mm-dropzone-mini">
                        <span class="dashicons dashicons-cloud-upload"></span>
                        <p><?php _e('Arrastra archivos o haz clic', 'flavor-chat-ia'); ?></p>
                        <input type="file" name="archivo" accept="image/*,video/*" multiple>
                    </div>

                    <div class="mm-preview-archivos"></div>

                    <div class="mm-form-campo">
                        <input type="text" name="descripcion" placeholder="<?php esc_attr_e('Descripción (opcional)', 'flavor-chat-ia'); ?>">
                    </div>

                    <button type="submit" class="mm-btn-enviar"><?php _e('Subir', 'flavor-chat-ia'); ?></button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // Actions del módulo
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'galeria' => [
                'description' => 'Ver galería de multimedia',
                'params' => ['tipo', 'album_id', 'limite', 'busqueda', 'orden'],
            ],
            'subir' => [
                'description' => 'Subir archivo multimedia',
                'params' => ['archivo_url', 'tipo', 'titulo', 'descripcion', 'album_id'],
            ],
            'albumes' => [
                'description' => 'Listar álbumes',
                'params' => ['usuario_id', 'limite'],
            ],
            'crear_album' => [
                'description' => 'Crear álbum',
                'params' => ['nombre', 'descripcion', 'privacidad'],
            ],
            'detalle' => [
                'description' => 'Ver detalle de archivo',
                'params' => ['archivo_id'],
            ],
            'mis_archivos' => [
                'description' => 'Ver mis archivos multimedia',
                'params' => ['tipo', 'album_id', 'limite'],
            ],
            'me_gusta' => [
                'description' => 'Dar/quitar me gusta',
                'params' => ['archivo_id'],
            ],
            'comentar' => [
                'description' => 'Comentar en archivo',
                'params' => ['archivo_id', 'comentario'],
            ],
            'buscar' => [
                'description' => 'Buscar multimedia',
                'params' => ['query', 'tipo'],
            ],
            'tags_populares' => [
                'description' => 'Ver tags populares',
                'params' => ['limite'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'galeria',
            'listado' => 'galeria',
            'explorar' => 'galeria',
            'buscar' => 'buscar',
            'crear' => 'subir',
            'nuevo' => 'subir',
            'albumes' => 'albumes',
            'galeria' => 'galeria',
            'crear_album' => 'crear_album',
            'detalle' => 'detalle',
            'ver' => 'detalle',
            'mis_items' => 'mis_archivos',
            'mis-archivos' => 'mis_archivos',
            'mi-galeria' => 'mis_archivos',
            'favoritos' => 'me_gusta',
            'me-gusta' => 'me_gusta',
            'tags' => 'tags_populares',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo_accion = 'action_' . $action_name;
        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }
        return ['success' => false, 'error' => __('La vista solicitada no está disponible en Multimedia.', 'flavor-chat-ia')];
    }

    private function action_galeria($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('tipo', $params['tipo'] ?? '');
        $request->set_param('album_id', $params['album_id'] ?? 0);
        $request->set_param('limite', $params['limite'] ?? 20);
        $request->set_param('busqueda', $params['busqueda'] ?? '');
        $request->set_param('orden', $params['orden'] ?? 'recientes');

        $response = $this->rest_get_galeria($request);
        return $response->get_data();
    }

    private function action_subir($params) {
        return do_shortcode('[flavor_subir_multimedia]');
    }

    private function action_albumes($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('usuario_id', $params['usuario_id'] ?? 0);
        $request->set_param('limite', $params['limite'] ?? 20);

        $response = $this->rest_get_albumes($request);
        return $response->get_data();
    }

    private function action_crear_album($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('nombre', $params['nombre'] ?? '');
        $request->set_param('descripcion', $params['descripcion'] ?? '');
        $request->set_param('privacidad', $params['privacidad'] ?? 'comunidad');

        $response = $this->rest_crear_album($request);
        return $response->get_data();
    }

    private function action_detalle($params) {
        if (empty($params['archivo_id'])) {
            return ['success' => false, 'error' => __('ID de archivo requerido', 'flavor-chat-ia')];
        }

        $request = new WP_REST_Request('GET');
        $request->set_param('id', $params['archivo_id']);

        $response = $this->rest_get_archivo($request);
        return $response->get_data();
    }

    private function action_mis_archivos($params) {
        return do_shortcode('[flavor_mi_galeria]');
    }

    private function action_me_gusta($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('id', $params['archivo_id'] ?? 0);

        $response = $this->rest_toggle_like($request);
        return $response->get_data();
    }

    private function action_comentar($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('id', $params['archivo_id'] ?? 0);
        $request->set_param('comentario', $params['comentario'] ?? '');

        $response = $this->rest_comentar($request);
        return $response->get_data();
    }

    private function action_buscar($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('busqueda', $params['query'] ?? '');
        $request->set_param('tipo', $params['tipo'] ?? '');
        $request->set_param('limite', 20);

        $response = $this->rest_get_galeria($request);
        return $response->get_data();
    }

    private function action_tags_populares($params) {
        $request = new WP_REST_Request('GET');
        $request->set_param('limite', $params['limite'] ?? 30);

        $response = $this->rest_get_tags($request);
        return $response->get_data();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function sanitize_public_multimedia_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['archivos']) && is_array($respuesta['archivos'])) {
            $respuesta['archivos'] = array_map([$this, 'sanitize_public_archivo'], $respuesta['archivos']);
        }

        if (!empty($respuesta['archivo']) && is_array($respuesta['archivo'])) {
            $respuesta['archivo'] = $this->sanitize_public_archivo($respuesta['archivo']);
        }

        if (!empty($respuesta['comentarios']) && is_array($respuesta['comentarios'])) {
            $respuesta['comentarios'] = array_map([$this, 'sanitize_public_comentario'], $respuesta['comentarios']);
        }

        if (!empty($respuesta['albumes']) && is_array($respuesta['albumes'])) {
            $respuesta['albumes'] = array_map([$this, 'sanitize_public_album'], $respuesta['albumes']);
        }

        return $respuesta;
    }

    private function sanitize_public_archivo($archivo) {
        if (!is_array($archivo)) {
            return $archivo;
        }

        if (!empty($archivo['autor']) && is_array($archivo['autor'])) {
            unset($archivo['autor']['id']);
            $archivo['autor']['avatar'] = '';
        }

        if (!empty($archivo['ubicacion']) && is_array($archivo['ubicacion'])) {
            $archivo['ubicacion'] = null;
        }

        return $archivo;
    }

    private function sanitize_public_comentario($comentario) {
        if (!is_array($comentario)) {
            return $comentario;
        }

        if (!empty($comentario['autor']) && is_array($comentario['autor'])) {
            unset($comentario['autor']['id']);
            $comentario['autor']['avatar'] = '';
        }

        return $comentario;
    }

    private function sanitize_public_album($album) {
        if (!is_array($album)) {
            return $album;
        }

        if (!empty($album['autor']) && is_array($album['autor'])) {
            unset($album['autor']['id']);
        }

        return $album;
    }

    /**
     * Formatear archivo para respuesta
     */
    private function format_archivo($archivo) {
        $autor = isset($archivo->autor_nombre) ? $archivo->autor_nombre : '';
        if (!$autor && $archivo->usuario_id) {
            $user = get_userdata($archivo->usuario_id);
            $autor = $user ? $user->display_name : 'Usuario';
        }

        return [
            'id' => $archivo->id,
            'tipo' => $archivo->tipo,
            'titulo' => $archivo->titulo,
            'descripcion' => $archivo->descripcion,
            'url' => $archivo->archivo_url,
            'thumbnail' => $archivo->thumbnail_url ?: $archivo->archivo_url,
            'ancho' => $archivo->ancho,
            'alto' => $archivo->alto,
            'duracion' => $archivo->duracion_segundos,
            'autor' => [
                'id' => $archivo->usuario_id,
                'nombre' => $autor,
                'avatar' => get_avatar_url($archivo->usuario_id, ['size' => 48]),
            ],
            'ubicacion' => [
                'lat' => $archivo->ubicacion_lat,
                'lng' => $archivo->ubicacion_lng,
                'nombre' => $archivo->ubicacion_nombre,
            ],
            'vistas' => (int) $archivo->vistas,
            'me_gusta' => (int) $archivo->me_gusta,
            'comentarios_count' => (int) $archivo->comentarios_count,
            'estado' => $archivo->estado,
            'destacado' => (bool) $archivo->destacado,
            'permite_descargas' => (bool) $archivo->permite_descargas,
            'permite_comentarios' => (bool) $archivo->permite_comentarios,
            'fecha' => $archivo->fecha_creacion,
            'fecha_humana' => human_time_diff(strtotime($archivo->fecha_creacion)) . ' ago',
        ];
    }

    /**
     * Obtener tipo de archivo desde MIME
     */
    private function get_tipo_from_mime($mime) {
        $settings = $this->get_settings();

        if (strpos($mime, 'image/') === 0) {
            $ext = str_replace('image/', '', $mime);
            if ($ext === 'jpeg') $ext = 'jpg';
            if (in_array($ext, $settings['formatos_imagen'])) {
                return 'imagen';
            }
        }

        if (strpos($mime, 'video/') === 0) {
            $ext = str_replace('video/', '', $mime);
            if (in_array($ext, $settings['formatos_video'])) {
                return 'video';
            }
        }

        if (strpos($mime, 'audio/') === 0) {
            $ext = str_replace('audio/', '', $mime);
            if ($ext === 'mpeg') $ext = 'mp3';
            if (in_array($ext, $settings['formatos_audio'])) {
                return 'audio';
            }
        }

        return null;
    }

    /**
     * Obtener tamaño máximo según tipo
     */
    private function get_max_size($tipo) {
        $settings = $this->get_settings();

        return match ($tipo) {
            'imagen' => $settings['max_tamano_imagen_mb'] * 1024 * 1024,
            'video' => $settings['max_tamano_video_mb'] * 1024 * 1024,
            'audio' => $settings['max_tamano_audio_mb'] * 1024 * 1024,
            default => 10 * 1024 * 1024,
        };
    }

    /**
     * Generar thumbnail
     */
    private function generate_thumbnail($archivo_path, $archivo_url) {
        $settings = $this->get_settings();

        $editor = wp_get_image_editor($archivo_path);
        if (is_wp_error($editor)) {
            return null;
        }

        $editor->resize($settings['thumbnail_width'], $settings['thumbnail_height'], true);
        $editor->set_quality($settings['calidad_jpeg']);

        $path_info = pathinfo($archivo_path);
        $thumb_path = $path_info['dirname'] . '/' . $path_info['filename'] . '-thumb.' . $path_info['extension'];

        $saved = $editor->save($thumb_path);
        if (is_wp_error($saved)) {
            return null;
        }

        // Convertir path a URL
        $upload_dir = wp_upload_dir();
        $thumb_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $thumb_path);

        return $thumb_url;
    }

    /**
     * Actualizar contador de álbum
     */
    private function update_album_count($album_id) {
        global $wpdb;
        $tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';
        $tabla_mm = $wpdb->prefix . 'flavor_multimedia';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_mm WHERE album_id = %d",
            $album_id
        ));

        $wpdb->update($tabla_albumes,
            ['archivos_count' => $count],
            ['id' => $album_id],
            ['%d'],
            ['%d']
        );
    }

    // =========================================================================
    // Admin & Assets
    // =========================================================================

    /**
     * Configuración para el Panel de Administración Unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'multimedia',
            'label' => __('Multimedia', 'flavor-chat-ia'),
            'icon' => 'dashicons-format-gallery',
            'capability' => 'manage_options',
            'categoria' => 'recursos',
            'paginas' => [
                [
                    'slug' => 'flavor-multimedia-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'flavor-multimedia-galeria',
                    'titulo' => __('Galería', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_galeria'],
                    'badge' => [$this, 'contar_archivos_pendientes'],
                ],
                [
                    'slug' => 'flavor-multimedia-configuracion',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_configuracion'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas' => [$this, 'get_estadisticas_admin'],
        ];
    }

    /**
     * Renderizar dashboard del panel unificado
     */
    public function render_admin_dashboard() {
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');
        $template = FLAVOR_CHAT_IA_PATH . 'includes/modules/multimedia/views/dashboard.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard Multimedia', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Panel de estadísticas y resumen del módulo multimedia.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Renderizar galería del panel unificado
     */
    public function render_admin_galeria() {
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');
        if ($is_dashboard_viewer) {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Galería', 'flavor-chat-ia') . '</h1>';
            echo '<div class="notice notice-info"><p>' . esc_html__('Vista de consulta para gestor de grupos. La gestión detallada de la galería y la moderación siguen reservadas a administración.', 'flavor-chat-ia') . '</p></div>';
            echo '<p><a class="button" href="' . esc_url(home_url('/mi-portal/multimedia/')) . '">' . esc_html__('Ver en portal', 'flavor-chat-ia') . '</a></p>';
            echo '</div>';
            return;
        }

        $template = FLAVOR_CHAT_IA_PATH . 'includes/modules/multimedia/views/admin-galeria.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Galería', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Gestión de archivos multimedia.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Renderizar configuración del panel unificado
     */
    public function render_admin_configuracion() {
        $template = FLAVOR_CHAT_IA_PATH . 'includes/modules/multimedia/views/admin-configuracion.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Configuración', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Ajustes del módulo multimedia.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Contar archivos pendientes de moderación
     *
     * @return int
     */
    public function contar_archivos_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_multimedia WHERE estado = 'pendiente'"
        );
    }

    /**
     * Renderizar widget del dashboard unificado
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

        $total_archivos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia");
        $archivos_pendientes = $this->contar_archivos_pendientes();
        $total_imagenes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE tipo = 'imagen'");
        $total_videos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE tipo = 'video'");

        ?>
        <div class="flavor-widget-multimedia">
            <div class="widget-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($total_archivos); ?></span>
                    <span class="stat-label"><?php esc_html_e('Total archivos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($total_imagenes); ?></span>
                    <span class="stat-label"><?php esc_html_e('Imágenes', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($total_videos); ?></span>
                    <span class="stat-label"><?php esc_html_e('Videos', 'flavor-chat-ia'); ?></span>
                </div>
                <?php if ($archivos_pendientes > 0): ?>
                <div class="stat-item stat-warning">
                    <span class="stat-number"><?php echo esc_html($archivos_pendientes); ?></span>
                    <span class="stat-label"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener estadísticas para el panel unificado
     *
     * @return array
     */
    public function get_estadisticas_admin() {
        global $wpdb;
        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

        $total_archivos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia");
        $archivos_pendientes = $this->contar_archivos_pendientes();
        $archivos_hoy = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_multimedia WHERE DATE(fecha_creacion) = CURDATE()"
        );

        return [
            'total_archivos' => $total_archivos,
            'archivos_pendientes' => $archivos_pendientes,
            'archivos_hoy' => $archivos_hoy,
        ];
    }

    /**
     * Agregar menú admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            __('Multimedia', 'flavor-chat-ia'),
            __('Multimedia', 'flavor-chat-ia'),
            'manage_options',
            'flavor-multimedia',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderizar página admin
     */
    public function render_admin_page() {
        $template = FLAVOR_CHAT_IA_PATH . 'includes/modules/multimedia/views/dashboard.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Multimedia', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . __('Panel de administración de multimedia.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Verifica si debe cargar los assets del módulo
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'flavor_galeria',
            'flavor_albumes',
            'flavor_subir_multimedia',
            'flavor_mi_galeria',
            'flavor_carousel',
            'flavor_multimedia_galeria',
            'flavor_multimedia_mis_fotos',
            'flavor_multimedia_subir',
            'flavor_multimedia_albumes',
            'flavor_multimedia_album',
            'flavor_multimedia_visor',
            'flavor_multimedia_dashboard',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->can_activate()) {
            return;
        }

        if (!$this->should_load_assets()) {
            return;
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style(
            'flavor-multimedia-css',
            $base_url . 'css/multimedia-frontend.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-multimedia-js',
            $base_url . 'js/multimedia-frontend.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-multimedia-js', 'flavorMultimedia', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'resturl' => rest_url('flavor/v1/multimedia/'),
            'nonce' => wp_create_nonce('flavor_mm_nonce'),
            'user_id' => get_current_user_id(),
            'strings' => [
                'loading' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error', 'flavor-chat-ia'),
                'no_results' => __('No se encontraron resultados', 'flavor-chat-ia'),
                'confirm_delete' => __('¿Eliminar este archivo?', 'flavor-chat-ia'),
                'uploading' => __('Subiendo...', 'flavor-chat-ia'),
                'upload_success' => __('Archivo subido correctamente', 'flavor-chat-ia'),
                'upload_error' => __('Error al subir archivo', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'flavor-multimedia') === false) {
            return;
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style(
            'flavor-multimedia-admin-css',
            $base_url . 'css/multimedia-admin.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-multimedia-admin-js',
            $base_url . 'js/multimedia-admin.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-multimedia-admin-js', 'flavorMMAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_mm_admin'),
        ]);
    }

    /**
     * Agregar tab al dashboard de usuario
     */
    public function add_dashboard_tab($tabs) {
        $tabs['mi-galeria'] = [
            'label' => __('Mi Galería', 'flavor-chat-ia'),
            'icon' => 'images-alt2',
            'callback' => [$this, 'render_dashboard_tab'],
            'orden' => 35,
        ];
        return $tabs;
    }

    /**
     * Renderizar tab del dashboard
     */
    public function render_dashboard_tab() {
        echo do_shortcode('[flavor_mi_galeria]');
    }

    /**
     * Cron: Limpieza de archivos
     */
    public function cron_cleanup_archivos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_multimedia';

        // Eliminar archivos rechazados con más de 30 días
        $archivos_viejos = $wpdb->get_results(
            "SELECT * FROM $tabla
             WHERE estado = 'rechazado'
             AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        foreach ($archivos_viejos as $archivo) {
            if ($archivo->archivo_path && file_exists($archivo->archivo_path)) {
                unlink($archivo->archivo_path);
            }
            if ($archivo->thumbnail_url) {
                $thumb_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $archivo->thumbnail_url);
                if (file_exists($thumb_path)) {
                    unlink($thumb_path);
                }
            }
            $wpdb->delete($tabla, ['id' => $archivo->id], ['%d']);
        }

        // Limpiar huérfanos de tags, likes, comentarios
        $wpdb->query("DELETE t FROM {$wpdb->prefix}flavor_multimedia_tags t
                      LEFT JOIN $tabla m ON t.archivo_id = m.id
                      WHERE m.id IS NULL");

        $wpdb->query("DELETE l FROM {$wpdb->prefix}flavor_multimedia_likes l
                      LEFT JOIN $tabla m ON l.archivo_id = m.id
                      WHERE m.id IS NULL");

        $wpdb->query("DELETE c FROM {$wpdb->prefix}flavor_multimedia_comentarios c
                      LEFT JOIN $tabla m ON c.archivo_id = m.id
                      WHERE m.id IS NULL");
    }

    // =========================================================================
    // Web Components & Tools
    // =========================================================================

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero_multimedia' => [
                'label' => __('Hero Multimedia', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-format-gallery',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Galería Comunitaria', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Momentos y recuerdos de nuestra comunidad', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_contador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'multimedia/hero',
            ],
            'galeria_grid' => [
                'label' => __('Grid de Galería', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Galería de Fotos', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [3, 4, 5, 6], 'default' => 4],
                    'limite' => ['type' => 'number', 'default' => 12],
                    'tipo' => ['type' => 'select', 'options' => ['todos', 'imagen', 'video'], 'default' => 'todos'],
                    'album_id' => ['type' => 'number', 'default' => 0],
                ],
                'template' => 'multimedia/galeria-grid',
            ],
            'albumes' => [
                'label' => __('Álbumes', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-images-alt2',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Álbumes de la Comunidad', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 9],
                ],
                'template' => 'multimedia/albumes',
            ],
            'carousel_destacado' => [
                'label' => __('Carrusel Destacado', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-images-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Momentos Destacados', 'flavor-chat-ia')],
                    'album_id' => ['type' => 'number', 'default' => 0],
                    'autoplay' => ['type' => 'toggle', 'default' => true],
                    'intervalo_segundos' => ['type' => 'number', 'default' => 5],
                ],
                'template' => 'multimedia/carousel',
            ],
            'subir_multimedia' => [
                'label' => __('Formulario Subir', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-cloud-upload',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Comparte tus fotos', 'flavor-chat-ia')],
                    'mostrar_albumes' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'multimedia/subir-form',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'multimedia_galeria',
                'description' => 'Ver galería de fotos y videos de la comunidad',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => ['type' => 'string', 'enum' => ['imagen', 'video', 'audio'], 'description' => 'Tipo de contenido'],
                        'busqueda' => ['type' => 'string', 'description' => 'Término de búsqueda'],
                        'limite' => ['type' => 'integer', 'description' => 'Cantidad de resultados'],
                    ],
                ],
            ],
            [
                'name' => 'multimedia_albumes',
                'description' => 'Ver álbumes de fotos disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'limite' => ['type' => 'integer', 'description' => 'Cantidad de álbumes'],
                    ],
                ],
            ],
            [
                'name' => 'multimedia_tags',
                'description' => 'Ver etiquetas populares de multimedia',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'limite' => ['type' => 'integer', 'description' => 'Cantidad de tags'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Galería Multimedia Comunitaria**

Espacio para compartir fotos, videos y contenido audiovisual de la comunidad.

**Características:**
- Sube fotos (JPG, PNG, GIF, WebP) y videos (MP4, MOV, WebM)
- Organiza contenido en álbumes personalizados
- Sistema de likes y comentarios
- Etiqueta ubicaciones y añade tags
- Comparte momentos del barrio y eventos
- Descarga archivos en alta calidad (si el autor lo permite)
- Galería con lightbox y navegación

**Privacidad:**
- **Público**: Visible para todos, incluso sin iniciar sesión
- **Comunidad**: Solo para usuarios registrados
- **Privado**: Solo visible para ti

**Moderación:**
- Los contenidos pueden requerir aprobación antes de publicarse
- Sistema de reportes para contenido inapropiado
- Los administradores pueden destacar contenido

**Límites:**
- Imágenes: máximo 10MB
- Videos: máximo 100MB
- Formatos soportados según configuración
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Puedo descargar las fotos?',
                'respuesta' => 'Sí, si el autor lo permite. Verás un botón de descarga en el visor.',
            ],
            [
                'pregunta' => '¿Cuánto espacio tengo para subir?',
                'respuesta' => 'Puedes subir imágenes de hasta 10MB y videos de hasta 100MB.',
            ],
            [
                'pregunta' => '¿Cómo creo un álbum?',
                'respuesta' => 'Ve a "Mi Galería", pestaña "Álbumes" y haz clic en "Crear álbum".',
            ],
            [
                'pregunta' => '¿Puedo editar mis fotos después de subirlas?',
                'respuesta' => 'Sí, puedes cambiar título, descripción, privacidad y etiquetas.',
            ],
            [
                'pregunta' => '¿Por qué mi foto no aparece?',
                'respuesta' => 'Si la moderación está activada, debe ser aprobada por un administrador.',
            ],
            [
                'pregunta' => '¿Cómo reporto contenido inapropiado?',
                'respuesta' => 'Abre la imagen y usa el botón de reportar. Selecciona el motivo.',
            ],
        ];
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
            Flavor_Page_Creator::refresh_module_pages('multimedia');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('multimedia');
        if (!$pagina && !get_option('flavor_multimedia_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['multimedia']);
            update_option('flavor_multimedia_pages_created', 1, false);
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

        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
        $tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_multimedia)) {
            return $estadisticas;
        }

        // Total de archivos públicos
        $total_archivos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_multimedia} WHERE estado = 'publico'"
        );

        $estadisticas['archivos'] = [
            'icon' => 'dashicons-format-gallery',
            'valor' => $total_archivos,
            'label' => __('Archivos', 'flavor-chat-ia'),
            'color' => 'blue',
        ];

        $usuario_id = get_current_user_id();
        if ($usuario_id) {
            // Mis archivos
            $mis_archivos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_multimedia}
                 WHERE usuario_id = %d",
                $usuario_id
            ));

            if ($mis_archivos > 0) {
                $estadisticas['mis_archivos'] = [
                    'icon' => 'dashicons-images-alt2',
                    'valor' => $mis_archivos,
                    'label' => __('Mis archivos', 'flavor-chat-ia'),
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
                'title' => __('Galería Multimedia', 'flavor-chat-ia'),
                'slug' => 'multimedia',
                'content' => '<h1>' . __('Galería Multimedia', 'flavor-chat-ia') . '</h1>
<p>' . __('Explora la galería multimedia de nuestra comunidad. Descubre fotos, videos y contenido compartido por los vecinos.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="multimedia" action="listar" columnas="4" limite="16"]',
                'parent' => 0,
            ],
            [
                'title' => __('Subir Contenido', 'flavor-chat-ia'),
                'slug' => 'subir',
                'content' => '<h1>' . __('Subir Contenido', 'flavor-chat-ia') . '</h1>
<p>' . __('Comparte tus fotos y videos con la comunidad. Sube contenido multimedia y contribuye a nuestra galería colectiva.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="multimedia" action="crear"]',
                'parent' => 'multimedia',
            ],
            [
                'title' => __('Mis Archivos', 'flavor-chat-ia'),
                'slug' => 'mis-archivos',
                'content' => '<h1>' . __('Mis Archivos', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona tu contenido multimedia. Revisa, edita y organiza las fotos y videos que has compartido.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="multimedia" action="mis_items"]',
                'parent' => 'multimedia',
            ],
        ];
    }

    /**
     * Registrar páginas de administración
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        $capability = 'manage_options';

        // Páginas ocultas (sin menú visible en el sidebar)
        add_submenu_page(
            null,
            __('Multimedia - Dashboard', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'multimedia',
            [$this, 'render_pagina_dashboard']
        );

        add_submenu_page(
            null,
            __('Multimedia - Galería', 'flavor-chat-ia'),
            __('Galería', 'flavor-chat-ia'),
            $capability,
            'multimedia-galeria',
            [$this, 'render_pagina_galeria']
        );

        add_submenu_page(
            null,
            __('Multimedia - Álbumes', 'flavor-chat-ia'),
            __('Álbumes', 'flavor-chat-ia'),
            $capability,
            'multimedia-albumes',
            [$this, 'render_pagina_albumes']
        );

        add_submenu_page(
            null,
            __('Multimedia - Categorías', 'flavor-chat-ia'),
            __('Categorías', 'flavor-chat-ia'),
            $capability,
            'multimedia-categorias',
            [$this, 'render_pagina_categorias']
        );

        add_submenu_page(
            null,
            __('Multimedia - Moderación', 'flavor-chat-ia'),
            __('Moderación', 'flavor-chat-ia'),
            $capability,
            'multimedia-moderacion',
            [$this, 'render_pagina_moderacion']
        );

        add_submenu_page(
            null,
            __('Multimedia - Configuración', 'flavor-chat-ia'),
            __('Configuración', 'flavor-chat-ia'),
            $capability,
            'flavor-multimedia-configuracion',
            [$this, 'render_pagina_configuracion']
        );

        add_submenu_page(
            null,
            __('Multimedia - Estadísticas', 'flavor-chat-ia'),
            __('Estadísticas', 'flavor-chat-ia'),
            $capability,
            'multimedia-estadisticas',
            [$this, 'render_pagina_estadisticas']
        );

        add_submenu_page(
            null,
            __('Multimedia - Subir', 'flavor-chat-ia'),
            __('Subir', 'flavor-chat-ia'),
            $capability,
            'multimedia-subir',
            [$this, 'render_pagina_subir']
        );
    }

    /**
     * Renderizar página dashboard
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard Multimedia', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de galería
     */
    public function render_pagina_galeria() {
        $views_path = dirname(__FILE__) . '/views/galeria.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Galería Multimedia', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de álbumes
     */
    public function render_pagina_albumes() {
        $views_path = dirname(__FILE__) . '/views/albumes.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Álbumes', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de categorías
     */
    public function render_pagina_categorias() {
        $views_path = dirname(__FILE__) . '/views/categorias.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Categorías', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de moderación
     */
    public function render_pagina_moderacion() {
        $views_path = dirname(__FILE__) . '/views/moderacion.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Moderación de Contenido', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de configuración
     */
    public function render_pagina_configuracion() {
        $views_path = dirname(__FILE__) . '/views/configuracion.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Configuración Multimedia', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de estadísticas
     */
    public function render_pagina_estadisticas() {
        $views_path = dirname(__FILE__) . '/views/estadisticas.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Estadísticas Multimedia', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de subida
     */
    public function render_pagina_subir() {
        $views_path = dirname(__FILE__) . '/views/subir.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Subir Multimedia', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'multimedia',
            'title'    => __('Galería Multimedia', 'flavor-chat-ia'),
            'subtitle' => __('Comparte fotos y videos con la comunidad', 'flavor-chat-ia'),
            'icon'     => '📷',
            'color'    => 'primary', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_multimedia',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'      => ['label' => __('Título', 'flavor-chat-ia'), 'type' => 'text', 'required' => true],
                'descripcion' => ['label' => __('Descripción', 'flavor-chat-ia'), 'type' => 'textarea'],
                'tipo'        => ['label' => __('Tipo', 'flavor-chat-ia'), 'type' => 'select', 'options' => ['imagen' => 'Imagen', 'video' => 'Video', 'audio' => 'Audio']],
                'archivo'     => ['label' => __('Archivo', 'flavor-chat-ia'), 'type' => 'file'],
                'album_id'    => ['label' => __('Álbum', 'flavor-chat-ia'), 'type' => 'select'],
                'privacidad'  => ['label' => __('Privacidad', 'flavor-chat-ia'), 'type' => 'select'],
                'estado'      => ['label' => __('Estado', 'flavor-chat-ia'), 'type' => 'select'],
            ],

            'estados' => [
                'publico'   => ['label' => __('Público', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🌐'],
                'pendiente' => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⏳'],
                'privado'   => ['label' => __('Privado', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '🔒'],
                'rechazado' => ['label' => __('Rechazado', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '❌'],
            ],

            'stats' => [
                'total_archivos' => ['label' => __('Total archivos', 'flavor-chat-ia'), 'icon' => '📁', 'color' => 'pink'],
                'imagenes'       => ['label' => __('Imágenes', 'flavor-chat-ia'), 'icon' => '🖼️', 'color' => 'blue'],
                'videos'         => ['label' => __('Videos', 'flavor-chat-ia'), 'icon' => '🎬', 'color' => 'purple'],
                'pendientes'     => ['label' => __('Pendientes', 'flavor-chat-ia'), 'icon' => '⏳', 'color' => 'yellow'],
            ],

            'card' => [
                'title_field'    => 'titulo',
                'subtitle_field' => 'descripcion',
                'badge_field'    => 'estado',
                'image_field'    => 'thumbnail_url',
                'meta_fields'    => ['tipo', 'fecha_creacion', 'likes'],
            ],

            'tabs' => [
                'galeria' => [
                    'label'   => __('Galería', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-format-gallery',
                    'content' => 'template:_archive.php',
                ],
                'albumes' => [
                    'label'   => __('Álbumes', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-images-alt2',
                    'content' => 'template:albumes.php',
                ],
                'mi-galeria' => [
                    'label'   => __('Mi galería', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-admin-media',
                    'content' => 'template:mi-galeria.php',
                ],
                'subir' => [
                    'label'   => __('Subir', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-cloud-upload',
                    'content' => 'template:subir.php',
                ],
            ],

            'archive' => [
                'columns'      => 4,
                'per_page'     => 16,
                'show_filters' => true,
                'show_search'  => true,
            ],

            'dashboard' => [
                'show_stats'   => true,
                'show_actions' => true,
                'actions'      => [
                    'subir_archivo' => ['label' => __('Subir archivo', 'flavor-chat-ia'), 'icon' => '📤', 'color' => 'pink'],
                    'crear_album'   => ['label' => __('Crear álbum', 'flavor-chat-ia'), 'icon' => '📁', 'color' => 'blue'],
                ],
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-multimedia-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Multimedia_Dashboard_Tab')) {
                Flavor_Multimedia_Dashboard_Tab::get_instance();
            }
        }
    }
}
