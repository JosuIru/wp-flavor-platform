<?php
/**
 * Gestor de Custom Post Types para Apps Móviles
 *
 * Gestiona qué CPTs se muestran en las apps y cómo se presentan
 *
 * @package FlavorPlatform
 * @subpackage AppIntegration
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar CPTs en apps
 *
 * @since 3.0.0
 */
class Flavor_App_CPT_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_App_CPT_Manager
     */
    private static $instancia = null;

    /**
     * CPTs excluidos por defecto
     *
     * @var array
     */
    private $excluded_cpts = [
        'attachment',
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        'wp_block',
        'wp_template',
        'wp_template_part',
        'wp_global_styles',
        'wp_navigation',
    ];

    /**
     * Iconos predefinidos para CPTs comunes
     *
     * @var array
     */
    private $default_icons = [
        'post' => 'article',
        'page' => 'description',
        'product' => 'shopping_bag',
        'evento' => 'event',
        'course' => 'school',
        'portfolio' => 'work',
        'testimonial' => 'format_quote',
        'faq' => 'help',
        'team' => 'people',
        'service' => 'build',
        'download' => 'download',
        'video' => 'videocam',
        'podcast' => 'mic',
        'recipe' => 'restaurant',
        'property' => 'home',
        'job' => 'work_outline',
    ];

    /**
     * Colores predefinidos
     *
     * @var array
     */
    private $default_colors = [
        '#2196F3', // Blue
        '#4CAF50', // Green
        '#FF9800', // Orange
        '#9C27B0', // Purple
        '#F44336', // Red
        '#00BCD4', // Cyan
        '#FFEB3B', // Yellow
        '#795548', // Brown
        '#607D8B', // Blue Grey
        '#E91E63', // Pink
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_App_CPT_Manager
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Endpoint REST API para obtener CPTs configurados
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX para guardar configuración
        add_action('wp_ajax_flavor_save_cpt_config', [$this, 'ajax_save_config']);
        add_action('wp_ajax_flavor_get_cpt_preview', [$this, 'ajax_get_preview']);
    }

    /**
     * Registra rutas REST API
     *
     * @return void
     */
    public function register_rest_routes() {
        // Endpoint para obtener CPTs configurados
        register_rest_route('app-discovery/v1', '/custom-post-types', [
            'methods' => 'GET',
            'callback' => [$this, 'get_configured_cpts'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint para obtener posts de un CPT
        register_rest_route('app-discovery/v1', '/cpt/(?P<post_type>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_cpt_posts'],
            'permission_callback' => '__return_true',
            'args' => [
                'post_type' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'page' => [
                    'default' => 1,
                    'type' => 'integer',
                ],
                'per_page' => [
                    'default' => 10,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Endpoint para obtener un post individual
        register_rest_route('app-discovery/v1', '/cpt/(?P<post_type>[a-zA-Z0-9_-]+)/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_single_cpt_post'],
            'permission_callback' => '__return_true',
            'args' => [
                'post_type' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
    }

    /**
     * Obtiene todos los CPTs disponibles en WordPress
     *
     * @return array
     */
    public function get_available_cpts() {
        $cpts = get_post_types([
            'public' => true,
        ], 'objects');

        $available = [];

        foreach ($cpts as $cpt) {
            // Excluir CPTs del sistema
            if (in_array($cpt->name, $this->excluded_cpts)) {
                continue;
            }

            $available[$cpt->name] = [
                'name' => $cpt->name,
                'label' => $cpt->label,
                'labels' => $cpt->labels,
                'description' => $cpt->description,
                'hierarchical' => $cpt->hierarchical,
                'has_archive' => $cpt->has_archive,
                'menu_icon' => $cpt->menu_icon,
                'supports' => get_all_post_type_supports($cpt->name),
                'taxonomies' => get_object_taxonomies($cpt->name),
                'count' => wp_count_posts($cpt->name)->publish,
            ];
        }

        return $available;
    }

    /**
     * Obtiene CPTs configurados para mostrar en la app
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_configured_cpts($request) {
        $config = get_option('flavor_app_cpts_config', []);

        $cpts = [];

        foreach ($config as $cpt_name => $cpt_config) {
            // Verificar que el CPT existe y está activo
            if (!$cpt_config['enabled'] || !post_type_exists($cpt_name)) {
                continue;
            }

            $post_type = get_post_type_object($cpt_name);

            if (!$post_type) {
                continue;
            }

            $cpts[] = [
                'id' => $cpt_name,
                'name' => $cpt_config['app_name'] ?: $post_type->label,
                'description' => $cpt_config['description'] ?: $post_type->description,
                'icon' => $cpt_config['icon'] ?: $this->get_default_icon($cpt_name),
                'color' => $cpt_config['color'] ?: $this->get_default_color($cpt_name),
                'order' => $cpt_config['order'] ?: 100,
                'show_in_navigation' => $cpt_config['show_in_navigation'],
                'show_featured_image' => $cpt_config['show_featured_image'],
                'show_author' => $cpt_config['show_author'],
                'show_date' => $cpt_config['show_date'],
                'show_excerpt' => $cpt_config['show_excerpt'],
                'show_categories' => $cpt_config['show_categories'],
                'show_tags' => $cpt_config['show_tags'],
                'enable_search' => $cpt_config['enable_search'],
                'enable_filters' => $cpt_config['enable_filters'],
                'taxonomies' => get_object_taxonomies($cpt_name),
                'endpoint' => rest_url('app-discovery/v1/cpt/' . $cpt_name),
                'total_posts' => wp_count_posts($cpt_name)->publish,
            ];
        }

        // Ordenar por orden configurado
        usort($cpts, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        return new WP_REST_Response([
            'success' => true,
            'cpts' => $cpts,
            'total' => count($cpts),
        ], 200);
    }

    /**
     * Obtiene posts de un CPT
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_cpt_posts($request) {
        $post_type = $request->get_param('post_type');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');

        // Verificar que el CPT está configurado y habilitado
        $config = get_option('flavor_app_cpts_config', []);
        if (!isset($config[$post_type]) || !$config[$post_type]['enabled']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Este tipo de contenido no está habilitado en la app',
            ], 403);
        }

        // Query de posts
        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Filtros opcionales
        if ($request->has_param('category')) {
            $args['category_name'] = $request->get_param('category');
        }

        if ($request->has_param('tag')) {
            $args['tag'] = $request->get_param('tag');
        }

        if ($request->has_param('search')) {
            $args['s'] = $request->get_param('search');
        }

        $query = new WP_Query($args);

        $posts = [];
        foreach ($query->posts as $post) {
            $posts[] = $this->format_post_for_app($post, $config[$post_type]);
        }

        return new WP_REST_Response([
            'success' => true,
            'posts' => $posts,
            'pagination' => [
                'total' => $query->found_posts,
                'total_pages' => $query->max_num_pages,
                'current_page' => $page,
                'per_page' => $per_page,
            ],
        ], 200);
    }

    /**
     * Obtiene un post individual de un CPT
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_single_cpt_post($request) {
        $post_type = $request->get_param('post_type');
        $post_id = $request->get_param('id');

        // Verificar que el CPT está configurado y habilitado
        $config = get_option('flavor_app_cpts_config', []);
        if (!isset($config[$post_type]) || !$config[$post_type]['enabled']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Este tipo de contenido no está habilitado en la app',
            ], 403);
        }

        $post = get_post($post_id);

        if (!$post || $post->post_type !== $post_type || $post->post_status !== 'publish') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Contenido no encontrado',
            ], 404);
        }

        $formatted_post = $this->format_post_for_app($post, $config[$post_type], true);

        return new WP_REST_Response([
            'success' => true,
            'post' => $formatted_post,
        ], 200);
    }

    /**
     * Formatea un post para la app
     *
     * @param WP_Post $post
     * @param array $config
     * @param bool $full Incluir contenido completo
     * @return array
     */
    private function format_post_for_app($post, $config, $full = false) {
        $data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'link' => get_permalink($post),
        ];

        // Fecha
        if ($config['show_date']) {
            $data['date'] = get_the_date('', $post);
            $data['date_formatted'] = get_the_date('d M Y', $post);
            $data['timestamp'] = get_post_timestamp($post);
        }

        // Autor
        if ($config['show_author']) {
            $author = get_userdata($post->post_author);
            $data['author'] = [
                'id' => $post->post_author,
                'name' => $author->display_name,
                'avatar' => get_avatar_url($post->post_author),
            ];
        }

        // Imagen destacada
        if ($config['show_featured_image'] && has_post_thumbnail($post)) {
            $thumbnail_id = get_post_thumbnail_id($post);
            $data['featured_image'] = [
                'id' => $thumbnail_id,
                'url' => get_the_post_thumbnail_url($post, 'large'),
                'thumbnail' => get_the_post_thumbnail_url($post, 'thumbnail'),
                'medium' => get_the_post_thumbnail_url($post, 'medium'),
                'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
            ];
        }

        // Excerpt
        if ($config['show_excerpt']) {
            $data['excerpt'] = get_the_excerpt($post);
        }

        // Categorías
        if ($config['show_categories']) {
            $categories = get_the_category($post);
            $data['categories'] = array_map(function($cat) {
                return [
                    'id' => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                ];
            }, $categories);
        }

        // Tags
        if ($config['show_tags']) {
            $tags = get_the_tags($post);
            if ($tags) {
                $data['tags'] = array_map(function($tag) {
                    return [
                        'id' => $tag->term_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ];
                }, $tags);
            }
        }

        // Contenido completo (solo para vista individual)
        if ($full) {
            $data['content'] = apply_filters('the_content', $post->post_content);
            $data['content_raw'] = $post->post_content;

            // Custom fields si están configurados
            $custom_fields = get_option('flavor_app_cpt_custom_fields_' . $post->post_type, []);
            if (!empty($custom_fields)) {
                $data['custom_fields'] = [];
                foreach ($custom_fields as $field_key) {
                    $data['custom_fields'][$field_key] = get_post_meta($post->ID, $field_key, true);
                }
            }
        }

        return apply_filters('flavor_app_cpt_format_post', $data, $post, $config, $full);
    }

    /**
     * Obtiene icono por defecto para un CPT
     *
     * @param string $cpt_name
     * @return string
     */
    private function get_default_icon($cpt_name) {
        return isset($this->default_icons[$cpt_name])
            ? $this->default_icons[$cpt_name]
            : 'description';
    }

    /**
     * Obtiene color por defecto para un CPT
     *
     * @param string $cpt_name
     * @return string
     */
    private function get_default_color($cpt_name) {
        $index = abs(crc32($cpt_name)) % count($this->default_colors);
        return $this->default_colors[$index];
    }

    /**
     * Guarda configuración de CPTs
     *
     * @param array $config
     * @return bool
     */
    public function save_config($config) {
        return update_option('flavor_app_cpts_config', $config);
    }

    /**
     * Obtiene configuración guardada
     *
     * @return array
     */
    public function get_config() {
        return get_option('flavor_app_cpts_config', []);
    }

    /**
     * AJAX: Guardar configuración
     *
     * @return void
     */
    public function ajax_save_config() {
        check_ajax_referer('flavor_app_cpts_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos');
        }

        $config = isset($_POST['config']) ? json_decode(stripslashes($_POST['config']), true) : [];

        if ($this->save_config($config)) {
            wp_send_json_success([
                'message' => __('Configuración guardada correctamente', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error('Error al guardar configuración');
        }
    }

    /**
     * AJAX: Obtener preview de un CPT
     *
     * @return void
     */
    public function ajax_get_preview() {
        check_ajax_referer('flavor_app_cpts_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos');
        }

        $post_type = sanitize_text_field($_POST['post_type'] ?? '');

        if (!post_type_exists($post_type)) {
            wp_send_json_error('Tipo de contenido no existe');
        }

        // Obtener algunos posts de ejemplo
        $posts = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => 3,
            'post_status' => 'publish',
        ]);

        $preview = [];
        foreach ($posts as $post) {
            $preview[] = [
                'title' => $post->post_title,
                'excerpt' => wp_trim_words(get_the_excerpt($post), 20),
                'thumbnail' => get_the_post_thumbnail_url($post, 'thumbnail'),
            ];
        }

        wp_send_json_success([
            'posts' => $preview,
            'total' => wp_count_posts($post_type)->publish,
        ]);
    }
}
