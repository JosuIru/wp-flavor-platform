<?php
/**
 * API de Contenido Nativo para Apps
 *
 * Expone páginas, posts y CPTs como JSON estructurado
 * para renderizado nativo en apps móviles (sin WebViews).
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Native_Content_API {

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'native-content/v1';

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registrar rutas REST
     */
    public function register_routes() {
        // Obtener página por slug
        register_rest_route(self::API_NAMESPACE, '/content/page/(?P<slug>[a-z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_page_content'],
            'permission_callback' => '__return_true',
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
            ],
        ]);

        // Obtener post por slug
        register_rest_route(self::API_NAMESPACE, '/content/post/(?P<slug>[a-z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_post_content'],
            'permission_callback' => '__return_true',
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
            ],
        ]);

        // Obtener CPT por tipo y slug
        register_rest_route(self::API_NAMESPACE, '/content/cpt/(?P<type>[a-z0-9_-]+)/(?P<slug>[a-z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_cpt_content'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener CPT por ID
        register_rest_route(self::API_NAMESPACE, '/content/by-id/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_content_by_id'],
            'permission_callback' => '__return_true',
        ]);

        // Listar contenido de un CPT
        register_rest_route(self::API_NAMESPACE, '/content/list/(?P<type>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_content_list'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener sección de Info
        register_rest_route(self::API_NAMESPACE, '/content/info-section/(?P<section>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_info_section'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener todas las secciones de Info
        register_rest_route(self::API_NAMESPACE, '/content/info-sections', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_info_sections'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener menú de navegación
        register_rest_route(self::API_NAMESPACE, '/content/menu/(?P<location>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_menu_content'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener pantalla nativa del sistema (info, chat, reservations, etc.)
        register_rest_route(self::API_NAMESPACE, '/screen/(?P<screen_id>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_native_screen'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener datos de módulo del plugin
        register_rest_route(self::API_NAMESPACE, '/module/(?P<module_id>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_module_data'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * GET /content/page/{slug}
     */
    public function get_page_content($request) {
        $slug = $request->get_param('slug');
        $page = get_page_by_path($slug);

        if (!$page) {
            return new WP_Error('not_found', __('Página no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $this->format_post_content($page),
        ], 200);
    }

    /**
     * GET /content/post/{slug}
     */
    public function get_post_content($request) {
        $slug = $request->get_param('slug');

        $posts = get_posts([
            'name' => $slug,
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 1,
        ]);

        if (empty($posts)) {
            return new WP_Error('not_found', __('Post no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $this->format_post_content($posts[0]),
        ], 200);
    }

    /**
     * GET /content/cpt/{type}/{slug}
     */
    public function get_cpt_content($request) {
        $type = sanitize_key($request->get_param('type'));
        $slug = sanitize_title($request->get_param('slug'));

        if (!post_type_exists($type)) {
            return new WP_Error('invalid_type', __('Tipo de contenido no válido', 'flavor-chat-ia'), ['status' => 400]);
        }

        $posts = get_posts([
            'name' => $slug,
            'post_type' => $type,
            'post_status' => 'publish',
            'numberposts' => 1,
        ]);

        if (empty($posts)) {
            return new WP_Error('not_found', __('Contenido no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $this->format_post_content($posts[0]),
        ], 200);
    }

    /**
     * GET /content/by-id/{id}
     */
    public function get_content_by_id($request) {
        $id = absint($request->get_param('id'));
        $post = get_post($id);

        if (!$post || $post->post_status !== 'publish') {
            return new WP_Error('not_found', __('Contenido no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $this->format_post_content($post),
        ], 200);
    }

    /**
     * GET /content/list/{type}
     */
    public function get_content_list($request) {
        $type = sanitize_key($request->get_param('type'));
        $page = absint($request->get_param('page')) ?: 1;
        $per_page = absint($request->get_param('per_page')) ?: 10;
        $category = sanitize_text_field($request->get_param('category') ?? '');
        $search = sanitize_text_field($request->get_param('search') ?? '');
        $orderby = sanitize_key($request->get_param('orderby') ?? 'date');
        $order = strtoupper($request->get_param('order') ?? 'DESC');

        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        if (!post_type_exists($type)) {
            return new WP_Error('invalid_type', __('Tipo de contenido no válido', 'flavor-chat-ia'), ['status' => 400]);
        }

        $args = [
            'post_type' => $type,
            'post_status' => 'publish',
            'posts_per_page' => min($per_page, 50),
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];

        if ($search) {
            $args['s'] = $search;
        }

        if ($category) {
            $args['tax_query'] = [
                [
                    'taxonomy' => $this->get_primary_taxonomy($type),
                    'field' => 'slug',
                    'terms' => $category,
                ],
            ];
        }

        $query = new WP_Query($args);
        $items = [];

        foreach ($query->posts as $post) {
            $items[] = $this->format_post_summary($post);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $page,
            ],
        ], 200);
    }

    /**
     * GET /content/info-section/{section}
     */
    public function get_info_section($request) {
        $section = sanitize_key($request->get_param('section'));
        $data = $this->get_info_section_data($section);

        if (!$data) {
            return new WP_Error('not_found', __('Sección no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * GET /content/info-sections
     */
    public function get_all_info_sections($request) {
        $app_config = get_option('flavor_apps_config', []);
        $info_sections = $app_config['info_sections'] ?? [];

        $sections = [];
        $available_sections = ['header', 'about', 'hours', 'contact', 'location', 'social', 'gallery', 'services'];

        foreach ($available_sections as $section_id) {
            $config = $info_sections[$section_id] ?? [];
            $enabled = $config['enabled'] ?? true;

            if (!$enabled) {
                continue;
            }

            $section_data = $this->get_info_section_data($section_id);
            if ($section_data) {
                $sections[] = [
                    'id' => $section_id,
                    'label' => $config['label'] ?? $this->get_section_default_label($section_id),
                    'order' => $config['order'] ?? 0,
                    'data' => $section_data,
                ];
            }
        }

        usort($sections, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        return new WP_REST_Response([
            'success' => true,
            'data' => $sections,
        ], 200);
    }

    /**
     * GET /content/menu/{location}
     */
    public function get_menu_content($request) {
        $location = sanitize_key($request->get_param('location'));
        $locations = get_nav_menu_locations();

        $menu_id = $locations[$location] ?? null;
        if (!$menu_id) {
            return new WP_Error('not_found', __('Menú no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        $menu_items = wp_get_nav_menu_items($menu_id);
        if (!$menu_items) {
            return new WP_REST_Response([
                'success' => true,
                'data' => ['items' => []],
            ], 200);
        }

        $items = [];
        foreach ($menu_items as $item) {
            $items[] = [
                'id' => $item->ID,
                'title' => $item->title,
                'url' => $item->url,
                'target' => $item->target,
                'parent' => (int) $item->menu_item_parent,
                'order' => $item->menu_order,
                'type' => $item->type,
                'object' => $item->object,
                'object_id' => (int) $item->object_id,
                'icon' => get_post_meta($item->ID, '_menu_item_icon', true) ?: null,
                // Datos para renderizado nativo
                'native_route' => $this->get_native_route_for_menu_item($item),
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => ['items' => $items],
        ], 200);
    }

    /**
     * Formatea un post/página para la API
     */
    private function format_post_content($post) {
        $content_blocks = $this->parse_content_blocks($post->post_content);
        $featured_image = $this->get_featured_image_data($post->ID);
        $meta = $this->get_post_meta_data($post);
        $taxonomies = $this->get_post_taxonomies($post);

        return [
            'id' => $post->ID,
            'type' => $post->post_type,
            'slug' => $post->post_name,
            'title' => get_the_title($post),
            'excerpt' => get_the_excerpt($post),
            'content' => [
                'raw' => $post->post_content,
                'rendered' => apply_filters('the_content', $post->post_content),
                'blocks' => $content_blocks,
            ],
            'featured_image' => $featured_image,
            'date' => get_the_date('c', $post),
            'modified' => get_the_modified_date('c', $post),
            'author' => $this->get_author_data($post->post_author),
            'meta' => $meta,
            'taxonomies' => $taxonomies,
            'template' => get_page_template_slug($post->ID) ?: 'default',
            'parent' => $post->post_parent ? [
                'id' => $post->post_parent,
                'title' => get_the_title($post->post_parent),
                'slug' => get_post_field('post_name', $post->post_parent),
            ] : null,
        ];
    }

    /**
     * Formatea un post como resumen (para listados)
     */
    private function format_post_summary($post) {
        return [
            'id' => $post->ID,
            'type' => $post->post_type,
            'slug' => $post->post_name,
            'title' => get_the_title($post),
            'excerpt' => get_the_excerpt($post),
            'featured_image' => $this->get_featured_image_data($post->ID),
            'date' => get_the_date('c', $post),
            'author' => $this->get_author_data($post->post_author),
            'taxonomies' => $this->get_post_taxonomies($post),
        ];
    }

    /**
     * Parsea bloques de contenido Gutenberg
     */
    private function parse_content_blocks($content) {
        if (!has_blocks($content)) {
            // Contenido clásico, lo convertimos a un bloque de párrafo
            return [
                [
                    'type' => 'classic_content',
                    'content' => $content,
                ],
            ];
        }

        $blocks = parse_blocks($content);
        $parsed = [];

        foreach ($blocks as $block) {
            if (empty($block['blockName'])) {
                continue;
            }

            $parsed_block = $this->parse_single_block($block);
            if ($parsed_block) {
                $parsed[] = $parsed_block;
            }
        }

        return $parsed;
    }

    /**
     * Parsea un bloque individual
     */
    private function parse_single_block($block) {
        $block_name = $block['blockName'];
        $attrs = $block['attrs'] ?? [];
        $inner_html = $block['innerHTML'] ?? '';

        // Mapeo de bloques a tipos nativos
        $block_mapping = [
            'core/paragraph' => 'text',
            'core/heading' => 'heading',
            'core/image' => 'image',
            'core/gallery' => 'gallery',
            'core/list' => 'list',
            'core/quote' => 'quote',
            'core/video' => 'video',
            'core/audio' => 'audio',
            'core/file' => 'file',
            'core/button' => 'button',
            'core/buttons' => 'button_group',
            'core/columns' => 'columns',
            'core/column' => 'column',
            'core/group' => 'group',
            'core/separator' => 'divider',
            'core/spacer' => 'spacer',
            'core/html' => 'html',
            'core/code' => 'code',
            'core/preformatted' => 'preformatted',
            'core/pullquote' => 'pullquote',
            'core/table' => 'table',
            'core/embed' => 'embed',
            'core/cover' => 'cover',
            'core/media-text' => 'media_text',
        ];

        $native_type = $block_mapping[$block_name] ?? 'unknown';

        $parsed = [
            'type' => $native_type,
            'block_name' => $block_name,
            'attributes' => $attrs,
        ];

        // Procesar contenido según el tipo
        switch ($native_type) {
            case 'text':
            case 'heading':
                $parsed['content'] = strip_tags($inner_html);
                $parsed['level'] = $attrs['level'] ?? 2;
                break;

            case 'image':
                $parsed['url'] = $attrs['url'] ?? '';
                $parsed['alt'] = $attrs['alt'] ?? '';
                $parsed['caption'] = $attrs['caption'] ?? '';
                $parsed['width'] = $attrs['width'] ?? null;
                $parsed['height'] = $attrs['height'] ?? null;
                if (!empty($attrs['id'])) {
                    $parsed['sizes'] = $this->get_image_sizes($attrs['id']);
                }
                break;

            case 'gallery':
                $parsed['images'] = [];
                foreach ($attrs['ids'] ?? [] as $image_id) {
                    $parsed['images'][] = $this->get_image_data($image_id);
                }
                break;

            case 'list':
                $parsed['ordered'] = $attrs['ordered'] ?? false;
                $parsed['items'] = $this->parse_list_items($inner_html);
                break;

            case 'quote':
            case 'pullquote':
                $parsed['content'] = strip_tags($inner_html, '<p><br>');
                $parsed['citation'] = $attrs['citation'] ?? '';
                break;

            case 'button':
                $parsed['text'] = $attrs['text'] ?? strip_tags($inner_html);
                $parsed['url'] = $attrs['url'] ?? '';
                $parsed['style'] = $attrs['className'] ?? 'default';
                break;

            case 'embed':
                $parsed['url'] = $attrs['url'] ?? '';
                $parsed['provider'] = $attrs['providerNameSlug'] ?? '';
                $parsed['type'] = $attrs['type'] ?? '';
                break;

            case 'cover':
                $parsed['url'] = $attrs['url'] ?? '';
                $parsed['overlay_color'] = $attrs['overlayColor'] ?? '';
                $parsed['dim_ratio'] = $attrs['dimRatio'] ?? 50;
                break;

            default:
                $parsed['html'] = $inner_html;
        }

        // Procesar bloques internos
        if (!empty($block['innerBlocks'])) {
            $parsed['children'] = [];
            foreach ($block['innerBlocks'] as $inner_block) {
                $inner_parsed = $this->parse_single_block($inner_block);
                if ($inner_parsed) {
                    $parsed['children'][] = $inner_parsed;
                }
            }
        }

        return $parsed;
    }

    /**
     * Obtiene datos de imagen destacada
     */
    private function get_featured_image_data($post_id) {
        $image_id = get_post_thumbnail_id($post_id);
        if (!$image_id) {
            return null;
        }
        return $this->get_image_data($image_id);
    }

    /**
     * Obtiene datos de una imagen
     */
    private function get_image_data($image_id) {
        $image = wp_get_attachment_image_src($image_id, 'full');
        if (!$image) {
            return null;
        }

        return [
            'id' => $image_id,
            'url' => $image[0],
            'width' => $image[1],
            'height' => $image[2],
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
            'sizes' => $this->get_image_sizes($image_id),
        ];
    }

    /**
     * Obtiene todos los tamaños de una imagen
     */
    private function get_image_sizes($image_id) {
        $sizes = ['thumbnail', 'medium', 'medium_large', 'large'];
        $result = [];

        foreach ($sizes as $size) {
            $image = wp_get_attachment_image_src($image_id, $size);
            if ($image) {
                $result[$size] = [
                    'url' => $image[0],
                    'width' => $image[1],
                    'height' => $image[2],
                ];
            }
        }

        return $result;
    }

    /**
     * Obtiene meta datos del post
     */
    private function get_post_meta_data($post) {
        $meta = [];
        $all_meta = get_post_meta($post->ID);

        // Filtrar meta privados (empiezan con _)
        foreach ($all_meta as $key => $value) {
            if (strpos($key, '_') !== 0) {
                $meta[$key] = maybe_unserialize($value[0] ?? '');
            }
        }

        // Añadir campos ACF si existe
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($post->ID);
            if ($acf_fields) {
                $meta['acf'] = $this->process_acf_fields($acf_fields);
            }
        }

        return $meta;
    }

    /**
     * Procesa campos ACF para la API
     */
    private function process_acf_fields($fields) {
        $processed = [];

        foreach ($fields as $key => $value) {
            if (is_array($value) && isset($value['ID']) && isset($value['url'])) {
                // Es una imagen
                $processed[$key] = [
                    'type' => 'image',
                    'id' => $value['ID'],
                    'url' => $value['url'],
                    'alt' => $value['alt'] ?? '',
                    'sizes' => $value['sizes'] ?? [],
                ];
            } elseif (is_array($value)) {
                $processed[$key] = $this->process_acf_fields($value);
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }

    /**
     * Obtiene taxonomías del post
     */
    private function get_post_taxonomies($post) {
        $taxonomies = get_object_taxonomies($post->post_type, 'names');
        $result = [];

        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($post->ID, $taxonomy);
            if ($terms && !is_wp_error($terms)) {
                $result[$taxonomy] = array_map(function($term) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ];
                }, $terms);
            }
        }

        return $result;
    }

    /**
     * Obtiene datos del autor
     */
    private function get_author_data($author_id) {
        $user = get_user_by('id', $author_id);
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->ID,
            'name' => $user->display_name,
            'avatar' => get_avatar_url($user->ID, ['size' => 96]),
        ];
    }

    /**
     * Obtiene datos de una sección de Info
     */
    private function get_info_section_data($section_id) {
        $app_config = get_option('flavor_apps_config', []);
        $site_info = get_option('flavor_site_info', []);
        $contact_info = get_option('flavor_contact_info', []);

        switch ($section_id) {
            case 'header':
                $logo_id = $app_config['app_logo'] ?? get_theme_mod('custom_logo');
                return [
                    'type' => 'header',
                    'name' => $app_config['app_name'] ?? get_bloginfo('name'),
                    'tagline' => $app_config['app_description'] ?? get_bloginfo('description'),
                    'logo' => $logo_id ? $this->get_image_data($logo_id) : null,
                    'cover_image' => isset($app_config['cover_image']) ? $this->get_image_data($app_config['cover_image']) : null,
                ];

            case 'about':
                return [
                    'type' => 'about',
                    'title' => __('Sobre nosotros', 'flavor-chat-ia'),
                    'content' => $site_info['about'] ?? $app_config['app_description'] ?? get_bloginfo('description'),
                    'features' => $site_info['features'] ?? [],
                ];

            case 'hours':
                return [
                    'type' => 'hours',
                    'title' => __('Horarios', 'flavor-chat-ia'),
                    'schedule' => $site_info['hours'] ?? $this->get_default_hours(),
                    'timezone' => wp_timezone_string(),
                    'special_hours' => $site_info['special_hours'] ?? [],
                ];

            case 'contact':
                return [
                    'type' => 'contact',
                    'title' => __('Contacto', 'flavor-chat-ia'),
                    'phone' => $contact_info['phone'] ?? '',
                    'email' => $contact_info['email'] ?? get_bloginfo('admin_email'),
                    'whatsapp' => $contact_info['whatsapp'] ?? '',
                    'website' => home_url(),
                ];

            case 'location':
                return [
                    'type' => 'location',
                    'title' => __('Ubicación', 'flavor-chat-ia'),
                    'address' => $app_config['business_address'] ?? '',
                    'city' => $app_config['business_city'] ?? '',
                    'country' => $app_config['business_country'] ?? '',
                    'postal_code' => $app_config['business_postal_code'] ?? '',
                    'coordinates' => [
                        'lat' => floatval($app_config['business_lat'] ?? 0),
                        'lng' => floatval($app_config['business_lng'] ?? 0),
                    ],
                    'directions_url' => $this->get_directions_url($app_config),
                ];

            case 'social':
                return [
                    'type' => 'social',
                    'title' => __('Redes sociales', 'flavor-chat-ia'),
                    'networks' => $this->get_social_networks(),
                ];

            case 'gallery':
                return [
                    'type' => 'gallery',
                    'title' => __('Galería', 'flavor-chat-ia'),
                    'images' => $this->get_gallery_images(),
                ];

            case 'services':
                return [
                    'type' => 'services',
                    'title' => __('Servicios', 'flavor-chat-ia'),
                    'items' => $site_info['services'] ?? [],
                ];

            default:
                return null;
        }
    }

    /**
     * Obtiene label por defecto de una sección
     */
    private function get_section_default_label($section_id) {
        $labels = [
            'header' => __('Cabecera', 'flavor-chat-ia'),
            'about' => __('Sobre nosotros', 'flavor-chat-ia'),
            'hours' => __('Horarios', 'flavor-chat-ia'),
            'contact' => __('Contacto', 'flavor-chat-ia'),
            'location' => __('Ubicación', 'flavor-chat-ia'),
            'social' => __('Redes sociales', 'flavor-chat-ia'),
            'gallery' => __('Galería', 'flavor-chat-ia'),
            'services' => __('Servicios', 'flavor-chat-ia'),
        ];
        return $labels[$section_id] ?? $section_id;
    }

    /**
     * Obtiene horarios por defecto
     */
    private function get_default_hours() {
        return [
            ['day' => 'monday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'tuesday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'wednesday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'thursday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'friday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
            ['day' => 'saturday', 'open' => '10:00', 'close' => '14:00', 'closed' => false],
            ['day' => 'sunday', 'open' => '', 'close' => '', 'closed' => true],
        ];
    }

    /**
     * Obtiene URL de direcciones
     */
    private function get_directions_url($config) {
        $lat = $config['business_lat'] ?? '';
        $lng = $config['business_lng'] ?? '';

        if ($lat && $lng) {
            return "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
        }

        $address = implode(', ', array_filter([
            $config['business_address'] ?? '',
            $config['business_city'] ?? '',
            $config['business_country'] ?? '',
        ]));

        return $address ? "https://www.google.com/maps/search/?api=1&query=" . urlencode($address) : '';
    }

    /**
     * Obtiene redes sociales configuradas
     */
    private function get_social_networks() {
        $site_info = get_option('flavor_site_info', []);
        $social = $site_info['social'] ?? [];

        $networks = [];
        $available = ['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok', 'whatsapp', 'telegram'];

        foreach ($available as $network) {
            if (!empty($social[$network])) {
                $networks[] = [
                    'id' => $network,
                    'url' => $social[$network],
                    'icon' => $network,
                ];
            }
        }

        return $networks;
    }

    /**
     * Obtiene imágenes de galería
     */
    private function get_gallery_images() {
        $site_info = get_option('flavor_site_info', []);
        $gallery_ids = $site_info['gallery'] ?? [];

        $images = [];
        foreach ($gallery_ids as $image_id) {
            $image_data = $this->get_image_data($image_id);
            if ($image_data) {
                $images[] = $image_data;
            }
        }

        return $images;
    }

    /**
     * Obtiene taxonomía principal de un CPT
     */
    private function get_primary_taxonomy($post_type) {
        $taxonomies = get_object_taxonomies($post_type, 'names');

        // Priorizar categorías comunes
        $priority = ['category', $post_type . '_category', $post_type . '_cat'];

        foreach ($priority as $tax) {
            if (in_array($tax, $taxonomies)) {
                return $tax;
            }
        }

        return !empty($taxonomies) ? $taxonomies[0] : 'category';
    }

    /**
     * Obtiene ruta nativa para un item de menú
     */
    private function get_native_route_for_menu_item($item) {
        // Si es una página o post, devolver la ruta nativa
        if ($item->type === 'post_type') {
            $post = get_post($item->object_id);
            if ($post) {
                return [
                    'type' => 'content',
                    'content_type' => $item->object,
                    'id' => $item->object_id,
                    'slug' => $post->post_name,
                    'api_endpoint' => rest_url(self::API_NAMESPACE . '/content/by-id/' . $item->object_id),
                ];
            }
        }

        // Si es una taxonomía
        if ($item->type === 'taxonomy') {
            return [
                'type' => 'taxonomy',
                'taxonomy' => $item->object,
                'term_id' => $item->object_id,
                'api_endpoint' => rest_url(self::API_NAMESPACE . '/content/list/' . $item->object),
            ];
        }

        // URL externa o custom
        return [
            'type' => 'external',
            'url' => $item->url,
        ];
    }

    /**
     * Parsea items de lista HTML
     */
    private function parse_list_items($html) {
        $items = [];
        preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $html, $matches);

        foreach ($matches[1] ?? [] as $item) {
            $items[] = strip_tags($item);
        }

        return $items;
    }

    /**
     * GET /screen/{screen_id}
     * Obtiene datos de una pantalla nativa del sistema
     */
    public function get_native_screen($request) {
        $screen_id = $request->get_param('screen_id');

        // Pantallas nativas del sistema
        $screen_data = $this->get_native_screen_data($screen_id);

        if (!$screen_data) {
            return new WP_Error('not_found', __('Pantalla no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $screen_data,
        ], 200);
    }

    /**
     * Obtiene datos de una pantalla nativa
     */
    private function get_native_screen_data($screen_id) {
        $app_config = get_option('flavor_apps_config', []);

        switch ($screen_id) {
            case 'info':
                // Pantalla de información del negocio
                return [
                    'screen_type' => 'info',
                    'title' => $app_config['app_name'] ?? get_bloginfo('name'),
                    'sections' => $this->get_all_info_sections_data(),
                ];

            case 'chat':
                // Pantalla de chat con IA
                return [
                    'screen_type' => 'chat',
                    'title' => __('Chat', 'flavor-chat-ia'),
                    'config' => [
                        'api_endpoint' => rest_url('flavor-chat-ia/v1/chat'),
                        'welcome_message' => __('¡Hola! ¿En qué puedo ayudarte?', 'flavor-chat-ia'),
                        'placeholder' => __('Escribe tu mensaje...', 'flavor-chat-ia'),
                    ],
                ];

            case 'reservations':
                // Pantalla de reservas
                return [
                    'screen_type' => 'reservations',
                    'title' => __('Reservar', 'flavor-chat-ia'),
                    'config' => [
                        'api_endpoint' => rest_url('chat-ia-mobile/v1/reservations'),
                        'booking_enabled' => true,
                    ],
                ];

            case 'my_tickets':
                // Pantalla de tickets del usuario
                return [
                    'screen_type' => 'my_tickets',
                    'title' => __('Mis Tickets', 'flavor-chat-ia'),
                    'config' => [
                        'api_endpoint' => rest_url('chat-ia-mobile/v1/tickets'),
                    ],
                ];

            case 'profile':
                // Pantalla de perfil de usuario
                return [
                    'screen_type' => 'profile',
                    'title' => __('Mi Perfil', 'flavor-chat-ia'),
                    'config' => [
                        'api_endpoint' => rest_url('chat-ia-mobile/v1/profile'),
                        'editable' => true,
                    ],
                ];

            case 'notifications':
                // Pantalla de notificaciones
                return [
                    'screen_type' => 'notifications',
                    'title' => __('Notificaciones', 'flavor-chat-ia'),
                    'config' => [
                        'api_endpoint' => rest_url('flavor-chat-ia/v1/notifications'),
                    ],
                ];

            case 'settings':
                // Pantalla de configuración
                return [
                    'screen_type' => 'settings',
                    'title' => __('Configuración', 'flavor-chat-ia'),
                    'options' => [
                        ['id' => 'notifications', 'label' => __('Notificaciones', 'flavor-chat-ia'), 'type' => 'toggle'],
                        ['id' => 'dark_mode', 'label' => __('Modo oscuro', 'flavor-chat-ia'), 'type' => 'toggle'],
                        ['id' => 'language', 'label' => __('Idioma', 'flavor-chat-ia'), 'type' => 'select'],
                    ],
                ];

            case 'modules':
                // Pantalla de lista de módulos
                return [
                    'screen_type' => 'modules',
                    'title' => __('Módulos', 'flavor-chat-ia'),
                    'config' => [
                        'api_endpoint' => rest_url('app-discovery/v1/modules'),
                    ],
                ];

            default:
                return null;
        }
    }

    /**
     * Obtiene todas las secciones de info como datos
     */
    private function get_all_info_sections_data() {
        $app_config = get_option('flavor_apps_config', []);
        $info_sections = $app_config['info_sections'] ?? [
            'header' => ['enabled' => true, 'order' => 0],
            'about' => ['enabled' => true, 'order' => 1],
            'hours' => ['enabled' => true, 'order' => 2],
            'contact' => ['enabled' => true, 'order' => 3],
            'location' => ['enabled' => true, 'order' => 4],
            'social' => ['enabled' => true, 'order' => 5],
        ];

        $sections = [];
        foreach ($info_sections as $section_id => $section_config) {
            if (empty($section_config['enabled'])) {
                continue;
            }

            $section_data = $this->get_info_section_data($section_id);
            if ($section_data) {
                $section_data['order'] = $section_config['order'] ?? 0;
                $sections[] = $section_data;
            }
        }

        // Ordenar por order
        usort($sections, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        return $sections;
    }

    /**
     * GET /module/{module_id}
     * Obtiene datos de un módulo del plugin
     */
    public function get_module_data($request) {
        $module_id = $request->get_param('module_id');

        $module_data = $this->get_module_screen_data($module_id);

        if (!$module_data) {
            return new WP_Error('not_found', __('Módulo no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $module_data,
        ], 200);
    }

    /**
     * Obtiene datos de pantalla de un módulo
     */
    private function get_module_screen_data($module_id) {
        // Verificar si el módulo está activo
        $settings = get_option('flavor_chat_ia_settings', []);
        $active_modules = $settings['active_modules'] ?? [];

        if (!in_array($module_id, $active_modules, true)) {
            return null;
        }

        // Obtener configuración del módulo
        $module_config = $this->get_module_config_data($module_id);

        // Generar respuesta base
        $module_data = [
            'module_id' => $module_id,
            'title' => $module_config['name'] ?? ucwords(str_replace(['_', '-'], ' ', $module_id)),
            'description' => $module_config['description'] ?? '',
            'icon' => $module_config['icon'] ?? 'extension',
            'color' => $module_config['color'] ?? '#4CAF50',
            'api_namespace' => 'flavor-chat-ia/v1/' . $module_id,
            'endpoints' => $this->get_module_endpoints($module_id),
            'config' => $module_config,
        ];

        return $module_data;
    }

    /**
     * Obtiene configuración de un módulo
     */
    private function get_module_config_data($module_id) {
        // Catálogo de módulos conocidos
        $module_catalog = [
            'grupos_consumo' => [
                'name' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'description' => __('Gestión de grupos de consumo colaborativo', 'flavor-chat-ia'),
                'icon' => 'groups',
                'color' => '#4CAF50',
            ],
            'banco_tiempo' => [
                'name' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'description' => __('Intercambio de servicios por tiempo', 'flavor-chat-ia'),
                'icon' => 'schedule',
                'color' => '#2196F3',
            ],
            'marketplace' => [
                'name' => __('Marketplace', 'flavor-chat-ia'),
                'description' => __('Compra y venta entre usuarios', 'flavor-chat-ia'),
                'icon' => 'store',
                'color' => '#FF9800',
            ],
            'eventos' => [
                'name' => __('Eventos', 'flavor-chat-ia'),
                'description' => __('Calendario de eventos', 'flavor-chat-ia'),
                'icon' => 'event',
                'color' => '#9C27B0',
            ],
            'talleres' => [
                'name' => __('Talleres', 'flavor-chat-ia'),
                'description' => __('Inscripción a talleres y cursos', 'flavor-chat-ia'),
                'icon' => 'school',
                'color' => '#3F51B5',
            ],
            'incidencias' => [
                'name' => __('Incidencias', 'flavor-chat-ia'),
                'description' => __('Reporte de incidencias', 'flavor-chat-ia'),
                'icon' => 'report_problem',
                'color' => '#F44336',
            ],
            'participacion' => [
                'name' => __('Participación', 'flavor-chat-ia'),
                'description' => __('Participación ciudadana', 'flavor-chat-ia'),
                'icon' => 'how_to_vote',
                'color' => '#00BCD4',
            ],
            'podcast' => [
                'name' => __('Podcast', 'flavor-chat-ia'),
                'description' => __('Series y episodios de podcast', 'flavor-chat-ia'),
                'icon' => 'mic',
                'color' => '#E91E63',
            ],
            'red_social' => [
                'name' => __('Red Social', 'flavor-chat-ia'),
                'description' => __('Red social de la comunidad', 'flavor-chat-ia'),
                'icon' => 'people',
                'color' => '#673AB7',
            ],
        ];

        return $module_catalog[$module_id] ?? [
            'name' => ucwords(str_replace(['_', '-'], ' ', $module_id)),
            'description' => '',
            'icon' => 'extension',
            'color' => '#607D8B',
        ];
    }

    /**
     * Obtiene los endpoints disponibles para un módulo
     */
    private function get_module_endpoints($module_id) {
        $base = 'flavor-chat-ia/v1/' . $module_id;

        // Endpoints base comunes
        return [
            'list' => rest_url($base),
            'single' => rest_url($base . '/{id}'),
            'create' => rest_url($base),
            'update' => rest_url($base . '/{id}'),
            'delete' => rest_url($base . '/{id}'),
        ];
    }
}

// Inicializar
Flavor_Native_Content_API::get_instance();
