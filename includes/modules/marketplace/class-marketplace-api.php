<?php
/**
 * API REST para Marketplace (Móvil)
 *
 * Endpoints optimizados para aplicaciones móviles
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * API REST para módulo Marketplace
 */
class Flavor_Marketplace_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-chat-ia/v1';

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
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
     * Registra las rutas REST
     */
    public function register_routes() {
        // GET /marketplace/anuncios - Lista anuncios
        register_rest_route(self::NAMESPACE, '/marketplace/anuncios', [
            'methods' => 'GET',
            'callback' => [$this, 'get_anuncios'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'busqueda' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['todos', 'regalo', 'venta', 'cambio', 'alquiler'],
                    'default' => 'todos',
                ],
                'categoria' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
                'pagina' => [
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // POST /marketplace/anuncios - Crear anuncio
        register_rest_route(self::NAMESPACE, '/marketplace/anuncios', [
            'methods' => 'POST',
            'callback' => [$this, 'crear_anuncio'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'titulo' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'descripcion' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'wp_kses_post',
                ],
                'tipo' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['regalo', 'venta', 'cambio', 'alquiler'],
                ],
                'categoria' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'precio' => [
                    'type' => 'number',
                ],
                'estado' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'ubicacion' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'imagenes' => [
                    'type' => 'array',
                ],
            ],
        ]);

        // GET /marketplace/mis-anuncios - Anuncios del usuario
        register_rest_route(self::NAMESPACE, '/marketplace/mis-anuncios', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_anuncios'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'estado' => [
                    'type' => 'string',
                    'default' => 'publish',
                ],
            ],
        ]);

        // GET /marketplace/anuncios/{id} - Detalle de anuncio
        register_rest_route(self::NAMESPACE, '/marketplace/anuncios/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_anuncio'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // PUT /marketplace/anuncios/{id} - Actualizar anuncio
        register_rest_route(self::NAMESPACE, '/marketplace/anuncios/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'actualizar_anuncio'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // DELETE /marketplace/anuncios/{id} - Eliminar anuncio
        register_rest_route(self::NAMESPACE, '/marketplace/anuncios/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'eliminar_anuncio'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /marketplace/anuncio - Crear anuncio (singular, alias para apps)
        register_rest_route(self::NAMESPACE, '/marketplace/anuncio', [
            'methods' => 'POST',
            'callback' => [$this, 'crear_anuncio'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // PUT /marketplace/anuncio/{id} - Actualizar anuncio (singular, para apps)
        register_rest_route(self::NAMESPACE, '/marketplace/anuncio/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'actualizar_anuncio'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /marketplace/categorias - Lista de categorías
        register_rest_route(self::NAMESPACE, '/marketplace/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categorias'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // POST /marketplace/anuncios/{id}/contactar - Contactar con vendedor
        register_rest_route(self::NAMESPACE, '/marketplace/anuncios/(?P<id>\d+)/contactar', [
            'methods' => 'POST',
            'callback' => [$this, 'contactar_vendedor'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'mensaje' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        // POST /marketplace/anuncios/{id}/marcar-vendido - Marcar como vendido
        register_rest_route(self::NAMESPACE, '/marketplace/anuncios/(?P<id>\d+)/marcar-vendido', [
            'methods' => 'POST',
            'callback' => [$this, 'marcar_vendido'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    /**
     * GET /marketplace/anuncios
     * Lista anuncios públicos
     */
    public function get_anuncios($request) {
        $busqueda = $request->get_param('busqueda');
        $tipo = $request->get_param('tipo');
        $categoria = $request->get_param('categoria');
        $limite = $request->get_param('limite');
        $pagina = $request->get_param('pagina');

        $args = [
            'post_type' => 'marketplace_item',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'paged' => $pagina,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Búsqueda por texto
        if (!empty($busqueda)) {
            $args['s'] = $busqueda;
        }

        // Filtro por tipo
        $tax_query = [];
        if (!empty($tipo) && $tipo !== 'todos') {
            $tax_query[] = [
                'taxonomy' => 'marketplace_tipo',
                'field' => 'slug',
                'terms' => $tipo,
            ];
        }

        // Filtro por categoría
        if (!empty($categoria) && $categoria !== 'todos') {
            $tax_query[] = [
                'taxonomy' => 'marketplace_categoria',
                'field' => 'slug',
                'terms' => $categoria,
            ];
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);

        $anuncios = array_map([$this, 'formatear_anuncio'], $query->posts);

        $respuesta = [
            'success' => true,
            'anuncios' => $anuncios,
            'total' => $query->found_posts,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => $query->max_num_pages,
        ];

        return new WP_REST_Response($this->sanitize_public_marketplace_response($respuesta), 200);
    }

    /**
     * POST /marketplace/anuncios
     * Crear nuevo anuncio
     */
    public function crear_anuncio($request) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return new WP_Error(
                'no_auth',
                'Debes iniciar sesión',
                ['status' => 401]
            );
        }

        $titulo = $request->get_param('titulo');
        $descripcion = $request->get_param('descripcion');
        $tipo = $request->get_param('tipo');
        $categoria = $request->get_param('categoria');
        $precio = $request->get_param('precio');
        $estado = $request->get_param('estado');
        $ubicacion = $request->get_param('ubicacion');
        $imagenes = $request->get_param('imagenes');

        // Validaciones
        if (empty($titulo) || empty($descripcion)) {
            return new WP_Error(
                'datos_incompletos',
                'Título y descripción son obligatorios',
                ['status' => 400]
            );
        }

        // Crear post
        $post_data = [
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_type' => 'marketplace_item',
            'post_status' => 'publish',
            'post_author' => $usuario_id,
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return new WP_Error(
                'error_crear',
                'Error al crear el anuncio',
                ['status' => 500]
            );
        }

        // Asignar tipo
        if (!empty($tipo)) {
            wp_set_object_terms($post_id, $tipo, 'marketplace_tipo');
        }

        // Asignar categoría
        if (!empty($categoria)) {
            wp_set_object_terms($post_id, $categoria, 'marketplace_categoria');
        }

        // Guardar meta datos
        if ($precio !== null) {
            update_post_meta($post_id, '_marketplace_precio', floatval($precio));
        }
        if (!empty($estado)) {
            update_post_meta($post_id, '_marketplace_estado', $estado);
        }
        if (!empty($ubicacion)) {
            update_post_meta($post_id, '_marketplace_ubicacion', $ubicacion);
        }

        // Calcular fecha de expiración (30 días por defecto)
        $dias_expiracion = 30;
        $fecha_expiracion = date('Y-m-d', strtotime("+$dias_expiracion days"));
        update_post_meta($post_id, '_marketplace_fecha_expiracion', $fecha_expiracion);

        $this->procesar_imagenes($imagenes, $post_id);

        $post = get_post($post_id);
        $anuncio = $this->formatear_anuncio($post);

        return new WP_REST_Response([
            'success' => true,
            'anuncio' => $anuncio,
            'mensaje' => __('Anuncio publicado con éxito', 'flavor-chat-ia'),
        ], 201);
    }

    /**
     * GET /marketplace/mis-anuncios
     * Anuncios del usuario
     */
    public function get_mis_anuncios($request) {
        $usuario_id = get_current_user_id();
        $estado = $request->get_param('estado');

        $args = [
            'post_type' => 'marketplace_item',
            'author' => $usuario_id,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ($estado !== 'todos') {
            $args['post_status'] = $estado;
        } else {
            $args['post_status'] = ['publish', 'draft', 'pending'];
        }

        $query = new WP_Query($args);
        $anuncios = array_map([$this, 'formatear_anuncio'], $query->posts);

        return new WP_REST_Response([
            'success' => true,
            'anuncios' => $anuncios,
            'total' => count($anuncios),
        ], 200);
    }

    /**
     * GET /marketplace/anuncios/{id}
     * Detalle de un anuncio
     */
    public function get_anuncio($request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'marketplace_item') {
            return new WP_Error(
                'no_encontrado',
                'Anuncio no encontrado',
                ['status' => 404]
            );
        }

        // Incrementar contador de vistas
        $vistas = get_post_meta($post_id, '_marketplace_vistas', true);
        update_post_meta($post_id, '_marketplace_vistas', intval($vistas) + 1);

        $respuesta = [
            'success' => true,
            'anuncio' => $this->formatear_anuncio($post, true), // true = detalle completo
        ];

        return new WP_REST_Response($this->sanitize_public_marketplace_response($respuesta), 200);
    }

    /**
     * PUT /marketplace/anuncios/{id}
     * Actualizar anuncio
     */
    public function actualizar_anuncio($request) {
        $usuario_id = get_current_user_id();
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'marketplace_item') {
            return new WP_Error(
                'no_encontrado',
                'Anuncio no encontrado',
                ['status' => 404]
            );
        }

        // Verificar que es el autor
        if ($post->post_author != $usuario_id) {
            return new WP_Error(
                'sin_permiso',
                'No tienes permiso para editar este anuncio',
                ['status' => 403]
            );
        }

        // Actualizar datos
        $post_data = ['ID' => $post_id];

        if ($request->has_param('titulo')) {
            $post_data['post_title'] = $request->get_param('titulo');
        }
        if ($request->has_param('descripcion')) {
            $post_data['post_content'] = $request->get_param('descripcion');
        }

        wp_update_post($post_data);

        // Actualizar taxonomías y meta
        if ($request->has_param('tipo')) {
            wp_set_object_terms($post_id, $request->get_param('tipo'), 'marketplace_tipo');
        }
        if ($request->has_param('categoria')) {
            wp_set_object_terms($post_id, $request->get_param('categoria'), 'marketplace_categoria');
        }
        if ($request->has_param('precio')) {
            update_post_meta($post_id, '_marketplace_precio', floatval($request->get_param('precio')));
        }
        if ($request->has_param('estado')) {
            update_post_meta($post_id, '_marketplace_estado', $request->get_param('estado'));
        }
        if ($request->has_param('ubicacion')) {
            update_post_meta($post_id, '_marketplace_ubicacion', $request->get_param('ubicacion'));
        }

        $post = get_post($post_id);

        return new WP_REST_Response([
            'success' => true,
            'anuncio' => $this->formatear_anuncio($post),
            'mensaje' => __('Anuncio actualizado con éxito', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * DELETE /marketplace/anuncios/{id}
     * Eliminar anuncio
     */
    public function eliminar_anuncio($request) {
        $usuario_id = get_current_user_id();
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'marketplace_item') {
            return new WP_Error(
                'no_encontrado',
                'Anuncio no encontrado',
                ['status' => 404]
            );
        }

        // Verificar que es el autor
        if ($post->post_author != $usuario_id && !current_user_can('delete_others_posts')) {
            return new WP_Error(
                'sin_permiso',
                'No tienes permiso para eliminar este anuncio',
                ['status' => 403]
            );
        }

        // Mover a papelera en lugar de eliminar
        wp_trash_post($post_id);

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Anuncio eliminado con éxito', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * GET /marketplace/categorias
     * Lista de categorías
     */
    public function get_categorias($request) {
        $categorias = get_terms([
            'taxonomy' => 'marketplace_categoria',
            'hide_empty' => false,
        ]);

        $categorias_formateadas = array_map(function($cat) {
            return [
                'id' => $cat->term_id,
                'slug' => $cat->slug,
                'nombre' => $cat->name,
                'descripcion' => $cat->description,
                'count' => $cat->count,
            ];
        }, $categorias);

        return new WP_REST_Response([
            'success' => true,
            'categorias' => $categorias_formateadas,
        ], 200);
    }

    private function sanitize_public_marketplace_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['anuncios']) && is_array($respuesta['anuncios'])) {
            $respuesta['anuncios'] = array_map([$this, 'sanitize_public_anuncio'], $respuesta['anuncios']);
        }

        if (!empty($respuesta['anuncio']) && is_array($respuesta['anuncio'])) {
            $respuesta['anuncio'] = $this->sanitize_public_anuncio($respuesta['anuncio']);
        }

        return $respuesta;
    }

    private function sanitize_public_anuncio($anuncio) {
        if (!is_array($anuncio)) {
            return $anuncio;
        }

        if (!empty($anuncio['autor']) && is_array($anuncio['autor'])) {
            unset($anuncio['autor']['id']);
        }

        $anuncio['ubicacion'] = '';

        return $anuncio;
    }

    /**
     * POST /marketplace/anuncios/{id}/contactar
     * Contactar con el vendedor
     */
    public function contactar_vendedor($request) {
        $usuario_id = get_current_user_id();
        $post_id = $request->get_param('id');
        $mensaje = $request->get_param('mensaje');

        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'marketplace_item') {
            return new WP_Error(
                'no_encontrado',
                'Anuncio no encontrado',
                ['status' => 404]
            );
        }

        // No puedes contactar tu propio anuncio
        if ($post->post_author == $usuario_id) {
            return new WP_Error(
                'anuncio_propio',
                'No puedes contactar tu propio anuncio',
                ['status' => 400]
            );
        }

        $comment_data = [
            'comment_post_ID' => $post_id,
            'comment_author' => wp_get_current_user()->display_name,
            'comment_author_email' => wp_get_current_user()->user_email,
            'comment_content' => $mensaje,
            'comment_type' => 'marketplace_contacto',
            'comment_approved' => 1,
            'user_id' => $usuario_id,
        ];

        $comment_id = wp_insert_comment($comment_data);

        $vendedor_email = get_the_author_meta('user_email', $post->post_author);
        if ($vendedor_email) {
            $asunto = sprintf(__('Nuevo mensaje sobre tu anuncio "%s"', 'flavor-chat-ia'), $post->post_title);
            $contenido = sprintf(
                __("Hola,\n\nHas recibido un mensaje sobre tu anuncio \"%s\".\n\nMensaje:\n%s\n\nVer anuncio: %s\n", 'flavor-chat-ia'),
                $post->post_title,
                $mensaje,
                get_permalink($post_id)
            );
            wp_mail($vendedor_email, $asunto, $contenido);
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Anuncio publicado con éxito', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * POST /marketplace/anuncios/{id}/marcar-vendido
     * Marcar anuncio como vendido
     */
    public function marcar_vendido($request) {
        $usuario_id = get_current_user_id();
        $post_id = $request->get_param('id');
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'marketplace_item') {
            return new WP_Error(
                'no_encontrado',
                'Anuncio no encontrado',
                ['status' => 404]
            );
        }

        // Solo el autor puede marcar como vendido
        if ($post->post_author != $usuario_id) {
            return new WP_Error(
                'sin_permiso',
                'Solo el autor puede marcar el anuncio como vendido',
                ['status' => 403]
            );
        }

        update_post_meta($post_id, '_marketplace_vendido', true);
        update_post_meta($post_id, '_marketplace_fecha_venta', current_time('mysql'));

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Anuncio publicado con éxito', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * Formatea un anuncio para la respuesta
     */
    private function formatear_anuncio($post, $detalle_completo = false) {
        if (!$post) {
            return null;
        }

        $autor = get_userdata($post->post_author);

        // Obtener taxonomías
        $tipos = wp_get_post_terms($post->ID, 'marketplace_tipo', ['fields' => 'slugs']);
        $categorias = wp_get_post_terms($post->ID, 'marketplace_categoria', ['fields' => 'all']);

        // Meta datos
        $precio = get_post_meta($post->ID, '_marketplace_precio', true);
        $estado = get_post_meta($post->ID, '_marketplace_estado', true);
        $ubicacion = get_post_meta($post->ID, '_marketplace_ubicacion', true);
        $vendido = get_post_meta($post->ID, '_marketplace_vendido', true);
        $vistas = get_post_meta($post->ID, '_marketplace_vistas', true);

        // Imagen destacada
        $imagen_url = get_the_post_thumbnail_url($post->ID, 'medium');

        $anuncio = [
            'id' => $post->ID,
            'titulo' => $post->post_title,
            'descripcion' => $detalle_completo ? $post->post_content : wp_trim_words($post->post_content, 20),
            'tipo' => !empty($tipos) ? $tipos[0] : '',
            'categoria' => !empty($categorias) ? [
                'id' => $categorias[0]->term_id,
                'slug' => $categorias[0]->slug,
                'nombre' => $categorias[0]->name,
            ] : null,
            'precio' => $precio ? floatval($precio) : null,
            'estado' => $estado,
            'ubicacion' => $ubicacion,
            'vendido' => (bool) $vendido,
            'imagen' => $imagen_url ? $imagen_url : '',
            'fecha_publicacion' => mysql2date('c', $post->post_date),
            'autor' => [
                'id' => $post->post_author,
                'nombre' => $autor ? $autor->display_name : 'Usuario',
            ],
        ];

        // Datos adicionales solo en detalle completo
        if ($detalle_completo) {
            $anuncio['vistas'] = intval($vistas);
            $anuncio['fecha_modificacion'] = mysql2date('c', $post->post_modified);

            // Galería de imágenes (si hay)
            $imagenes = [];
            $attachment_ids = get_children([
                'post_parent' => $post->ID,
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'orderby' => 'menu_order',
                'order' => 'ASC',
            ]);

            foreach ($attachment_ids as $attachment) {
                $imagenes[] = wp_get_attachment_url($attachment->ID);
            }

            $anuncio['imagenes'] = $imagenes;
        }

        return $anuncio;
    }

    private function procesar_imagenes($imagenes, $post_id) {
        if (empty($imagenes)) {
            return;
        }

        if (is_string($imagenes)) {
            $decoded = json_decode($imagenes, true);
            if (is_array($decoded)) {
                $imagenes = $decoded;
            } else {
                $imagenes = array_filter(array_map('trim', explode(',', $imagenes)));
            }
        }

        if (!is_array($imagenes)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_ids = [];
        $mime_map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        foreach ($imagenes as $imagen) {
            if (is_numeric($imagen)) {
                $attachment_id = absint($imagen);
                if ($attachment_id) {
                    wp_update_post(['ID' => $attachment_id, 'post_parent' => $post_id]);
                    $attachment_ids[] = $attachment_id;
                }
                continue;
            }

            if (!is_string($imagen)) {
                continue;
            }

            $imagen = trim($imagen);
            if ($imagen === '') {
                continue;
            }

            $attachment_id = 0;

            if (strpos($imagen, 'data:image') === 0) {
                $parts = explode(',', $imagen, 2);
                if (count($parts) === 2) {
                    $header = $parts[0];
                    $data = base64_decode($parts[1]);
                    preg_match('/data:(image\/[a-zA-Z0-9+.-]+);base64/', $header, $matches);
                    $mime = $matches[1] ?? 'image/jpeg';
                    $ext = $mime_map[$mime] ?? 'jpg';
                    $filename = 'marketplace-' . $post_id . '-' . wp_generate_password(6, false) . '.' . $ext;

                    $upload = wp_upload_bits($filename, null, $data);
                    if (empty($upload['error'])) {
                        $attachment_id = wp_insert_attachment([
                            'post_mime_type' => $mime,
                            'post_title' => sanitize_file_name($filename),
                            'post_content' => '',
                            'post_status' => 'inherit',
                            'post_parent' => $post_id,
                        ], $upload['file'], $post_id);

                        if (!is_wp_error($attachment_id)) {
                            $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                            wp_update_attachment_metadata($attachment_id, $metadata);
                        } else {
                            $attachment_id = 0;
                        }
                    }
                }
            } elseif (filter_var($imagen, FILTER_VALIDATE_URL)) {
                $tmp = download_url($imagen);
                if (!is_wp_error($tmp)) {
                    $file_array = [
                        'name' => basename(parse_url($imagen, PHP_URL_PATH)),
                        'tmp_name' => $tmp,
                    ];
                    $attachment_id = media_handle_sideload($file_array, $post_id);
                    if (is_wp_error($attachment_id)) {
                        @unlink($tmp);
                        $attachment_id = 0;
                    }
                }
            }

            if ($attachment_id) {
                $attachment_ids[] = $attachment_id;
            }
        }

        if (!empty($attachment_ids)) {
            set_post_thumbnail($post_id, $attachment_ids[0]);
            update_post_meta($post_id, '_marketplace_gallery', $attachment_ids);
        }
    }

    /**
     * Verifica autenticación
     */
    public function check_authentication($request) {
        return is_user_logged_in();
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}
