<?php
/**
 * Visual Builder Pro - Sistema de Comentarios Colaborativos
 *
 * Permite a los usuarios dejar comentarios en elementos del canvas
 * para facilitar la colaboración y revisión de diseños.
 *
 * @package FlavorPlatform
 * @subpackage Visual_Builder_Pro
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar comentarios en el Visual Builder Pro
 */
class Flavor_VBP_Comments {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Comments|null
     */
    private static $instancia = null;

    /**
     * Meta key para almacenar comentarios
     *
     * @var string
     */
    const META_KEY = '_vbp_comments';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Comments
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->register_rest_routes();
    }

    /**
     * Registra rutas REST
     */
    private function register_rest_routes() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registra las rutas REST de comentarios
     */
    public function register_routes() {
        $namespace = 'flavor-vbp/v1';

        // Obtener comentarios de una página
        register_rest_route(
            $namespace,
            '/comments/(?P<post_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_comments' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'post_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Añadir comentario
        register_rest_route(
            $namespace,
            '/comments/(?P<post_id>\d+)',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'add_comment' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'post_id'    => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'element_id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'content'    => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'position'   => array(
                        'required'          => false,
                        'type'              => 'object',
                        'default'           => array( 'x' => 0, 'y' => 0 ),
                    ),
                    'parent_id'  => array(
                        'required'          => false,
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Actualizar comentario
        register_rest_route(
            $namespace,
            '/comments/(?P<post_id>\d+)/(?P<comment_id>[a-zA-Z0-9_-]+)',
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_comment' ),
                'permission_callback' => array( $this, 'check_comment_permission' ),
                'args'                => array(
                    'post_id'    => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'comment_id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'content'    => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'resolved'   => array(
                        'required'          => false,
                        'type'              => 'boolean',
                    ),
                ),
            )
        );

        // Eliminar comentario
        register_rest_route(
            $namespace,
            '/comments/(?P<post_id>\d+)/(?P<comment_id>[a-zA-Z0-9_-]+)',
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_comment' ),
                'permission_callback' => array( $this, 'check_comment_permission' ),
                'args'                => array(
                    'post_id'    => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'comment_id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Obtener estadísticas de comentarios
        register_rest_route(
            $namespace,
            '/comments/(?P<post_id>\d+)/stats',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_stats' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'post_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );
    }

    /**
     * Verifica permisos generales
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verifica permisos para modificar un comentario específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return bool
     */
    public function check_comment_permission( $request ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return false;
        }

        // Admins pueden modificar cualquier comentario
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Verificar si el usuario es el autor del comentario
        $post_id = $request->get_param( 'post_id' );
        $comment_id = $request->get_param( 'comment_id' );
        $comments = $this->get_comments_array( $post_id );

        foreach ( $comments as $comment ) {
            if ( $comment['id'] === $comment_id ) {
                return (int) $comment['user_id'] === get_current_user_id();
            }
        }

        return false;
    }

    /**
     * Obtiene los comentarios de un post
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_comments( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $comments = $this->get_comments_array( $post_id );

        // Enriquecer con datos de usuario
        $enriched_comments = array_map( array( $this, 'enrich_comment' ), $comments );

        // Agrupar por elemento
        $grouped = $this->group_by_element( $enriched_comments );

        return new WP_REST_Response(
            array(
                'success'  => true,
                'comments' => $enriched_comments,
                'grouped'  => $grouped,
                'count'    => count( $enriched_comments ),
            ),
            200
        );
    }

    /**
     * Añade un comentario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function add_comment( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $element_id = $request->get_param( 'element_id' );
        $content = $request->get_param( 'content' );
        $position = $request->get_param( 'position' );
        $parent_id = $request->get_param( 'parent_id' );

        // Validar que el post existe
        if ( ! get_post( $post_id ) ) {
            return new WP_Error(
                'invalid_post',
                __( 'El post no existe', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        // Crear comentario
        $comment_id = 'comment_' . uniqid();
        $current_user = wp_get_current_user();

        $new_comment = array(
            'id'         => $comment_id,
            'element_id' => $element_id,
            'content'    => $content,
            'position'   => array(
                'x' => isset( $position['x'] ) ? floatval( $position['x'] ) : 0,
                'y' => isset( $position['y'] ) ? floatval( $position['y'] ) : 0,
            ),
            'user_id'    => get_current_user_id(),
            'parent_id'  => $parent_id,
            'resolved'   => false,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
        );

        // Obtener comentarios existentes y añadir el nuevo
        $comments = $this->get_comments_array( $post_id );
        $comments[] = $new_comment;

        // Guardar
        update_post_meta( $post_id, self::META_KEY, $comments );

        // Enriquecer para la respuesta
        $enriched_comment = $this->enrich_comment( $new_comment );

        return new WP_REST_Response(
            array(
                'success' => true,
                'comment' => $enriched_comment,
                'message' => __( 'Comentario añadido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            201
        );
    }

    /**
     * Actualiza un comentario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function update_comment( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $comment_id = $request->get_param( 'comment_id' );
        $content = $request->get_param( 'content' );
        $resolved = $request->get_param( 'resolved' );

        $comments = $this->get_comments_array( $post_id );
        $found = false;
        $updated_comment = null;

        foreach ( $comments as &$comment ) {
            if ( $comment['id'] === $comment_id ) {
                if ( null !== $content ) {
                    $comment['content'] = $content;
                }
                if ( null !== $resolved ) {
                    $comment['resolved'] = (bool) $resolved;
                    if ( $resolved ) {
                        $comment['resolved_by'] = get_current_user_id();
                        $comment['resolved_at'] = current_time( 'mysql' );
                    } else {
                        unset( $comment['resolved_by'], $comment['resolved_at'] );
                    }
                }
                $comment['updated_at'] = current_time( 'mysql' );
                $updated_comment = $comment;
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_Error(
                'comment_not_found',
                __( 'Comentario no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        update_post_meta( $post_id, self::META_KEY, $comments );

        return new WP_REST_Response(
            array(
                'success' => true,
                'comment' => $this->enrich_comment( $updated_comment ),
                'message' => __( 'Comentario actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            200
        );
    }

    /**
     * Elimina un comentario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_comment( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $comment_id = $request->get_param( 'comment_id' );

        $comments = $this->get_comments_array( $post_id );
        $original_count = count( $comments );

        // Eliminar comentario y sus respuestas
        $comments = array_filter(
            $comments,
            function ( $comment ) use ( $comment_id ) {
                return $comment['id'] !== $comment_id && $comment['parent_id'] !== $comment_id;
            }
        );

        if ( count( $comments ) === $original_count ) {
            return new WP_Error(
                'comment_not_found',
                __( 'Comentario no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        // Reindexar y guardar
        $comments = array_values( $comments );
        update_post_meta( $post_id, self::META_KEY, $comments );

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __( 'Comentario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            200
        );
    }

    /**
     * Obtiene estadísticas de comentarios
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_stats( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $comments = $this->get_comments_array( $post_id );

        $total = count( $comments );
        $resolved = count(
            array_filter(
                $comments,
                function ( $comment ) {
                    return ! empty( $comment['resolved'] );
                }
            )
        );
        $pending = $total - $resolved;
        $threads = count(
            array_filter(
                $comments,
                function ( $comment ) {
                    return empty( $comment['parent_id'] );
                }
            )
        );

        // Comentarios por elemento
        $by_element = array();
        foreach ( $comments as $comment ) {
            $element_id = $comment['element_id'];
            if ( ! isset( $by_element[ $element_id ] ) ) {
                $by_element[ $element_id ] = 0;
            }
            $by_element[ $element_id ]++;
        }

        return new WP_REST_Response(
            array(
                'success'    => true,
                'stats'      => array(
                    'total'      => $total,
                    'resolved'   => $resolved,
                    'pending'    => $pending,
                    'threads'    => $threads,
                    'by_element' => $by_element,
                ),
            ),
            200
        );
    }

    /**
     * Obtiene el array de comentarios de un post
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function get_comments_array( $post_id ) {
        $comments = get_post_meta( $post_id, self::META_KEY, true );
        return is_array( $comments ) ? $comments : array();
    }

    /**
     * Enriquece un comentario con datos de usuario
     *
     * @param array $comment Comentario.
     * @return array
     */
    private function enrich_comment( $comment ) {
        $user = get_user_by( 'id', $comment['user_id'] );

        $comment['author'] = array(
            'id'           => $comment['user_id'],
            'name'         => $user ? $user->display_name : __( 'Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'avatar'       => get_avatar_url( $comment['user_id'], array( 'size' => 48 ) ),
            'initials'     => $user ? strtoupper( substr( $user->display_name, 0, 2 ) ) : '??',
        );

        // Añadir info del que resolvió si está resuelto
        if ( ! empty( $comment['resolved_by'] ) ) {
            $resolved_user = get_user_by( 'id', $comment['resolved_by'] );
            $comment['resolved_by_name'] = $resolved_user ? $resolved_user->display_name : '';
        }

        // Formatear fechas
        $comment['created_ago'] = human_time_diff( strtotime( $comment['created_at'] ), current_time( 'timestamp' ) );
        if ( ! empty( $comment['updated_at'] ) && $comment['updated_at'] !== $comment['created_at'] ) {
            $comment['updated_ago'] = human_time_diff( strtotime( $comment['updated_at'] ), current_time( 'timestamp' ) );
        }

        return $comment;
    }

    /**
     * Agrupa comentarios por elemento
     *
     * @param array $comments Comentarios.
     * @return array
     */
    private function group_by_element( $comments ) {
        $grouped = array();

        foreach ( $comments as $comment ) {
            $element_id = $comment['element_id'];
            if ( ! isset( $grouped[ $element_id ] ) ) {
                $grouped[ $element_id ] = array(
                    'element_id' => $element_id,
                    'threads'    => array(),
                    'count'      => 0,
                    'pending'    => 0,
                );
            }

            // Es un hilo principal (no tiene parent)
            if ( empty( $comment['parent_id'] ) ) {
                $grouped[ $element_id ]['threads'][ $comment['id'] ] = array(
                    'comment'  => $comment,
                    'replies'  => array(),
                );
            }

            $grouped[ $element_id ]['count']++;
            if ( empty( $comment['resolved'] ) ) {
                $grouped[ $element_id ]['pending']++;
            }
        }

        // Añadir respuestas a sus hilos
        foreach ( $comments as $comment ) {
            if ( ! empty( $comment['parent_id'] ) ) {
                $element_id = $comment['element_id'];
                $parent_id = $comment['parent_id'];
                if ( isset( $grouped[ $element_id ]['threads'][ $parent_id ] ) ) {
                    $grouped[ $element_id ]['threads'][ $parent_id ]['replies'][] = $comment;
                }
            }
        }

        // Convertir threads a array indexado
        foreach ( $grouped as &$group ) {
            $group['threads'] = array_values( $group['threads'] );
        }

        return $grouped;
    }
}

// Inicializar
Flavor_VBP_Comments::get_instance();
