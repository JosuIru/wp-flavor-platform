<?php
/**
 * Módulo: Agregador de Contenido Comunitario
 *
 * Importa noticias de fuentes RSS externas y gestiona videos de YouTube
 * relacionados con la comunidad.
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Agregador_Contenido
 * @since 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase principal del módulo Agregador de Contenido
 */
class Flavor_Agregador_Contenido_Module {

    /**
     * ID del módulo
     */
    const MODULE_ID = 'agregador_contenido';

    /**
     * CPT para fuentes RSS
     */
    const CPT_FUENTE = 'flavor_rss_fuente';

    /**
     * CPT para noticias importadas
     */
    const CPT_NOTICIA = 'flavor_noticia';

    /**
     * CPT para videos
     */
    const CPT_VIDEO = 'flavor_video_yt';

    /**
     * Taxonomía para categorías
     */
    const TAX_CATEGORIA = 'flavor_contenido_cat';

    /**
     * Instancia singleton
     *
     * @var Flavor_Agregador_Contenido_Module|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Agregador_Contenido_Module
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Cron para importar RSS
        add_action( 'flavor_agregador_import_rss', array( $this, 'import_all_feeds' ) );

        if ( ! wp_next_scheduled( 'flavor_agregador_import_rss' ) ) {
            wp_schedule_event( time(), 'hourly', 'flavor_agregador_import_rss' );
        }

        // AJAX
        add_action( 'wp_ajax_flavor_import_single_feed', array( $this, 'ajax_import_single_feed' ) );
        add_action( 'wp_ajax_flavor_add_youtube_video', array( $this, 'ajax_add_youtube_video' ) );
        add_action( 'wp_ajax_flavor_import_youtube_playlist', array( $this, 'ajax_import_youtube_playlist' ) );

        // Meta boxes
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );

        // REST API
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /**
     * Registra los Custom Post Types
     */
    public function register_post_types() {
        // Fuentes RSS
        register_post_type(
            self::CPT_FUENTE,
            array(
                'labels'              => array(
                    'name'          => __( 'Fuentes RSS', 'flavor-chat-ia' ),
                    'singular_name' => __( 'Fuente RSS', 'flavor-chat-ia' ),
                    'add_new'       => __( 'Añadir Fuente', 'flavor-chat-ia' ),
                    'add_new_item'  => __( 'Añadir Nueva Fuente', 'flavor-chat-ia' ),
                    'edit_item'     => __( 'Editar Fuente', 'flavor-chat-ia' ),
                ),
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'supports'            => array( 'title' ),
                'capability_type'     => 'post',
            )
        );

        // Noticias importadas
        register_post_type(
            self::CPT_NOTICIA,
            array(
                'labels'              => array(
                    'name'          => __( 'Noticias Externas', 'flavor-chat-ia' ),
                    'singular_name' => __( 'Noticia Externa', 'flavor-chat-ia' ),
                    'add_new'       => __( 'Añadir Noticia', 'flavor-chat-ia' ),
                    'edit_item'     => __( 'Editar Noticia', 'flavor-chat-ia' ),
                ),
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'show_in_rest'        => true,
                'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
                'has_archive'         => true,
                'rewrite'             => array( 'slug' => 'noticias-comunidad' ),
                'menu_icon'           => 'dashicons-rss',
            )
        );

        // Videos de YouTube
        register_post_type(
            self::CPT_VIDEO,
            array(
                'labels'              => array(
                    'name'          => __( 'Videos YouTube', 'flavor-chat-ia' ),
                    'singular_name' => __( 'Video YouTube', 'flavor-chat-ia' ),
                    'add_new'       => __( 'Añadir Video', 'flavor-chat-ia' ),
                    'edit_item'     => __( 'Editar Video', 'flavor-chat-ia' ),
                ),
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'show_in_rest'        => true,
                'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
                'has_archive'         => true,
                'rewrite'             => array( 'slug' => 'videos-comunidad' ),
                'menu_icon'           => 'dashicons-video-alt3',
            )
        );
    }

    /**
     * Registra taxonomías
     */
    public function register_taxonomies() {
        register_taxonomy(
            self::TAX_CATEGORIA,
            array( self::CPT_NOTICIA, self::CPT_VIDEO ),
            array(
                'labels'            => array(
                    'name'          => __( 'Categorías de Contenido', 'flavor-chat-ia' ),
                    'singular_name' => __( 'Categoría', 'flavor-chat-ia' ),
                ),
                'hierarchical'      => true,
                'public'            => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'rewrite'           => array( 'slug' => 'contenido-categoria' ),
            )
        );
    }

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode( 'agregador_noticias', array( $this, 'shortcode_noticias' ) );
        add_shortcode( 'agregador_videos', array( $this, 'shortcode_videos' ) );
        add_shortcode( 'agregador_feed_combinado', array( $this, 'shortcode_feed_combinado' ) );
        add_shortcode( 'agregador_carrusel_videos', array( $this, 'shortcode_carrusel_videos' ) );
    }

    /**
     * Añade menús de administración
     */
    public function add_admin_menus() {
        add_submenu_page(
            'flavor-chat-ia',
            __( 'Agregador de Contenido', 'flavor-chat-ia' ),
            __( 'Agregador Contenido', 'flavor-chat-ia' ),
            'manage_options',
            'flavor-agregador',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Encola assets frontend
     */
    public function enqueue_assets() {
        if ( is_singular( array( self::CPT_NOTICIA, self::CPT_VIDEO ) ) ||
             has_shortcode( get_post()->post_content ?? '', 'agregador_' ) ) {
            wp_enqueue_style(
                'flavor-agregador',
                FLAVOR_CHAT_IA_URL . 'includes/modules/agregador-contenido/assets/css/agregador.css',
                array(),
                FLAVOR_CHAT_IA_VERSION
            );
            wp_enqueue_script(
                'flavor-agregador',
                FLAVOR_CHAT_IA_URL . 'includes/modules/agregador-contenido/assets/js/agregador.js',
                array( 'jquery' ),
                FLAVOR_CHAT_IA_VERSION,
                true
            );
        }
    }

    /**
     * Añade meta boxes
     */
    public function add_meta_boxes() {
        // Meta box para fuente RSS
        add_meta_box(
            'flavor_rss_config',
            __( 'Configuración de Fuente', 'flavor-chat-ia' ),
            array( $this, 'render_metabox_fuente' ),
            self::CPT_FUENTE,
            'normal',
            'high'
        );

        // Meta box para video YouTube
        add_meta_box(
            'flavor_video_config',
            __( 'Datos del Video', 'flavor-chat-ia' ),
            array( $this, 'render_metabox_video' ),
            self::CPT_VIDEO,
            'normal',
            'high'
        );

        // Meta box para noticia
        add_meta_box(
            'flavor_noticia_source',
            __( 'Fuente Original', 'flavor-chat-ia' ),
            array( $this, 'render_metabox_noticia' ),
            self::CPT_NOTICIA,
            'side',
            'default'
        );
    }

    /**
     * Renderiza metabox de fuente RSS
     *
     * @param WP_Post $post Post object.
     */
    public function render_metabox_fuente( $post ) {
        wp_nonce_field( 'flavor_rss_fuente', 'flavor_rss_nonce' );

        $feed_url    = get_post_meta( $post->ID, '_feed_url', true );
        $max_items   = get_post_meta( $post->ID, '_max_items', true ) ?: 10;
        $keywords    = get_post_meta( $post->ID, '_keywords', true );
        $auto_import = get_post_meta( $post->ID, '_auto_import', true );
        $last_import = get_post_meta( $post->ID, '_last_import', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="feed_url"><?php esc_html_e( 'URL del Feed RSS', 'flavor-chat-ia' ); ?></label></th>
                <td>
                    <input type="url" id="feed_url" name="feed_url" value="<?php echo esc_url( $feed_url ); ?>" class="large-text" required>
                    <p class="description"><?php esc_html_e( 'URL completa del feed RSS (ej: https://ejemplo.com/feed)', 'flavor-chat-ia' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="max_items"><?php esc_html_e( 'Máximo de artículos', 'flavor-chat-ia' ); ?></label></th>
                <td>
                    <input type="number" id="max_items" name="max_items" value="<?php echo esc_attr( $max_items ); ?>" min="1" max="50">
                    <p class="description"><?php esc_html_e( 'Número máximo de artículos a importar por vez', 'flavor-chat-ia' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="keywords"><?php esc_html_e( 'Palabras clave (filtro)', 'flavor-chat-ia' ); ?></label></th>
                <td>
                    <input type="text" id="keywords" name="keywords" value="<?php echo esc_attr( $keywords ); ?>" class="large-text">
                    <p class="description"><?php esc_html_e( 'Solo importar artículos que contengan estas palabras (separadas por coma). Dejar vacío para importar todo.', 'flavor-chat-ia' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="auto_import"><?php esc_html_e( 'Importación automática', 'flavor-chat-ia' ); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="auto_import" name="auto_import" value="1" <?php checked( $auto_import, '1' ); ?>>
                        <?php esc_html_e( 'Importar automáticamente cada hora', 'flavor-chat-ia' ); ?>
                    </label>
                </td>
            </tr>
            <?php if ( $last_import ) : ?>
            <tr>
                <th><?php esc_html_e( 'Última importación', 'flavor-chat-ia' ); ?></th>
                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_import ) ) ); ?></td>
            </tr>
            <?php endif; ?>
        </table>
        <p>
            <button type="button" class="button button-secondary" id="test-feed-btn">
                <?php esc_html_e( 'Probar Feed', 'flavor-chat-ia' ); ?>
            </button>
            <button type="button" class="button button-primary" id="import-now-btn">
                <?php esc_html_e( 'Importar Ahora', 'flavor-chat-ia' ); ?>
            </button>
        </p>
        <div id="feed-test-result"></div>
        <?php
    }

    /**
     * Renderiza metabox de video YouTube
     *
     * @param WP_Post $post Post object.
     */
    public function render_metabox_video( $post ) {
        wp_nonce_field( 'flavor_video_yt', 'flavor_video_nonce' );

        $video_url     = get_post_meta( $post->ID, '_video_url', true );
        $video_id      = get_post_meta( $post->ID, '_video_id', true );
        $channel_name  = get_post_meta( $post->ID, '_channel_name', true );
        $channel_url   = get_post_meta( $post->ID, '_channel_url', true );
        $duration      = get_post_meta( $post->ID, '_duration', true );
        $published_at  = get_post_meta( $post->ID, '_published_at', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="video_url"><?php esc_html_e( 'URL del Video', 'flavor-chat-ia' ); ?></label></th>
                <td>
                    <input type="url" id="video_url" name="video_url" value="<?php echo esc_url( $video_url ); ?>" class="large-text">
                    <p class="description"><?php esc_html_e( 'URL de YouTube (ej: https://youtube.com/watch?v=xxxxx)', 'flavor-chat-ia' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="channel_name"><?php esc_html_e( 'Canal', 'flavor-chat-ia' ); ?></label></th>
                <td>
                    <input type="text" id="channel_name" name="channel_name" value="<?php echo esc_attr( $channel_name ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="duration"><?php esc_html_e( 'Duración', 'flavor-chat-ia' ); ?></label></th>
                <td>
                    <input type="text" id="duration" name="duration" value="<?php echo esc_attr( $duration ); ?>" class="small-text" placeholder="10:30">
                </td>
            </tr>
        </table>
        <?php if ( $video_id ) : ?>
        <div class="video-preview">
            <iframe width="400" height="225" src="https://www.youtube.com/embed/<?php echo esc_attr( $video_id ); ?>" frameborder="0" allowfullscreen></iframe>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Renderiza metabox de noticia
     *
     * @param WP_Post $post Post object.
     */
    public function render_metabox_noticia( $post ) {
        $source_url   = get_post_meta( $post->ID, '_source_url', true );
        $source_name  = get_post_meta( $post->ID, '_source_name', true );
        $import_date  = get_post_meta( $post->ID, '_import_date', true );
        ?>
        <p>
            <strong><?php esc_html_e( 'Fuente:', 'flavor-chat-ia' ); ?></strong><br>
            <?php echo esc_html( $source_name ); ?>
        </p>
        <?php if ( $source_url ) : ?>
        <p>
            <a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener">
                <?php esc_html_e( 'Ver artículo original', 'flavor-chat-ia' ); ?> →
            </a>
        </p>
        <?php endif; ?>
        <?php if ( $import_date ) : ?>
        <p>
            <strong><?php esc_html_e( 'Importado:', 'flavor-chat-ia' ); ?></strong><br>
            <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $import_date ) ) ); ?>
        </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Guarda meta boxes
     *
     * @param int $post_id Post ID.
     */
    public function save_meta_boxes( $post_id ) {
        // Fuente RSS
        if ( isset( $_POST['flavor_rss_nonce'] ) && wp_verify_nonce( $_POST['flavor_rss_nonce'], 'flavor_rss_fuente' ) ) {
            update_post_meta( $post_id, '_feed_url', esc_url_raw( $_POST['feed_url'] ?? '' ) );
            update_post_meta( $post_id, '_max_items', absint( $_POST['max_items'] ?? 10 ) );
            update_post_meta( $post_id, '_keywords', sanitize_text_field( $_POST['keywords'] ?? '' ) );
            update_post_meta( $post_id, '_auto_import', isset( $_POST['auto_import'] ) ? '1' : '0' );
        }

        // Video YouTube
        if ( isset( $_POST['flavor_video_nonce'] ) && wp_verify_nonce( $_POST['flavor_video_nonce'], 'flavor_video_yt' ) ) {
            $video_url = esc_url_raw( $_POST['video_url'] ?? '' );
            update_post_meta( $post_id, '_video_url', $video_url );

            // Extraer video ID
            $video_id = $this->extract_youtube_id( $video_url );
            if ( $video_id ) {
                update_post_meta( $post_id, '_video_id', $video_id );
            }

            update_post_meta( $post_id, '_channel_name', sanitize_text_field( $_POST['channel_name'] ?? '' ) );
            update_post_meta( $post_id, '_duration', sanitize_text_field( $_POST['duration'] ?? '' ) );
        }
    }

    /**
     * Extrae el ID de video de una URL de YouTube
     *
     * @param string $url URL de YouTube.
     * @return string|false
     */
    public function extract_youtube_id( $url ) {
        $patterns = array(
            '/youtube\.com\/watch\?v=([^&]+)/',
            '/youtu\.be\/([^?]+)/',
            '/youtube\.com\/embed\/([^?]+)/',
            '/youtube\.com\/v\/([^?]+)/',
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $url, $matches ) ) {
                return $matches[1];
            }
        }

        return false;
    }

    /**
     * Importa un feed RSS
     *
     * @param int $fuente_id ID del post de fuente.
     * @return array Resultado de la importación.
     */
    public function import_feed( $fuente_id ) {
        $feed_url  = get_post_meta( $fuente_id, '_feed_url', true );
        $max_items = get_post_meta( $fuente_id, '_max_items', true ) ?: 10;
        $keywords  = get_post_meta( $fuente_id, '_keywords', true );
        $fuente    = get_post( $fuente_id );

        if ( ! $feed_url ) {
            return array( 'error' => __( 'URL de feed no configurada', 'flavor-chat-ia' ) );
        }

        include_once ABSPATH . WPINC . '/feed.php';

        $rss = fetch_feed( $feed_url );

        if ( is_wp_error( $rss ) ) {
            return array( 'error' => $rss->get_error_message() );
        }

        $items    = $rss->get_items( 0, $max_items );
        $imported = 0;
        $skipped  = 0;
        $keywords_array = $keywords ? array_map( 'trim', explode( ',', strtolower( $keywords ) ) ) : array();

        foreach ( $items as $item ) {
            $title   = $item->get_title();
            $content = $item->get_content();
            $link    = $item->get_permalink();
            $date    = $item->get_date( 'Y-m-d H:i:s' );

            // Filtrar por palabras clave
            if ( ! empty( $keywords_array ) ) {
                $text_to_check = strtolower( $title . ' ' . $content );
                $match = false;
                foreach ( $keywords_array as $keyword ) {
                    if ( strpos( $text_to_check, $keyword ) !== false ) {
                        $match = true;
                        break;
                    }
                }
                if ( ! $match ) {
                    $skipped++;
                    continue;
                }
            }

            // Verificar si ya existe
            $existing = get_posts(
                array(
                    'post_type'  => self::CPT_NOTICIA,
                    'meta_key'   => '_source_url',
                    'meta_value' => $link,
                    'posts_per_page' => 1,
                )
            );

            if ( ! empty( $existing ) ) {
                $skipped++;
                continue;
            }

            // Crear noticia
            $post_id = wp_insert_post(
                array(
                    'post_type'    => self::CPT_NOTICIA,
                    'post_title'   => wp_strip_all_tags( $title ),
                    'post_content' => wp_kses_post( $content ),
                    'post_excerpt' => wp_trim_words( wp_strip_all_tags( $content ), 30 ),
                    'post_status'  => 'publish',
                    'post_date'    => $date,
                )
            );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_source_url', $link );
                update_post_meta( $post_id, '_source_name', $fuente->post_title );
                update_post_meta( $post_id, '_source_id', $fuente_id );
                update_post_meta( $post_id, '_import_date', current_time( 'mysql' ) );

                // Intentar obtener imagen
                $enclosure = $item->get_enclosure();
                if ( $enclosure && $enclosure->get_type() && strpos( $enclosure->get_type(), 'image' ) !== false ) {
                    $this->set_featured_image_from_url( $post_id, $enclosure->get_link() );
                }

                $imported++;
            }
        }

        update_post_meta( $fuente_id, '_last_import', current_time( 'mysql' ) );

        return array(
            'imported' => $imported,
            'skipped'  => $skipped,
            'total'    => count( $items ),
        );
    }

    /**
     * Importa todos los feeds con auto_import activo
     */
    public function import_all_feeds() {
        $fuentes = get_posts(
            array(
                'post_type'      => self::CPT_FUENTE,
                'posts_per_page' => -1,
                'meta_key'       => '_auto_import',
                'meta_value'     => '1',
            )
        );

        $results = array();
        foreach ( $fuentes as $fuente ) {
            $results[ $fuente->ID ] = $this->import_feed( $fuente->ID );
        }

        return $results;
    }

    /**
     * Establece imagen destacada desde URL
     *
     * @param int    $post_id Post ID.
     * @param string $image_url URL de la imagen.
     */
    private function set_featured_image_from_url( $post_id, $image_url ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_sideload_image( $image_url, $post_id, '', 'id' );

        if ( ! is_wp_error( $attachment_id ) ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }
    }

    /**
     * Shortcode: Noticias
     *
     * @param array $atts Atributos.
     * @return string
     */
    public function shortcode_noticias( $atts ) {
        $atts = shortcode_atts(
            array(
                'limite'    => 6,
                'columnas'  => 3,
                'categoria' => '',
                'fuente'    => '',
                'mostrar_fuente' => 'true',
                'mostrar_fecha'  => 'true',
                'mostrar_extracto' => 'true',
            ),
            $atts
        );

        $args = array(
            'post_type'      => self::CPT_NOTICIA,
            'posts_per_page' => absint( $atts['limite'] ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( $atts['categoria'] ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => self::TAX_CATEGORIA,
                    'field'    => 'slug',
                    'terms'    => explode( ',', $atts['categoria'] ),
                ),
            );
        }

        if ( $atts['fuente'] ) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_source_id',
                    'value' => absint( $atts['fuente'] ),
                ),
            );
        }

        $noticias = new WP_Query( $args );

        ob_start();
        ?>
        <div class="flavor-agregador-noticias" data-columnas="<?php echo esc_attr( $atts['columnas'] ); ?>">
            <?php if ( $noticias->have_posts() ) : ?>
                <div class="agregador-grid">
                    <?php while ( $noticias->have_posts() ) : $noticias->the_post(); ?>
                        <article class="noticia-card">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="noticia-imagen">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'medium' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="noticia-content">
                                <h3 class="noticia-titulo">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <?php if ( $atts['mostrar_extracto'] === 'true' ) : ?>
                                    <p class="noticia-extracto"><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></p>
                                <?php endif; ?>
                                <div class="noticia-meta">
                                    <?php if ( $atts['mostrar_fecha'] === 'true' ) : ?>
                                        <span class="noticia-fecha"><?php echo get_the_date(); ?></span>
                                    <?php endif; ?>
                                    <?php if ( $atts['mostrar_fuente'] === 'true' ) : ?>
                                        <?php $source_name = get_post_meta( get_the_ID(), '_source_name', true ); ?>
                                        <?php if ( $source_name ) : ?>
                                            <span class="noticia-fuente"><?php echo esc_html( $source_name ); ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <p class="no-noticias"><?php esc_html_e( 'No hay noticias disponibles.', 'flavor-chat-ia' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Shortcode: Videos
     *
     * @param array $atts Atributos.
     * @return string
     */
    public function shortcode_videos( $atts ) {
        $atts = shortcode_atts(
            array(
                'limite'    => 6,
                'columnas'  => 3,
                'categoria' => '',
                'canal'     => '',
                'layout'    => 'grid', // grid, list
            ),
            $atts
        );

        $args = array(
            'post_type'      => self::CPT_VIDEO,
            'posts_per_page' => absint( $atts['limite'] ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( $atts['categoria'] ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => self::TAX_CATEGORIA,
                    'field'    => 'slug',
                    'terms'    => explode( ',', $atts['categoria'] ),
                ),
            );
        }

        if ( $atts['canal'] ) {
            $args['meta_query'] = array(
                array(
                    'key'     => '_channel_name',
                    'value'   => $atts['canal'],
                    'compare' => 'LIKE',
                ),
            );
        }

        $videos = new WP_Query( $args );

        ob_start();
        ?>
        <div class="flavor-agregador-videos layout-<?php echo esc_attr( $atts['layout'] ); ?>" data-columnas="<?php echo esc_attr( $atts['columnas'] ); ?>">
            <?php if ( $videos->have_posts() ) : ?>
                <div class="agregador-grid">
                    <?php while ( $videos->have_posts() ) : $videos->the_post();
                        $video_id = get_post_meta( get_the_ID(), '_video_id', true );
                        $channel_name = get_post_meta( get_the_ID(), '_channel_name', true );
                        $duration = get_post_meta( get_the_ID(), '_duration', true );
                    ?>
                        <article class="video-card">
                            <div class="video-thumbnail" data-video-id="<?php echo esc_attr( $video_id ); ?>">
                                <img src="https://img.youtube.com/vi/<?php echo esc_attr( $video_id ); ?>/maxresdefault.jpg"
                                     alt="<?php the_title_attribute(); ?>"
                                     loading="lazy">
                                <div class="video-play-btn">
                                    <svg viewBox="0 0 68 48"><path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path><path d="M 45,24 27,14 27,34" fill="#fff"></path></svg>
                                </div>
                                <?php if ( $duration ) : ?>
                                    <span class="video-duration"><?php echo esc_html( $duration ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="video-content">
                                <h3 class="video-titulo">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <?php if ( $channel_name ) : ?>
                                    <p class="video-canal"><?php echo esc_html( $channel_name ); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <p class="no-videos"><?php esc_html_e( 'No hay videos disponibles.', 'flavor-chat-ia' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Shortcode: Feed combinado
     *
     * @param array $atts Atributos.
     * @return string
     */
    public function shortcode_feed_combinado( $atts ) {
        $atts = shortcode_atts(
            array(
                'limite'    => 12,
                'columnas'  => 4,
                'categoria' => '',
            ),
            $atts
        );

        $args = array(
            'post_type'      => array( self::CPT_NOTICIA, self::CPT_VIDEO ),
            'posts_per_page' => absint( $atts['limite'] ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( $atts['categoria'] ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => self::TAX_CATEGORIA,
                    'field'    => 'slug',
                    'terms'    => explode( ',', $atts['categoria'] ),
                ),
            );
        }

        $contenido = new WP_Query( $args );

        ob_start();
        ?>
        <div class="flavor-agregador-combinado" data-columnas="<?php echo esc_attr( $atts['columnas'] ); ?>">
            <?php if ( $contenido->have_posts() ) : ?>
                <div class="agregador-grid">
                    <?php while ( $contenido->have_posts() ) : $contenido->the_post();
                        $post_type = get_post_type();
                        $is_video = $post_type === self::CPT_VIDEO;
                    ?>
                        <article class="contenido-card <?php echo $is_video ? 'tipo-video' : 'tipo-noticia'; ?>">
                            <?php if ( $is_video ) :
                                $video_id = get_post_meta( get_the_ID(), '_video_id', true );
                            ?>
                                <div class="contenido-media video-thumbnail" data-video-id="<?php echo esc_attr( $video_id ); ?>">
                                    <img src="https://img.youtube.com/vi/<?php echo esc_attr( $video_id ); ?>/hqdefault.jpg"
                                         alt="<?php the_title_attribute(); ?>" loading="lazy">
                                    <span class="tipo-badge video"><?php esc_html_e( 'Video', 'flavor-chat-ia' ); ?></span>
                                    <div class="video-play-btn">
                                        <svg viewBox="0 0 68 48"><path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path><path d="M 45,24 27,14 27,34" fill="#fff"></path></svg>
                                    </div>
                                </div>
                            <?php elseif ( has_post_thumbnail() ) : ?>
                                <div class="contenido-media">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'medium' ); ?>
                                    </a>
                                    <span class="tipo-badge noticia"><?php esc_html_e( 'Noticia', 'flavor-chat-ia' ); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="contenido-body">
                                <h3 class="contenido-titulo">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <span class="contenido-fecha"><?php echo get_the_date(); ?></span>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Shortcode: Carrusel de videos
     *
     * @param array $atts Atributos.
     * @return string
     */
    public function shortcode_carrusel_videos( $atts ) {
        $atts = shortcode_atts(
            array(
                'limite'    => 8,
                'categoria' => '',
                'autoplay'  => 'false',
            ),
            $atts
        );

        $args = array(
            'post_type'      => self::CPT_VIDEO,
            'posts_per_page' => absint( $atts['limite'] ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( $atts['categoria'] ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => self::TAX_CATEGORIA,
                    'field'    => 'slug',
                    'terms'    => explode( ',', $atts['categoria'] ),
                ),
            );
        }

        $videos = new WP_Query( $args );

        ob_start();
        ?>
        <div class="flavor-agregador-carrusel" data-autoplay="<?php echo esc_attr( $atts['autoplay'] ); ?>">
            <button class="carrusel-nav carrusel-prev" aria-label="Anterior">‹</button>
            <div class="carrusel-container">
                <div class="carrusel-track">
                    <?php while ( $videos->have_posts() ) : $videos->the_post();
                        $video_id = get_post_meta( get_the_ID(), '_video_id', true );
                    ?>
                        <div class="carrusel-slide">
                            <div class="video-thumbnail" data-video-id="<?php echo esc_attr( $video_id ); ?>">
                                <img src="https://img.youtube.com/vi/<?php echo esc_attr( $video_id ); ?>/hqdefault.jpg"
                                     alt="<?php the_title_attribute(); ?>" loading="lazy">
                                <div class="video-play-btn">
                                    <svg viewBox="0 0 68 48"><path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path><path d="M 45,24 27,14 27,34" fill="#fff"></path></svg>
                                </div>
                            </div>
                            <h4 class="video-titulo"><?php the_title(); ?></h4>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <button class="carrusel-nav carrusel-next" aria-label="Siguiente">›</button>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * AJAX: Importar feed individual
     */
    public function ajax_import_single_feed() {
        check_ajax_referer( 'flavor_agregador', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permisos insuficientes', 'flavor-chat-ia' ) );
        }

        $fuente_id = absint( $_POST['fuente_id'] ?? 0 );

        if ( ! $fuente_id ) {
            wp_send_json_error( __( 'ID de fuente no válido', 'flavor-chat-ia' ) );
        }

        $result = $this->import_feed( $fuente_id );

        if ( isset( $result['error'] ) ) {
            wp_send_json_error( $result['error'] );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX: Añadir video de YouTube
     */
    public function ajax_add_youtube_video() {
        check_ajax_referer( 'flavor_agregador', 'nonce' );

        if ( ! current_user_can( 'publish_posts' ) ) {
            wp_send_json_error( __( 'Permisos insuficientes', 'flavor-chat-ia' ) );
        }

        $video_url = esc_url_raw( $_POST['video_url'] ?? '' );
        $video_id  = $this->extract_youtube_id( $video_url );

        if ( ! $video_id ) {
            wp_send_json_error( __( 'URL de YouTube no válida', 'flavor-chat-ia' ) );
        }

        // Verificar si ya existe
        $existing = get_posts(
            array(
                'post_type'  => self::CPT_VIDEO,
                'meta_key'   => '_video_id',
                'meta_value' => $video_id,
                'posts_per_page' => 1,
            )
        );

        if ( ! empty( $existing ) ) {
            wp_send_json_error( __( 'Este video ya existe', 'flavor-chat-ia' ) );
        }

        // Obtener info del video via oEmbed
        $oembed_url = 'https://www.youtube.com/oembed?url=' . urlencode( $video_url ) . '&format=json';
        $response   = wp_remote_get( $oembed_url );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( __( 'No se pudo obtener información del video', 'flavor-chat-ia' ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        // Crear video
        $post_id = wp_insert_post(
            array(
                'post_type'   => self::CPT_VIDEO,
                'post_title'  => sanitize_text_field( $data['title'] ?? 'Video YouTube' ),
                'post_status' => 'publish',
            )
        );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( $post_id->get_error_message() );
        }

        update_post_meta( $post_id, '_video_url', $video_url );
        update_post_meta( $post_id, '_video_id', $video_id );
        update_post_meta( $post_id, '_channel_name', $data['author_name'] ?? '' );
        update_post_meta( $post_id, '_thumbnail_url', $data['thumbnail_url'] ?? '' );

        // Guardar thumbnail como imagen destacada
        if ( ! empty( $data['thumbnail_url'] ) ) {
            $this->set_featured_image_from_url( $post_id, $data['thumbnail_url'] );
        }

        wp_send_json_success(
            array(
                'post_id' => $post_id,
                'title'   => $data['title'] ?? '',
                'edit_url' => get_edit_post_link( $post_id, 'raw' ),
            )
        );
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor-agregador/v1';

        // Obtener noticias
        register_rest_route(
            $namespace,
            '/noticias',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_get_noticias' ),
                'permission_callback' => '__return_true',
            )
        );

        // Obtener videos
        register_rest_route(
            $namespace,
            '/videos',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_get_videos' ),
                'permission_callback' => '__return_true',
            )
        );

        // Añadir video
        register_rest_route(
            $namespace,
            '/videos',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_add_video' ),
                'permission_callback' => function() {
                    return current_user_can( 'publish_posts' );
                },
            )
        );

        // Importar feed
        register_rest_route(
            $namespace,
            '/feeds/(?P<id>\d+)/import',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_import_feed' ),
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
            )
        );
    }

    /**
     * REST: Obtener noticias
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_get_noticias( $request ) {
        $args = array(
            'post_type'      => self::CPT_NOTICIA,
            'posts_per_page' => $request->get_param( 'per_page' ) ?: 10,
            'paged'          => $request->get_param( 'page' ) ?: 1,
        );

        if ( $request->get_param( 'categoria' ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => self::TAX_CATEGORIA,
                    'field'    => 'slug',
                    'terms'    => $request->get_param( 'categoria' ),
                ),
            );
        }

        $query = new WP_Query( $args );
        $items = array();

        foreach ( $query->posts as $post ) {
            $items[] = array(
                'id'           => $post->ID,
                'title'        => $post->post_title,
                'excerpt'      => get_the_excerpt( $post ),
                'date'         => $post->post_date,
                'link'         => get_permalink( $post ),
                'source_url'   => get_post_meta( $post->ID, '_source_url', true ),
                'source_name'  => get_post_meta( $post->ID, '_source_name', true ),
                'thumbnail'    => get_the_post_thumbnail_url( $post, 'medium' ),
            );
        }

        return new WP_REST_Response(
            array(
                'items'       => $items,
                'total'       => $query->found_posts,
                'total_pages' => $query->max_num_pages,
            ),
            200
        );
    }

    /**
     * REST: Obtener videos
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_get_videos( $request ) {
        $args = array(
            'post_type'      => self::CPT_VIDEO,
            'posts_per_page' => $request->get_param( 'per_page' ) ?: 10,
            'paged'          => $request->get_param( 'page' ) ?: 1,
        );

        if ( $request->get_param( 'categoria' ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => self::TAX_CATEGORIA,
                    'field'    => 'slug',
                    'terms'    => $request->get_param( 'categoria' ),
                ),
            );
        }

        $query = new WP_Query( $args );
        $items = array();

        foreach ( $query->posts as $post ) {
            $video_id = get_post_meta( $post->ID, '_video_id', true );
            $items[] = array(
                'id'           => $post->ID,
                'title'        => $post->post_title,
                'date'         => $post->post_date,
                'link'         => get_permalink( $post ),
                'video_id'     => $video_id,
                'video_url'    => get_post_meta( $post->ID, '_video_url', true ),
                'embed_url'    => 'https://www.youtube.com/embed/' . $video_id,
                'thumbnail'    => 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg',
                'channel_name' => get_post_meta( $post->ID, '_channel_name', true ),
                'duration'     => get_post_meta( $post->ID, '_duration', true ),
            );
        }

        return new WP_REST_Response(
            array(
                'items'       => $items,
                'total'       => $query->found_posts,
                'total_pages' => $query->max_num_pages,
            ),
            200
        );
    }

    /**
     * REST: Añadir video
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_add_video( $request ) {
        $video_url = $request->get_param( 'video_url' );
        $video_id  = $this->extract_youtube_id( $video_url );

        if ( ! $video_id ) {
            return new WP_REST_Response( array( 'error' => 'URL de YouTube no válida' ), 400 );
        }

        // Obtener info
        $oembed_url = 'https://www.youtube.com/oembed?url=' . urlencode( $video_url ) . '&format=json';
        $response   = wp_remote_get( $oembed_url );
        $data       = json_decode( wp_remote_retrieve_body( $response ), true );

        $post_id = wp_insert_post(
            array(
                'post_type'   => self::CPT_VIDEO,
                'post_title'  => $request->get_param( 'title' ) ?: ( $data['title'] ?? 'Video YouTube' ),
                'post_status' => 'publish',
            )
        );

        if ( is_wp_error( $post_id ) ) {
            return new WP_REST_Response( array( 'error' => $post_id->get_error_message() ), 500 );
        }

        update_post_meta( $post_id, '_video_url', $video_url );
        update_post_meta( $post_id, '_video_id', $video_id );
        update_post_meta( $post_id, '_channel_name', $data['author_name'] ?? '' );

        // Asignar categorías si se proporcionan
        if ( $request->get_param( 'categorias' ) ) {
            wp_set_object_terms( $post_id, $request->get_param( 'categorias' ), self::TAX_CATEGORIA );
        }

        return new WP_REST_Response(
            array(
                'id'       => $post_id,
                'title'    => get_the_title( $post_id ),
                'video_id' => $video_id,
            ),
            201
        );
    }

    /**
     * REST: Importar feed
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function rest_import_feed( $request ) {
        $fuente_id = $request->get_param( 'id' );
        $result    = $this->import_feed( $fuente_id );

        if ( isset( $result['error'] ) ) {
            return new WP_REST_Response( array( 'error' => $result['error'] ), 400 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Renderiza página de administración
     */
    public function render_admin_page() {
        include __DIR__ . '/views/dashboard.php';
    }

    /**
     * Obtiene configuración del módulo
     *
     * @return array
     */
    public static function get_module_config() {
        return array(
            'id'          => self::MODULE_ID,
            'name'        => __( 'Agregador de Contenido', 'flavor-chat-ia' ),
            'description' => __( 'Importa noticias RSS y gestiona videos de YouTube relacionados con la comunidad', 'flavor-chat-ia' ),
            'icon'        => 'dashicons-rss',
            'category'    => 'comunicacion',
            'version'     => '1.0.0',
            'shortcodes'  => array(
                'agregador_noticias'       => __( 'Grid de noticias externas', 'flavor-chat-ia' ),
                'agregador_videos'         => __( 'Grid de videos YouTube', 'flavor-chat-ia' ),
                'agregador_feed_combinado' => __( 'Feed combinado noticias + videos', 'flavor-chat-ia' ),
                'agregador_carrusel_videos' => __( 'Carrusel de videos', 'flavor-chat-ia' ),
            ),
            'cpts'        => array( self::CPT_NOTICIA, self::CPT_VIDEO, self::CPT_FUENTE ),
            'taxonomies'  => array( self::TAX_CATEGORIA ),
        );
    }
}

// Inicializar
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'Flavor_Module_Loader' ) ) {
        Flavor_Agregador_Contenido_Module::get_instance();
    }
}, 20 );
