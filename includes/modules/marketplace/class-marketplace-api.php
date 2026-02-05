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
            'permission_callback' => '__return_true',
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
            'permission_callback' => '__return_true',
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

        // GET /marketplace/categorias - Lista de categorías
        register_rest_route(self::NAMESPACE, '/marketplace/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categorias'],
            'permission_callback' => '__return_true',
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

        return new WP_REST_Response([
            'success' => true,
            'anuncios' => $anuncios,
            'total' => $query->found_posts,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => $query->max_num_pages,
        ], 200);
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

        // TODO: Procesar imágenes si se envían

        $post = get_post($post_id);
        $anuncio = $this->formatear_anuncio($post);

        return new WP_REST_Response([
            'success' => true,
            'anuncio' => $anuncio,
            'mensaje' => 'Anuncio publicado con éxito',
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

        return new WP_REST_Response([
            'success' => true,
            'anuncio' => $this->formatear_anuncio($post, true), // true = detalle completo
        ], 200);
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
            'mensaje' => 'Anuncio actualizado con éxito',
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
            'mensaje' => 'Anuncio eliminado con éxito',
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

        // TODO: Enviar notificación o email al vendedor
        // Por ahora, guardamos el mensaje como comentario

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

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Mensaje enviado al vendedor',
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
            'mensaje' => 'Anuncio marcado como vendido',
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

    /**
     * Verifica autenticación
     */
    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
