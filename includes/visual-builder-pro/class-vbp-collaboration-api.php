<?php
/**
 * Visual Builder Pro - Collaboration API
 *
 * API REST para colaboración en tiempo real
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para manejar la API de colaboración
 */
class Flavor_VBP_Collaboration_API {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    private $namespace = 'flavor-vbp/v1';

    /**
     * Transient prefix para presencia
     */
    private $presence_prefix = 'vbp_presence_';

    /**
     * Tiempo de expiración de presencia (segundos)
     */
    private $presence_ttl = 30;

    /**
     * Obtener instancia singleton
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
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_filter( 'heartbeat_received', array( $this, 'heartbeat_received' ), 10, 2 );
        add_filter( 'heartbeat_send', array( $this, 'heartbeat_send' ), 10, 2 );
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // Presencia
        register_rest_route( $this->namespace, '/collaboration/presence/(?P<post_id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_presence' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/collaboration/presence', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'update_presence' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/collaboration/leave', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'leave_presence' ),
            'permission_callback' => '__return_true', // Permitir sin autenticación para sendBeacon
        ) );

        // Comentarios
        register_rest_route( $this->namespace, '/collaboration/comments/(?P<post_id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_comments' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/collaboration/comments', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'add_comment' ),
            'permission_callback' => array( $this, 'check_comment_permission' ),
        ) );

        register_rest_route( $this->namespace, '/collaboration/comments/(?P<comment_id>\d+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_comment' ),
            'permission_callback' => array( $this, 'check_comment_permission' ),
        ) );

        register_rest_route( $this->namespace, '/collaboration/comments/(?P<comment_id>\d+)/reply', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'reply_comment' ),
            'permission_callback' => array( $this, 'check_comment_permission' ),
        ) );

        register_rest_route( $this->namespace, '/collaboration/comments/(?P<comment_id>\d+)/resolve', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'resolve_comment' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );
    }

    /**
     * Verificar permisos de acceso
     */
    public function check_permission( $request ) {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verificar permisos de comentario
     */
    public function check_comment_permission( $request ) {
        return current_user_can( 'read' );
    }

    // ============ PRESENCIA ============

    /**
     * Obtener usuarios activos en un documento
     */
    public function get_presence( $request ) {
        $post_id = absint( $request['post_id'] );

        $presence = $this->get_document_presence( $post_id );

        return rest_ensure_response( array(
            'success' => true,
            'users'   => $presence['users'],
            'cursors' => $presence['cursors'],
        ) );
    }

    /**
     * Actualizar presencia del usuario
     */
    public function update_presence( $request ) {
        $user_id = get_current_user_id();
        $post_id = absint( $request->get_param( 'post_id' ) );
        $cursor  = $request->get_param( 'cursor' );
        $editing = $request->get_param( 'editing_element' );

        if ( ! $user_id || ! $post_id ) {
            return rest_ensure_response( array( 'success' => false ) );
        }

        $user = get_userdata( $user_id );

        $presence_data = array(
            'user_id'         => $user_id,
            'display_name'    => $user->display_name,
            'avatar'          => get_avatar_url( $user_id, array( 'size' => 32 ) ),
            'email_hash'      => md5( strtolower( trim( $user->user_email ) ) ),
            'cursor'          => $cursor,
            'editing_element' => $editing,
            'last_active'     => time(),
        );

        // Guardar presencia del usuario
        $transient_key = $this->presence_prefix . $post_id . '_' . $user_id;
        set_transient( $transient_key, $presence_data, $this->presence_ttl );

        // Obtener todos los usuarios activos
        $presence = $this->get_document_presence( $post_id );

        return rest_ensure_response( array(
            'success' => true,
            'users'   => $presence['users'],
            'cursors' => $presence['cursors'],
        ) );
    }

    /**
     * Remover presencia al salir
     */
    public function leave_presence( $request ) {
        $post_id = absint( $request->get_param( 'post_id' ) );
        $user_id = get_current_user_id();

        if ( $post_id && $user_id ) {
            $transient_key = $this->presence_prefix . $post_id . '_' . $user_id;
            delete_transient( $transient_key );
        }

        return rest_ensure_response( array( 'success' => true ) );
    }

    /**
     * Obtener presencia de un documento
     */
    private function get_document_presence( $post_id ) {
        global $wpdb;

        $users   = array();
        $cursors = array();

        // Buscar todos los transients de presencia para este documento
        $prefix  = '_transient_' . $this->presence_prefix . $post_id . '_';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                WHERE option_name LIKE %s",
                $prefix . '%'
            )
        );

        $current_time = time();

        foreach ( $results as $row ) {
            $data = maybe_unserialize( $row->option_value );

            if ( is_array( $data ) && isset( $data['last_active'] ) ) {
                // Verificar que no ha expirado
                if ( ( $current_time - $data['last_active'] ) < $this->presence_ttl ) {
                    $users[] = array(
                        'id'              => $data['user_id'],
                        'name'            => $data['display_name'],
                        'avatar'          => $data['avatar'],
                        'email_hash'      => $data['email_hash'],
                        'editing_element' => $data['editing_element'],
                    );

                    if ( ! empty( $data['cursor'] ) ) {
                        $cursors[ $data['user_id'] ] = $data['cursor'];
                    }
                }
            }
        }

        return array(
            'users'   => $users,
            'cursors' => $cursors,
        );
    }

    // ============ COMENTARIOS ============

    /**
     * Obtener comentarios de un documento
     */
    public function get_comments( $request ) {
        $post_id = absint( $request['post_id'] );

        $comments = get_post_meta( $post_id, '_vbp_comments', true );

        if ( ! is_array( $comments ) ) {
            $comments = array();
        }

        // Enriquecer con datos de usuario
        foreach ( $comments as &$comment ) {
            $user = get_userdata( $comment['user_id'] );
            if ( $user ) {
                $comment['author_name']   = $user->display_name;
                $comment['author_avatar'] = get_avatar_url( $comment['user_id'], array( 'size' => 32 ) );
            }

            // Procesar replies
            if ( ! empty( $comment['replies'] ) ) {
                foreach ( $comment['replies'] as &$reply ) {
                    $reply_user = get_userdata( $reply['user_id'] );
                    if ( $reply_user ) {
                        $reply['author_name']   = $reply_user->display_name;
                        $reply['author_avatar'] = get_avatar_url( $reply['user_id'], array( 'size' => 32 ) );
                    }
                }
            }
        }

        return rest_ensure_response( array(
            'success'  => true,
            'comments' => array_values( $comments ),
        ) );
    }

    /**
     * Añadir comentario
     */
    public function add_comment( $request ) {
        $post_id    = absint( $request->get_param( 'post_id' ) );
        $element_id = sanitize_text_field( $request->get_param( 'element_id' ) );
        $text       = sanitize_textarea_field( $request->get_param( 'text' ) );
        $position   = $request->get_param( 'position' );
        $user_id    = get_current_user_id();

        if ( ! $post_id || ! $text ) {
            return rest_ensure_response( array(
                'success' => false,
                'message' => 'Datos inválidos',
            ) );
        }

        $comments = get_post_meta( $post_id, '_vbp_comments', true );
        if ( ! is_array( $comments ) ) {
            $comments = array();
        }

        $comment_id = 'comment_' . uniqid();

        $new_comment = array(
            'id'         => $comment_id,
            'user_id'    => $user_id,
            'element_id' => $element_id,
            'text'       => $text,
            'position'   => $position,
            'created_at' => current_time( 'mysql' ),
            'resolved'   => false,
            'replies'    => array(),
        );

        $comments[ $comment_id ] = $new_comment;
        update_post_meta( $post_id, '_vbp_comments', $comments );

        // Enriquecer con datos de usuario
        $user = get_userdata( $user_id );
        $new_comment['author_name']   = $user->display_name;
        $new_comment['author_avatar'] = get_avatar_url( $user_id, array( 'size' => 32 ) );

        return rest_ensure_response( array(
            'success' => true,
            'comment' => $new_comment,
        ) );
    }

    /**
     * Responder a un comentario
     */
    public function reply_comment( $request ) {
        $comment_id = sanitize_text_field( $request['comment_id'] );
        $text       = sanitize_textarea_field( $request->get_param( 'text' ) );
        $user_id    = get_current_user_id();

        // Buscar el post que contiene el comentario
        global $wpdb;
        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_vbp_comments'
                AND meta_value LIKE %s",
                '%' . $wpdb->esc_like( $comment_id ) . '%'
            )
        );

        if ( ! $post_id ) {
            return rest_ensure_response( array(
                'success' => false,
                'message' => 'Comentario no encontrado',
            ) );
        }

        $comments = get_post_meta( $post_id, '_vbp_comments', true );

        if ( isset( $comments[ $comment_id ] ) ) {
            $reply_id = 'reply_' . uniqid();

            $reply = array(
                'id'         => $reply_id,
                'user_id'    => $user_id,
                'text'       => $text,
                'created_at' => current_time( 'mysql' ),
            );

            if ( ! isset( $comments[ $comment_id ]['replies'] ) ) {
                $comments[ $comment_id ]['replies'] = array();
            }

            $comments[ $comment_id ]['replies'][] = $reply;
            update_post_meta( $post_id, '_vbp_comments', $comments );

            // Enriquecer con datos de usuario
            $user = get_userdata( $user_id );
            $reply['author_name']   = $user->display_name;
            $reply['author_avatar'] = get_avatar_url( $user_id, array( 'size' => 32 ) );

            return rest_ensure_response( array(
                'success' => true,
                'reply'   => $reply,
            ) );
        }

        return rest_ensure_response( array(
            'success' => false,
            'message' => 'Comentario no encontrado',
        ) );
    }

    /**
     * Resolver comentario
     */
    public function resolve_comment( $request ) {
        $comment_id = sanitize_text_field( $request['comment_id'] );
        $user_id    = get_current_user_id();

        global $wpdb;
        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_vbp_comments'
                AND meta_value LIKE %s",
                '%' . $wpdb->esc_like( $comment_id ) . '%'
            )
        );

        if ( ! $post_id ) {
            return rest_ensure_response( array(
                'success' => false,
                'message' => 'Comentario no encontrado',
            ) );
        }

        $comments = get_post_meta( $post_id, '_vbp_comments', true );

        if ( isset( $comments[ $comment_id ] ) ) {
            $comments[ $comment_id ]['resolved']    = true;
            $comments[ $comment_id ]['resolved_by'] = $user_id;
            $comments[ $comment_id ]['resolved_at'] = current_time( 'mysql' );

            update_post_meta( $post_id, '_vbp_comments', $comments );

            $user = get_userdata( $user_id );

            return rest_ensure_response( array(
                'success'     => true,
                'resolved_by' => $user->display_name,
                'resolved_at' => $comments[ $comment_id ]['resolved_at'],
            ) );
        }

        return rest_ensure_response( array(
            'success' => false,
            'message' => 'Comentario no encontrado',
        ) );
    }

    /**
     * Eliminar comentario
     */
    public function delete_comment( $request ) {
        $comment_id = sanitize_text_field( $request['comment_id'] );
        $user_id    = get_current_user_id();

        global $wpdb;
        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_vbp_comments'
                AND meta_value LIKE %s",
                '%' . $wpdb->esc_like( $comment_id ) . '%'
            )
        );

        if ( ! $post_id ) {
            return rest_ensure_response( array(
                'success' => false,
                'message' => 'Comentario no encontrado',
            ) );
        }

        $comments = get_post_meta( $post_id, '_vbp_comments', true );

        if ( isset( $comments[ $comment_id ] ) ) {
            // Solo el autor o admin puede eliminar
            if ( $comments[ $comment_id ]['user_id'] !== $user_id && ! current_user_can( 'manage_options' ) ) {
                return rest_ensure_response( array(
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este comentario',
                ) );
            }

            unset( $comments[ $comment_id ] );
            update_post_meta( $post_id, '_vbp_comments', $comments );

            return rest_ensure_response( array( 'success' => true ) );
        }

        return rest_ensure_response( array(
            'success' => false,
            'message' => 'Comentario no encontrado',
        ) );
    }

    // ============ HEARTBEAT ============

    /**
     * Recibir datos de heartbeat
     */
    public function heartbeat_received( $response, $data ) {
        if ( ! empty( $data['vbp_presence'] ) ) {
            $presence_data = $data['vbp_presence'];
            $post_id       = absint( $presence_data['post_id'] );
            $user_id       = get_current_user_id();

            if ( $post_id && $user_id ) {
                $user = get_userdata( $user_id );

                $transient_key = $this->presence_prefix . $post_id . '_' . $user_id;
                set_transient( $transient_key, array(
                    'user_id'         => $user_id,
                    'display_name'    => $user->display_name,
                    'avatar'          => get_avatar_url( $user_id, array( 'size' => 32 ) ),
                    'email_hash'      => md5( strtolower( trim( $user->user_email ) ) ),
                    'cursor'          => $presence_data['cursor'],
                    'editing_element' => $presence_data['editing_element'],
                    'last_active'     => time(),
                ), $this->presence_ttl );
            }
        }

        return $response;
    }

    /**
     * Enviar datos en heartbeat
     */
    public function heartbeat_send( $response, $screen_id ) {
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'vbp-editor' ) {
            $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

            if ( $post_id ) {
                $presence = $this->get_document_presence( $post_id );
                $response['vbp_presence'] = $presence;

                // Enviar también comentarios recientes
                $comments = get_post_meta( $post_id, '_vbp_comments', true );
                if ( is_array( $comments ) ) {
                    $response['vbp_comments'] = array_values( $comments );
                }
            }
        }

        return $response;
    }
}

// Inicializar
Flavor_VBP_Collaboration_API::get_instance();
