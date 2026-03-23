<?php
/**
 * REST API para gestión de Media (imágenes, iconos, placeholders)
 *
 * Permite a Claude Code acceder y gestionar recursos visuales
 * para crear páginas de alta calidad.
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API REST de Media
 */
class Flavor_Media_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_Media_API|null
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-vbp/v1';

    /**
     * Clave de API
     *
     * @var string
     */
    private $api_key = '';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Media_API
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
        $settings = get_option( 'flavor_chat_ia_settings', array() );
        $this->api_key = $settings['vbp_api_key'] ?? 'flavor-vbp-2024';

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Verifica permisos de API
     *
     * @param WP_REST_Request $request Petición REST.
     * @return bool
     */
    public function check_permission( $request ) {
        $auth_header = $request->get_header( 'X-VBP-Key' );
        if ( $auth_header === $this->api_key ) {
            return true;
        }

        $key_param = $request->get_param( 'api_key' );
        if ( $key_param === $this->api_key ) {
            return true;
        }

        return current_user_can( 'upload_files' );
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // Obtener biblioteca de medios
        register_rest_route( self::NAMESPACE, '/media/library', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_media_library' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'type' => array(
                    'type'    => 'string',
                    'default' => 'image',
                    'enum'    => array( 'image', 'video', 'audio', 'all' ),
                ),
                'search' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'per_page' => array(
                    'type'    => 'integer',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 100,
                ),
                'page' => array(
                    'type'    => 'integer',
                    'default' => 1,
                ),
            ),
        ) );

        // Subir imagen desde URL
        register_rest_route( self::NAMESPACE, '/media/upload-url', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'upload_from_url' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'url' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'title' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'alt' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
        ) );

        // Obtener iconos disponibles
        register_rest_route( self::NAMESPACE, '/media/icons', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_available_icons' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'search' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'category' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
        ) );

        // Obtener placeholders de alta calidad
        register_rest_route( self::NAMESPACE, '/media/placeholders', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_placeholders' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'category' => array(
                    'type'    => 'string',
                    'default' => '',
                    'enum'    => array( '', 'nature', 'business', 'technology', 'people', 'food', 'architecture', 'abstract', 'animals' ),
                ),
                'width' => array(
                    'type'    => 'integer',
                    'default' => 1200,
                ),
                'height' => array(
                    'type'    => 'integer',
                    'default' => 800,
                ),
                'count' => array(
                    'type'    => 'integer',
                    'default' => 10,
                    'maximum' => 30,
                ),
            ),
        ) );

        // Buscar imágenes de stock (Unsplash, Pexels)
        register_rest_route( self::NAMESPACE, '/media/search-stock', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'search_stock_images' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'query' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
                'per_page' => array(
                    'type'    => 'integer',
                    'default' => 10,
                ),
            ),
        ) );

        // Obtener fuentes disponibles
        register_rest_route( self::NAMESPACE, '/media/fonts', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_available_fonts' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Obtener gradientes predefinidos
        register_rest_route( self::NAMESPACE, '/media/gradients', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_gradients' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Obtener paletas de colores
        register_rest_route( self::NAMESPACE, '/media/color-palettes', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_color_palettes' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Generar imagen placeholder con texto
        register_rest_route( self::NAMESPACE, '/media/generate-placeholder', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'generate_placeholder' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'width' => array(
                    'type'    => 'integer',
                    'default' => 800,
                ),
                'height' => array(
                    'type'    => 'integer',
                    'default' => 600,
                ),
                'text' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'bg_color' => array(
                    'type'    => 'string',
                    'default' => '6366f1',
                ),
                'text_color' => array(
                    'type'    => 'string',
                    'default' => 'ffffff',
                ),
            ),
        ) );
    }

    /**
     * Obtiene la biblioteca de medios
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_media_library( $request ) {
        $type = $request->get_param( 'type' );
        $search = $request->get_param( 'search' );
        $per_page = $request->get_param( 'per_page' );
        $page = $request->get_param( 'page' );

        $args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( 'all' !== $type ) {
            $mime_types = array(
                'image' => array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml' ),
                'video' => array( 'video/mp4', 'video/webm', 'video/ogg' ),
                'audio' => array( 'audio/mpeg', 'audio/ogg', 'audio/wav' ),
            );
            $args['post_mime_type'] = $mime_types[ $type ] ?? $mime_types['image'];
        }

        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        $query = new WP_Query( $args );
        $items = array();

        foreach ( $query->posts as $attachment ) {
            $metadata = wp_get_attachment_metadata( $attachment->ID );
            $items[] = array(
                'id'        => $attachment->ID,
                'title'     => $attachment->post_title,
                'alt'       => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
                'caption'   => $attachment->post_excerpt,
                'url'       => wp_get_attachment_url( $attachment->ID ),
                'thumbnail' => wp_get_attachment_image_url( $attachment->ID, 'thumbnail' ),
                'medium'    => wp_get_attachment_image_url( $attachment->ID, 'medium' ),
                'large'     => wp_get_attachment_image_url( $attachment->ID, 'large' ),
                'full'      => wp_get_attachment_image_url( $attachment->ID, 'full' ),
                'width'     => $metadata['width'] ?? null,
                'height'    => $metadata['height'] ?? null,
                'mime_type' => $attachment->post_mime_type,
                'date'      => $attachment->post_date,
            );
        }

        return new WP_REST_Response( array(
            'items'       => $items,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $page,
            'per_page'    => $per_page,
        ), 200 );
    }

    /**
     * Sube una imagen desde URL
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function upload_from_url( $request ) {
        $url = $request->get_param( 'url' );
        $title = $request->get_param( 'title' );
        $alt = $request->get_param( 'alt' );

        // Validar URL
        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return new WP_REST_Response( array( 'error' => 'URL inválida' ), 400 );
        }

        // Descargar archivo
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url( $url );

        if ( is_wp_error( $tmp ) ) {
            return new WP_REST_Response( array(
                'error'   => 'Error descargando imagen',
                'message' => $tmp->get_error_message(),
            ), 500 );
        }

        // Determinar nombre de archivo
        $file_name = basename( wp_parse_url( $url, PHP_URL_PATH ) );
        if ( empty( $file_name ) || ! preg_match( '/\.(jpg|jpeg|png|gif|webp|svg)$/i', $file_name ) ) {
            $file_name = 'image-' . time() . '.jpg';
        }

        $file_array = array(
            'name'     => sanitize_file_name( $file_name ),
            'tmp_name' => $tmp,
        );

        // Subir a la biblioteca de medios
        $attachment_id = media_handle_sideload( $file_array, 0 );

        // Limpiar archivo temporal
        if ( file_exists( $tmp ) ) {
            @unlink( $tmp );
        }

        if ( is_wp_error( $attachment_id ) ) {
            return new WP_REST_Response( array(
                'error'   => 'Error subiendo imagen',
                'message' => $attachment_id->get_error_message(),
            ), 500 );
        }

        // Actualizar título y alt
        if ( ! empty( $title ) ) {
            wp_update_post( array(
                'ID'         => $attachment_id,
                'post_title' => sanitize_text_field( $title ),
            ) );
        }

        if ( ! empty( $alt ) ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        }

        $metadata = wp_get_attachment_metadata( $attachment_id );

        return new WP_REST_Response( array(
            'success'   => true,
            'id'        => $attachment_id,
            'url'       => wp_get_attachment_url( $attachment_id ),
            'thumbnail' => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
            'medium'    => wp_get_attachment_image_url( $attachment_id, 'medium' ),
            'large'     => wp_get_attachment_image_url( $attachment_id, 'large' ),
            'full'      => wp_get_attachment_image_url( $attachment_id, 'full' ),
            'width'     => $metadata['width'] ?? null,
            'height'    => $metadata['height'] ?? null,
        ), 201 );
    }

    /**
     * Obtiene iconos disponibles
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_available_icons( $request ) {
        $search = strtolower( $request->get_param( 'search' ) );
        $category = $request->get_param( 'category' );

        $icons = $this->get_all_icons();

        // Filtrar por búsqueda
        if ( ! empty( $search ) ) {
            $icons = array_filter( $icons, function( $icon ) use ( $search ) {
                return strpos( strtolower( $icon['name'] ), $search ) !== false ||
                       strpos( strtolower( $icon['id'] ), $search ) !== false ||
                       in_array( $search, array_map( 'strtolower', $icon['tags'] ?? array() ), true );
            } );
        }

        // Filtrar por categoría
        if ( ! empty( $category ) ) {
            $icons = array_filter( $icons, function( $icon ) use ( $category ) {
                return ( $icon['category'] ?? '' ) === $category;
            } );
        }

        $categories = array_unique( array_column( $this->get_all_icons(), 'category' ) );

        return new WP_REST_Response( array(
            'icons'      => array_values( $icons ),
            'total'      => count( $icons ),
            'categories' => array_values( array_filter( $categories ) ),
            'usage'      => 'Usar el campo "id" como valor de icono en elementos VBP',
        ), 200 );
    }

    /**
     * Obtiene todos los iconos disponibles
     *
     * @return array
     */
    private function get_all_icons() {
        // Emojis comunes para usar como iconos
        $emoji_icons = array(
            // Tecnología
            array( 'id' => '⚡', 'name' => 'Rayo/Velocidad', 'category' => 'tech', 'tags' => array( 'fast', 'speed', 'power', 'energy' ) ),
            array( 'id' => '🚀', 'name' => 'Cohete', 'category' => 'tech', 'tags' => array( 'launch', 'startup', 'growth' ) ),
            array( 'id' => '💻', 'name' => 'Portátil', 'category' => 'tech', 'tags' => array( 'computer', 'work', 'laptop' ) ),
            array( 'id' => '📱', 'name' => 'Móvil', 'category' => 'tech', 'tags' => array( 'phone', 'mobile', 'app' ) ),
            array( 'id' => '🔧', 'name' => 'Herramienta', 'category' => 'tech', 'tags' => array( 'tool', 'settings', 'config' ) ),
            array( 'id' => '⚙️', 'name' => 'Engranaje', 'category' => 'tech', 'tags' => array( 'settings', 'gear', 'config' ) ),
            array( 'id' => '🔒', 'name' => 'Candado', 'category' => 'tech', 'tags' => array( 'security', 'lock', 'safe' ) ),
            array( 'id' => '🔓', 'name' => 'Candado abierto', 'category' => 'tech', 'tags' => array( 'unlock', 'open', 'access' ) ),
            array( 'id' => '🔗', 'name' => 'Enlace', 'category' => 'tech', 'tags' => array( 'link', 'connect', 'chain' ) ),
            array( 'id' => '📊', 'name' => 'Gráfico barras', 'category' => 'tech', 'tags' => array( 'chart', 'stats', 'analytics' ) ),
            array( 'id' => '📈', 'name' => 'Gráfico subida', 'category' => 'tech', 'tags' => array( 'growth', 'increase', 'trending' ) ),
            array( 'id' => '🔄', 'name' => 'Sincronizar', 'category' => 'tech', 'tags' => array( 'sync', 'refresh', 'update' ) ),
            array( 'id' => '☁️', 'name' => 'Nube', 'category' => 'tech', 'tags' => array( 'cloud', 'storage', 'online' ) ),
            array( 'id' => '🌐', 'name' => 'Globo', 'category' => 'tech', 'tags' => array( 'web', 'internet', 'global' ) ),

            // Comunidad/Social
            array( 'id' => '👥', 'name' => 'Grupo personas', 'category' => 'community', 'tags' => array( 'users', 'team', 'people' ) ),
            array( 'id' => '🤝', 'name' => 'Apretón manos', 'category' => 'community', 'tags' => array( 'handshake', 'deal', 'partnership' ) ),
            array( 'id' => '💬', 'name' => 'Mensaje', 'category' => 'community', 'tags' => array( 'chat', 'message', 'talk' ) ),
            array( 'id' => '📣', 'name' => 'Megáfono', 'category' => 'community', 'tags' => array( 'announce', 'loud', 'speak' ) ),
            array( 'id' => '❤️', 'name' => 'Corazón', 'category' => 'community', 'tags' => array( 'love', 'heart', 'like' ) ),
            array( 'id' => '🏠', 'name' => 'Casa', 'category' => 'community', 'tags' => array( 'home', 'house', 'family' ) ),
            array( 'id' => '🎯', 'name' => 'Diana', 'category' => 'community', 'tags' => array( 'target', 'goal', 'focus' ) ),
            array( 'id' => '🏆', 'name' => 'Trofeo', 'category' => 'community', 'tags' => array( 'trophy', 'winner', 'success' ) ),
            array( 'id' => '⭐', 'name' => 'Estrella', 'category' => 'community', 'tags' => array( 'star', 'rating', 'favorite' ) ),
            array( 'id' => '✨', 'name' => 'Brillos', 'category' => 'community', 'tags' => array( 'sparkle', 'magic', 'new' ) ),

            // Naturaleza/Ecología
            array( 'id' => '🌱', 'name' => 'Planta', 'category' => 'nature', 'tags' => array( 'plant', 'grow', 'eco' ) ),
            array( 'id' => '🌿', 'name' => 'Hoja', 'category' => 'nature', 'tags' => array( 'leaf', 'nature', 'green' ) ),
            array( 'id' => '🌍', 'name' => 'Tierra', 'category' => 'nature', 'tags' => array( 'earth', 'planet', 'world' ) ),
            array( 'id' => '💚', 'name' => 'Corazón verde', 'category' => 'nature', 'tags' => array( 'eco', 'green', 'sustainable' ) ),
            array( 'id' => '♻️', 'name' => 'Reciclaje', 'category' => 'nature', 'tags' => array( 'recycle', 'eco', 'reuse' ) ),
            array( 'id' => '🌻', 'name' => 'Girasol', 'category' => 'nature', 'tags' => array( 'sunflower', 'happy', 'nature' ) ),
            array( 'id' => '🌳', 'name' => 'Árbol', 'category' => 'nature', 'tags' => array( 'tree', 'forest', 'nature' ) ),
            array( 'id' => '💧', 'name' => 'Gota agua', 'category' => 'nature', 'tags' => array( 'water', 'drop', 'clean' ) ),
            array( 'id' => '☀️', 'name' => 'Sol', 'category' => 'nature', 'tags' => array( 'sun', 'energy', 'bright' ) ),
            array( 'id' => '🌈', 'name' => 'Arcoíris', 'category' => 'nature', 'tags' => array( 'rainbow', 'diversity', 'hope' ) ),

            // Negocio/Comercio
            array( 'id' => '💰', 'name' => 'Bolsa dinero', 'category' => 'business', 'tags' => array( 'money', 'profit', 'savings' ) ),
            array( 'id' => '💳', 'name' => 'Tarjeta', 'category' => 'business', 'tags' => array( 'card', 'payment', 'credit' ) ),
            array( 'id' => '🛒', 'name' => 'Carrito', 'category' => 'business', 'tags' => array( 'cart', 'shopping', 'buy' ) ),
            array( 'id' => '📦', 'name' => 'Caja', 'category' => 'business', 'tags' => array( 'package', 'box', 'shipping' ) ),
            array( 'id' => '🚚', 'name' => 'Camión', 'category' => 'business', 'tags' => array( 'delivery', 'truck', 'shipping' ) ),
            array( 'id' => '📝', 'name' => 'Documento', 'category' => 'business', 'tags' => array( 'document', 'write', 'note' ) ),
            array( 'id' => '📋', 'name' => 'Portapapeles', 'category' => 'business', 'tags' => array( 'clipboard', 'list', 'tasks' ) ),
            array( 'id' => '✅', 'name' => 'Check verde', 'category' => 'business', 'tags' => array( 'check', 'done', 'success' ) ),
            array( 'id' => '🎁', 'name' => 'Regalo', 'category' => 'business', 'tags' => array( 'gift', 'present', 'reward' ) ),
            array( 'id' => '🏷️', 'name' => 'Etiqueta', 'category' => 'business', 'tags' => array( 'tag', 'label', 'price' ) ),

            // Tiempo/Calendario
            array( 'id' => '📅', 'name' => 'Calendario', 'category' => 'time', 'tags' => array( 'calendar', 'date', 'schedule' ) ),
            array( 'id' => '⏰', 'name' => 'Reloj', 'category' => 'time', 'tags' => array( 'clock', 'time', 'alarm' ) ),
            array( 'id' => '⏱️', 'name' => 'Cronómetro', 'category' => 'time', 'tags' => array( 'timer', 'stopwatch', 'fast' ) ),
            array( 'id' => '🗓️', 'name' => 'Calendario espiral', 'category' => 'time', 'tags' => array( 'calendar', 'planner', 'month' ) ),
            array( 'id' => '⌛', 'name' => 'Reloj arena', 'category' => 'time', 'tags' => array( 'hourglass', 'wait', 'time' ) ),

            // Educación
            array( 'id' => '📚', 'name' => 'Libros', 'category' => 'education', 'tags' => array( 'books', 'learn', 'study' ) ),
            array( 'id' => '🎓', 'name' => 'Graduación', 'category' => 'education', 'tags' => array( 'graduate', 'education', 'degree' ) ),
            array( 'id' => '✏️', 'name' => 'Lápiz', 'category' => 'education', 'tags' => array( 'pencil', 'write', 'edit' ) ),
            array( 'id' => '💡', 'name' => 'Bombilla', 'category' => 'education', 'tags' => array( 'idea', 'light', 'innovation' ) ),
            array( 'id' => '🔬', 'name' => 'Microscopio', 'category' => 'education', 'tags' => array( 'science', 'research', 'lab' ) ),
            array( 'id' => '🧠', 'name' => 'Cerebro', 'category' => 'education', 'tags' => array( 'brain', 'think', 'smart' ) ),

            // Comida
            array( 'id' => '🥗', 'name' => 'Ensalada', 'category' => 'food', 'tags' => array( 'salad', 'healthy', 'food' ) ),
            array( 'id' => '🍎', 'name' => 'Manzana', 'category' => 'food', 'tags' => array( 'apple', 'fruit', 'healthy' ) ),
            array( 'id' => '🥕', 'name' => 'Zanahoria', 'category' => 'food', 'tags' => array( 'carrot', 'vegetable', 'organic' ) ),
            array( 'id' => '🍞', 'name' => 'Pan', 'category' => 'food', 'tags' => array( 'bread', 'bakery', 'food' ) ),
            array( 'id' => '☕', 'name' => 'Café', 'category' => 'food', 'tags' => array( 'coffee', 'drink', 'morning' ) ),
            array( 'id' => '🍽️', 'name' => 'Plato cubiertos', 'category' => 'food', 'tags' => array( 'restaurant', 'dining', 'food' ) ),

            // Salud
            array( 'id' => '🏥', 'name' => 'Hospital', 'category' => 'health', 'tags' => array( 'hospital', 'health', 'medical' ) ),
            array( 'id' => '💊', 'name' => 'Pastilla', 'category' => 'health', 'tags' => array( 'pill', 'medicine', 'health' ) ),
            array( 'id' => '🩺', 'name' => 'Estetoscopio', 'category' => 'health', 'tags' => array( 'doctor', 'medical', 'health' ) ),
            array( 'id' => '🏃', 'name' => 'Correr', 'category' => 'health', 'tags' => array( 'run', 'exercise', 'fitness' ) ),
            array( 'id' => '🧘', 'name' => 'Yoga', 'category' => 'health', 'tags' => array( 'yoga', 'meditation', 'wellness' ) ),
            array( 'id' => '💪', 'name' => 'Músculo', 'category' => 'health', 'tags' => array( 'strong', 'fitness', 'power' ) ),

            // Transporte
            array( 'id' => '🚗', 'name' => 'Coche', 'category' => 'transport', 'tags' => array( 'car', 'drive', 'travel' ) ),
            array( 'id' => '🚲', 'name' => 'Bicicleta', 'category' => 'transport', 'tags' => array( 'bike', 'cycle', 'eco' ) ),
            array( 'id' => '🚌', 'name' => 'Autobús', 'category' => 'transport', 'tags' => array( 'bus', 'public', 'transport' ) ),
            array( 'id' => '✈️', 'name' => 'Avión', 'category' => 'transport', 'tags' => array( 'plane', 'travel', 'fly' ) ),
            array( 'id' => '🚢', 'name' => 'Barco', 'category' => 'transport', 'tags' => array( 'ship', 'boat', 'travel' ) ),

            // Comunicación
            array( 'id' => '📧', 'name' => 'Email', 'category' => 'communication', 'tags' => array( 'email', 'mail', 'message' ) ),
            array( 'id' => '📞', 'name' => 'Teléfono', 'category' => 'communication', 'tags' => array( 'phone', 'call', 'contact' ) ),
            array( 'id' => '🔔', 'name' => 'Campana', 'category' => 'communication', 'tags' => array( 'bell', 'notification', 'alert' ) ),
            array( 'id' => '📢', 'name' => 'Altavoz', 'category' => 'communication', 'tags' => array( 'speaker', 'announce', 'loud' ) ),
            array( 'id' => '🎤', 'name' => 'Micrófono', 'category' => 'communication', 'tags' => array( 'mic', 'speak', 'podcast' ) ),
        );

        // Dashicons de WordPress
        $dashicons = array(
            array( 'id' => 'dashicons-admin-home', 'name' => 'Home', 'category' => 'dashicons', 'tags' => array( 'home', 'house' ) ),
            array( 'id' => 'dashicons-admin-users', 'name' => 'Users', 'category' => 'dashicons', 'tags' => array( 'users', 'people' ) ),
            array( 'id' => 'dashicons-admin-settings', 'name' => 'Settings', 'category' => 'dashicons', 'tags' => array( 'settings', 'config' ) ),
            array( 'id' => 'dashicons-admin-post', 'name' => 'Post', 'category' => 'dashicons', 'tags' => array( 'post', 'content' ) ),
            array( 'id' => 'dashicons-admin-media', 'name' => 'Media', 'category' => 'dashicons', 'tags' => array( 'media', 'image' ) ),
            array( 'id' => 'dashicons-admin-links', 'name' => 'Links', 'category' => 'dashicons', 'tags' => array( 'links', 'url' ) ),
            array( 'id' => 'dashicons-admin-comments', 'name' => 'Comments', 'category' => 'dashicons', 'tags' => array( 'comments', 'chat' ) ),
            array( 'id' => 'dashicons-admin-tools', 'name' => 'Tools', 'category' => 'dashicons', 'tags' => array( 'tools', 'wrench' ) ),
            array( 'id' => 'dashicons-admin-plugins', 'name' => 'Plugins', 'category' => 'dashicons', 'tags' => array( 'plugins', 'addon' ) ),
            array( 'id' => 'dashicons-calendar-alt', 'name' => 'Calendar', 'category' => 'dashicons', 'tags' => array( 'calendar', 'date' ) ),
            array( 'id' => 'dashicons-cart', 'name' => 'Cart', 'category' => 'dashicons', 'tags' => array( 'cart', 'shop' ) ),
            array( 'id' => 'dashicons-email', 'name' => 'Email', 'category' => 'dashicons', 'tags' => array( 'email', 'mail' ) ),
            array( 'id' => 'dashicons-heart', 'name' => 'Heart', 'category' => 'dashicons', 'tags' => array( 'heart', 'love' ) ),
            array( 'id' => 'dashicons-star-filled', 'name' => 'Star', 'category' => 'dashicons', 'tags' => array( 'star', 'rating' ) ),
            array( 'id' => 'dashicons-location', 'name' => 'Location', 'category' => 'dashicons', 'tags' => array( 'location', 'map' ) ),
            array( 'id' => 'dashicons-clock', 'name' => 'Clock', 'category' => 'dashicons', 'tags' => array( 'clock', 'time' ) ),
            array( 'id' => 'dashicons-shield', 'name' => 'Shield', 'category' => 'dashicons', 'tags' => array( 'shield', 'security' ) ),
            array( 'id' => 'dashicons-search', 'name' => 'Search', 'category' => 'dashicons', 'tags' => array( 'search', 'find' ) ),
            array( 'id' => 'dashicons-visibility', 'name' => 'Visibility', 'category' => 'dashicons', 'tags' => array( 'eye', 'view' ) ),
            array( 'id' => 'dashicons-megaphone', 'name' => 'Megaphone', 'category' => 'dashicons', 'tags' => array( 'announce', 'megaphone' ) ),
        );

        return array_merge( $emoji_icons, $dashicons );
    }

    /**
     * Obtiene placeholders de alta calidad
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_placeholders( $request ) {
        $category = $request->get_param( 'category' );
        $width = $request->get_param( 'width' );
        $height = $request->get_param( 'height' );
        $count = $request->get_param( 'count' );

        // Mapeo de categorías a términos de búsqueda de Unsplash
        $category_keywords = array(
            'nature'       => array( 'nature', 'forest', 'mountain', 'ocean', 'landscape' ),
            'business'     => array( 'office', 'meeting', 'teamwork', 'workspace', 'professional' ),
            'technology'   => array( 'technology', 'computer', 'code', 'digital', 'innovation' ),
            'people'       => array( 'people', 'portrait', 'team', 'community', 'diverse' ),
            'food'         => array( 'food', 'cooking', 'restaurant', 'healthy', 'organic' ),
            'architecture' => array( 'architecture', 'building', 'city', 'modern', 'design' ),
            'abstract'     => array( 'abstract', 'pattern', 'texture', 'gradient', 'minimal' ),
            'animals'      => array( 'animals', 'wildlife', 'pets', 'nature', 'birds' ),
        );

        $placeholders = array();

        // Generar URLs de placeholder
        for ( $i = 1; $i <= $count; $i++ ) {
            $keyword = '';
            if ( ! empty( $category ) && isset( $category_keywords[ $category ] ) ) {
                $keywords = $category_keywords[ $category ];
                $keyword = $keywords[ array_rand( $keywords ) ];
            }

            // Usar servicios de imágenes placeholder de alta calidad
            $seed = $i + ( empty( $category ) ? 0 : crc32( $category ) );

            $placeholders[] = array(
                'id'          => 'placeholder_' . $i,
                'category'    => $category ?: 'general',
                'urls'        => array(
                    // Unsplash Source (alta calidad, gratuito)
                    'unsplash'    => "https://source.unsplash.com/{$width}x{$height}/?" . ( $keyword ?: 'minimal' ) . "&sig={$seed}",
                    // Picsum (Lorem Picsum - alta calidad)
                    'picsum'      => "https://picsum.photos/seed/{$seed}/{$width}/{$height}",
                    // Placeholder con color del preset
                    'solid'       => "https://via.placeholder.com/{$width}x{$height}/6366f1/ffffff?text=",
                    // Placeholder con gradiente (usando placehold.co)
                    'gradient'    => "https://placehold.co/{$width}x{$height}/6366f1/818cf8",
                ),
                'recommended' => "https://picsum.photos/seed/{$seed}/{$width}/{$height}",
                'width'       => $width,
                'height'      => $height,
            );
        }

        return new WP_REST_Response( array(
            'placeholders' => $placeholders,
            'total'        => count( $placeholders ),
            'dimensions'   => "{$width}x{$height}",
            'category'     => $category ?: 'all',
            'usage'        => 'Usar la URL de "recommended" o elegir de "urls" según necesidad',
            'tips'         => array(
                'Para hero: usar 1920x1080 o 1600x900',
                'Para cards: usar 400x300 o 600x400',
                'Para avatares: usar 200x200 o 300x300',
                'Para thumbnails: usar 150x150',
            ),
        ), 200 );
    }

    /**
     * Busca imágenes de stock (simulado sin API key)
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function search_stock_images( $request ) {
        $query = $request->get_param( 'query' );
        $per_page = $request->get_param( 'per_page' );

        // Sin API key de Unsplash/Pexels, generamos URLs de Unsplash Source
        $results = array();
        $clean_query = sanitize_title( $query );

        for ( $i = 1; $i <= $per_page; $i++ ) {
            $seed = crc32( $query ) + $i;
            $results[] = array(
                'id'          => "stock_{$clean_query}_{$i}",
                'query'       => $query,
                'urls'        => array(
                    'small'   => "https://source.unsplash.com/400x300/?{$clean_query}&sig={$seed}",
                    'medium'  => "https://source.unsplash.com/800x600/?{$clean_query}&sig={$seed}",
                    'large'   => "https://source.unsplash.com/1200x800/?{$clean_query}&sig={$seed}",
                    'full'    => "https://source.unsplash.com/1920x1080/?{$clean_query}&sig={$seed}",
                ),
                'recommended' => "https://source.unsplash.com/1200x800/?{$clean_query}&sig={$seed}",
                'attribution' => 'Unsplash',
                'license'     => 'Free to use',
            );
        }

        return new WP_REST_Response( array(
            'results' => $results,
            'query'   => $query,
            'total'   => count( $results ),
            'note'    => 'Imágenes de Unsplash Source (gratuitas, sin API key)',
            'tip'     => 'Para usar, subir con POST /media/upload-url usando la URL deseada',
        ), 200 );
    }

    /**
     * Obtiene fuentes disponibles
     *
     * @return WP_REST_Response
     */
    public function get_available_fonts() {
        $fonts = array(
            // Sans-serif modernas
            array(
                'family'   => 'Inter',
                'category' => 'sans-serif',
                'weights'  => array( 400, 500, 600, 700 ),
                'style'    => 'modern',
                'url'      => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            ),
            array(
                'family'   => 'Poppins',
                'category' => 'sans-serif',
                'weights'  => array( 400, 500, 600, 700 ),
                'style'    => 'friendly',
                'url'      => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
            ),
            array(
                'family'   => 'DM Sans',
                'category' => 'sans-serif',
                'weights'  => array( 400, 500, 700 ),
                'style'    => 'minimal',
                'url'      => 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap',
            ),
            array(
                'family'   => 'Space Grotesk',
                'category' => 'sans-serif',
                'weights'  => array( 400, 500, 600, 700 ),
                'style'    => 'tech',
                'url'      => 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap',
            ),
            array(
                'family'   => 'Nunito',
                'category' => 'sans-serif',
                'weights'  => array( 400, 600, 700 ),
                'style'    => 'rounded',
                'url'      => 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap',
            ),
            array(
                'family'   => 'Roboto',
                'category' => 'sans-serif',
                'weights'  => array( 400, 500, 700 ),
                'style'    => 'corporate',
                'url'      => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
            ),
            array(
                'family'   => 'Open Sans',
                'category' => 'sans-serif',
                'weights'  => array( 400, 600, 700 ),
                'style'    => 'neutral',
                'url'      => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap',
            ),

            // Serif elegantes
            array(
                'family'   => 'Playfair Display',
                'category' => 'serif',
                'weights'  => array( 400, 500, 600, 700 ),
                'style'    => 'elegant',
                'url'      => 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap',
            ),
            array(
                'family'   => 'Merriweather',
                'category' => 'serif',
                'weights'  => array( 400, 700 ),
                'style'    => 'readable',
                'url'      => 'https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&display=swap',
            ),
            array(
                'family'   => 'Lora',
                'category' => 'serif',
                'weights'  => array( 400, 500, 600, 700 ),
                'style'    => 'classic',
                'url'      => 'https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&display=swap',
            ),

            // Monospace
            array(
                'family'   => 'JetBrains Mono',
                'category' => 'monospace',
                'weights'  => array( 400, 500, 700 ),
                'style'    => 'code',
                'url'      => 'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap',
            ),
            array(
                'family'   => 'Fira Code',
                'category' => 'monospace',
                'weights'  => array( 400, 500, 700 ),
                'style'    => 'code',
                'url'      => 'https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;700&display=swap',
            ),
        );

        // Agrupar por categoría
        $by_category = array();
        foreach ( $fonts as $font ) {
            $cat = $font['category'];
            if ( ! isset( $by_category[ $cat ] ) ) {
                $by_category[ $cat ] = array();
            }
            $by_category[ $cat ][] = $font;
        }

        return new WP_REST_Response( array(
            'fonts'        => $fonts,
            'by_category'  => $by_category,
            'total'        => count( $fonts ),
            'usage'        => "Añadir URL al <head> o usar la familia en CSS: font-family: 'Inter', sans-serif",
        ), 200 );
    }

    /**
     * Obtiene gradientes predefinidos
     *
     * @return WP_REST_Response
     */
    public function get_gradients() {
        $gradients = array(
            // Azules
            array( 'id' => 'ocean', 'name' => 'Ocean', 'css' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 'category' => 'blue' ),
            array( 'id' => 'sky', 'name' => 'Sky', 'css' => 'linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%)', 'category' => 'blue' ),
            array( 'id' => 'deep-blue', 'name' => 'Deep Blue', 'css' => 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)', 'category' => 'blue' ),

            // Púrpuras
            array( 'id' => 'purple-haze', 'name' => 'Purple Haze', 'css' => 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)', 'category' => 'purple' ),
            array( 'id' => 'cosmic', 'name' => 'Cosmic', 'css' => 'linear-gradient(135deg, #7c3aed 0%, #c026d3 100%)', 'category' => 'purple' ),
            array( 'id' => 'violet', 'name' => 'Violet', 'css' => 'linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%)', 'category' => 'purple' ),

            // Rosas
            array( 'id' => 'sunset', 'name' => 'Sunset', 'css' => 'linear-gradient(135deg, #f43f5e 0%, #ec4899 100%)', 'category' => 'pink' ),
            array( 'id' => 'rose', 'name' => 'Rose', 'css' => 'linear-gradient(135deg, #ec4899 0%, #f472b6 100%)', 'category' => 'pink' ),
            array( 'id' => 'flamingo', 'name' => 'Flamingo', 'css' => 'linear-gradient(135deg, #f472b6 0%, #fb7185 100%)', 'category' => 'pink' ),

            // Verdes
            array( 'id' => 'emerald', 'name' => 'Emerald', 'css' => 'linear-gradient(135deg, #059669 0%, #10b981 100%)', 'category' => 'green' ),
            array( 'id' => 'forest', 'name' => 'Forest', 'css' => 'linear-gradient(135deg, #047857 0%, #059669 100%)', 'category' => 'green' ),
            array( 'id' => 'lime', 'name' => 'Lime', 'css' => 'linear-gradient(135deg, #22c55e 0%, #84cc16 100%)', 'category' => 'green' ),

            // Naranjas/Amarillos
            array( 'id' => 'fire', 'name' => 'Fire', 'css' => 'linear-gradient(135deg, #f59e0b 0%, #ef4444 100%)', 'category' => 'orange' ),
            array( 'id' => 'gold', 'name' => 'Gold', 'css' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)', 'category' => 'orange' ),
            array( 'id' => 'sunrise', 'name' => 'Sunrise', 'css' => 'linear-gradient(135deg, #fbbf24 0%, #f97316 100%)', 'category' => 'orange' ),

            // Oscuros
            array( 'id' => 'midnight', 'name' => 'Midnight', 'css' => 'linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%)', 'category' => 'dark' ),
            array( 'id' => 'dark-purple', 'name' => 'Dark Purple', 'css' => 'linear-gradient(135deg, #1a1a2e 0%, #2d2d44 100%)', 'category' => 'dark' ),
            array( 'id' => 'charcoal', 'name' => 'Charcoal', 'css' => 'linear-gradient(135deg, #18181b 0%, #27272a 100%)', 'category' => 'dark' ),

            // Multi-color
            array( 'id' => 'rainbow', 'name' => 'Rainbow', 'css' => 'linear-gradient(135deg, #ec4899 0%, #8b5cf6 50%, #06b6d4 100%)', 'category' => 'multi' ),
            array( 'id' => 'aurora', 'name' => 'Aurora', 'css' => 'linear-gradient(135deg, #22c55e 0%, #06b6d4 50%, #8b5cf6 100%)', 'category' => 'multi' ),
            array( 'id' => 'neon', 'name' => 'Neon', 'css' => 'linear-gradient(135deg, #00d4ff 0%, #7c3aed 50%, #00ff88 100%)', 'category' => 'multi' ),

            // Sutiles
            array( 'id' => 'subtle-gray', 'name' => 'Subtle Gray', 'css' => 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)', 'category' => 'subtle' ),
            array( 'id' => 'subtle-blue', 'name' => 'Subtle Blue', 'css' => 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)', 'category' => 'subtle' ),
            array( 'id' => 'subtle-purple', 'name' => 'Subtle Purple', 'css' => 'linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%)', 'category' => 'subtle' ),
        );

        return new WP_REST_Response( array(
            'gradients' => $gradients,
            'total'     => count( $gradients ),
            'usage'     => 'Usar el campo "css" como valor de background en estilos',
        ), 200 );
    }

    /**
     * Obtiene paletas de colores
     *
     * @return WP_REST_Response
     */
    public function get_color_palettes() {
        $palettes = array(
            array(
                'id'          => 'modern-blue',
                'name'        => 'Modern Blue',
                'description' => 'Paleta azul moderna, ideal para tech y startups',
                'colors'      => array(
                    'primary'    => '#3b82f6',
                    'secondary'  => '#6366f1',
                    'accent'     => '#f59e0b',
                    'background' => '#ffffff',
                    'surface'    => '#f8fafc',
                    'text'       => '#1e293b',
                    'muted'      => '#64748b',
                ),
            ),
            array(
                'id'          => 'emerald-green',
                'name'        => 'Emerald Green',
                'description' => 'Paleta verde, ideal para naturaleza y ecología',
                'colors'      => array(
                    'primary'    => '#059669',
                    'secondary'  => '#0d9488',
                    'accent'     => '#d97706',
                    'background' => '#ffffff',
                    'surface'    => '#f0fdf4',
                    'text'       => '#1c3829',
                    'muted'      => '#4d6356',
                ),
            ),
            array(
                'id'          => 'royal-purple',
                'name'        => 'Royal Purple',
                'description' => 'Paleta púrpura, ideal para creativos y premium',
                'colors'      => array(
                    'primary'    => '#7c3aed',
                    'secondary'  => '#8b5cf6',
                    'accent'     => '#ec4899',
                    'background' => '#ffffff',
                    'surface'    => '#faf5ff',
                    'text'       => '#1f2937',
                    'muted'      => '#6b7280',
                ),
            ),
            array(
                'id'          => 'warm-coral',
                'name'        => 'Warm Coral',
                'description' => 'Paleta cálida, ideal para comunidades y social',
                'colors'      => array(
                    'primary'    => '#f43f5e',
                    'secondary'  => '#ec4899',
                    'accent'     => '#f59e0b',
                    'background' => '#ffffff',
                    'surface'    => '#fff1f2',
                    'text'       => '#1f2937',
                    'muted'      => '#6b7280',
                ),
            ),
            array(
                'id'          => 'dark-mode',
                'name'        => 'Dark Mode',
                'description' => 'Paleta oscura, ideal para apps y tech',
                'colors'      => array(
                    'primary'    => '#8b5cf6',
                    'secondary'  => '#06b6d4',
                    'accent'     => '#f59e0b',
                    'background' => '#0f0f0f',
                    'surface'    => '#1a1a1a',
                    'text'       => '#fafafa',
                    'muted'      => '#a1a1aa',
                ),
            ),
            array(
                'id'          => 'corporate-blue',
                'name'        => 'Corporate Blue',
                'description' => 'Paleta corporativa, ideal para empresas y B2B',
                'colors'      => array(
                    'primary'    => '#1e40af',
                    'secondary'  => '#0f766e',
                    'accent'     => '#dc2626',
                    'background' => '#ffffff',
                    'surface'    => '#f1f5f9',
                    'text'       => '#0f172a',
                    'muted'      => '#475569',
                ),
            ),
            array(
                'id'          => 'elegant-gold',
                'name'        => 'Elegant Gold',
                'description' => 'Paleta elegante, ideal para lujo y premium',
                'colors'      => array(
                    'primary'    => '#b8860b',
                    'secondary'  => '#1a1a2e',
                    'accent'     => '#c9a227',
                    'background' => '#fefefe',
                    'surface'    => '#f8f6f0',
                    'text'       => '#1a1a2e',
                    'muted'      => '#4a4a5a',
                ),
            ),
            array(
                'id'          => 'minimalist',
                'name'        => 'Minimalist',
                'description' => 'Paleta minimalista, ideal para portfolios y diseño',
                'colors'      => array(
                    'primary'    => '#18181b',
                    'secondary'  => '#71717a',
                    'accent'     => '#18181b',
                    'background' => '#ffffff',
                    'surface'    => '#fafafa',
                    'text'       => '#18181b',
                    'muted'      => '#71717a',
                ),
            ),
        );

        return new WP_REST_Response( array(
            'palettes' => $palettes,
            'total'    => count( $palettes ),
            'usage'    => 'Usar los colores en los estilos de elementos VBP o aplicar como preset personalizado',
        ), 200 );
    }

    /**
     * Genera una URL de placeholder con texto
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function generate_placeholder( $request ) {
        $width = $request->get_param( 'width' );
        $height = $request->get_param( 'height' );
        $text = $request->get_param( 'text' );
        $bg_color = ltrim( $request->get_param( 'bg_color' ), '#' );
        $text_color = ltrim( $request->get_param( 'text_color' ), '#' );

        // Generar URLs de diferentes servicios
        $encoded_text = rawurlencode( $text ?: "{$width}x{$height}" );

        $urls = array(
            'placehold' => "https://via.placeholder.com/{$width}x{$height}/{$bg_color}/{$text_color}?text={$encoded_text}",
            'dummyimage' => "https://dummyimage.com/{$width}x{$height}/{$bg_color}/{$text_color}&text={$encoded_text}",
            'placeholderco' => "https://placehold.co/{$width}x{$height}/{$bg_color}/{$text_color}?text={$encoded_text}",
        );

        return new WP_REST_Response( array(
            'urls'        => $urls,
            'recommended' => $urls['placeholderco'],
            'width'       => $width,
            'height'      => $height,
            'text'        => $text ?: "{$width}x{$height}",
            'colors'      => array(
                'background' => "#{$bg_color}",
                'text'       => "#{$text_color}",
            ),
        ), 200 );
    }
}

// Inicializar
Flavor_Media_API::get_instance();
