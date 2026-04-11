<?php
/**
 * Visual Builder Pro - Realtime Collaboration Server (v2.5.0)
 *
 * Servidor de colaboracion en tiempo real mejorado con soporte para:
 * - Interpolacion de cursores con velocidad
 * - Sistema de awareness completo
 * - Chat y comentarios en tiempo real
 * - Deteccion y resolucion de conflictos (CRDT/OT)
 * - Following mode
 * - Gestion de sesiones avanzada
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para manejar la sincronizacion en tiempo real
 */
class Flavor_VBP_Realtime_Server {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Realtime_Server|null
     */
    private static $instance = null;

    /**
     * Namespace de la API REST
     *
     * @var string
     */
    private $namespace = 'flavor-vbp/v1';

    /**
     * Prefijo para transients de sesion
     *
     * @var string
     */
    private $session_prefix = 'vbp_rt_session_';

    /**
     * Prefijo para transients de locks
     *
     * @var string
     */
    private $lock_prefix = 'vbp_rt_lock_';

    /**
     * Prefijo para transients de cambios pendientes
     *
     * @var string
     */
    private $changes_prefix = 'vbp_rt_changes_';

    /**
     * Prefijo para transients de chat
     *
     * @var string
     */
    private $chat_prefix = 'vbp_rt_chat_';

    /**
     * Prefijo para transients de comentarios
     *
     * @var string
     */
    private $comments_prefix = 'vbp_rt_comments_';

    /**
     * Prefijo para transients de awareness
     *
     * @var string
     */
    private $awareness_prefix = 'vbp_rt_awareness_';

    /**
     * Prefijo para transients de following
     *
     * @var string
     */
    private $following_prefix = 'vbp_rt_following_';

    /**
     * Prefijo para transients de typing
     *
     * @var string
     */
    private $typing_prefix = 'vbp_rt_typing_';

    /**
     * TTL de sesion en segundos (30s)
     *
     * @var int
     */
    private $session_ttl = 30;

    /**
     * TTL de locks en segundos (30s)
     *
     * @var int
     */
    private $lock_ttl = 30;

    /**
     * TTL de cambios pendientes en segundos (5min)
     *
     * @var int
     */
    private $changes_ttl = 300;

    /**
     * TTL de mensajes de chat en segundos (1h)
     *
     * @var int
     */
    private $chat_ttl = 3600;

    /**
     * TTL de comentarios en segundos (24h)
     *
     * @var int
     */
    private $comments_ttl = 86400;

    /**
     * TTL de awareness en segundos (30s)
     *
     * @var int
     */
    private $awareness_ttl = 30;

    /**
     * TTL de typing indicator (5s)
     *
     * @var int
     */
    private $typing_ttl = 5;

    /**
     * Colores predefinidos para usuarios
     *
     * @var array
     */
    private $user_colors = array(
        '#3b82f6', // blue
        '#ef4444', // red
        '#10b981', // green
        '#f59e0b', // amber
        '#8b5cf6', // violet
        '#ec4899', // pink
        '#06b6d4', // cyan
        '#f97316', // orange
        '#14b8a6', // teal
        '#6366f1', // indigo
    );

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_VBP_Realtime_Server
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_filter( 'heartbeat_received', array( $this, 'heartbeat_received' ), 10, 2 );
        add_filter( 'heartbeat_send', array( $this, 'heartbeat_send' ), 10, 2 );
        add_filter( 'heartbeat_settings', array( $this, 'heartbeat_settings' ) );
    }

    /**
     * Registrar rutas de la API REST
     */
    public function register_routes() {
        // Estado de la sesion
        register_rest_route(
            $this->namespace,
            '/realtime/status/(?P<post_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_session_status' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // Unirse a sesion
        register_rest_route(
            $this->namespace,
            '/realtime/join',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'join_session' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // Salir de sesion
        register_rest_route(
            $this->namespace,
            '/realtime/leave',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'leave_session' ),
                'permission_callback' => '__return_true', // Permitir sin auth para sendBeacon
            )
        );

        // Actualizar presencia (cursor, seleccion)
        register_rest_route(
            $this->namespace,
            '/realtime/presence',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'update_presence' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // Solicitar lock de elemento
        register_rest_route(
            $this->namespace,
            '/realtime/lock',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'request_lock' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // Liberar lock
        register_rest_route(
            $this->namespace,
            '/realtime/unlock',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'release_lock' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // Sincronizar cambios
        register_rest_route(
            $this->namespace,
            '/realtime/sync',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'sync_changes' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // Long-polling endpoint (fallback)
        register_rest_route(
            $this->namespace,
            '/realtime/poll/(?P<post_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'long_poll' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // === CHAT ENDPOINTS ===
        register_rest_route(
            $this->namespace,
            '/realtime/chat/message',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'send_chat_message' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/realtime/chat/reaction',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'add_chat_reaction' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/realtime/chat/messages/(?P<post_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_chat_messages' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        // === COMMENTS ENDPOINTS ===
        register_rest_route(
            $this->namespace,
            '/realtime/comments/create',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_comment' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/realtime/comments/reply',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'reply_to_comment' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/realtime/comments/resolve',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'resolve_comment' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/realtime/comments/(?P<post_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_comments' ),
                'permission_callback' => array( $this, 'check_edit_permission' ),
            )
        );
    }

    /**
     * Verificar permisos de edicion
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return bool
     */
    public function check_edit_permission( $request ) {
        $post_id = $request->get_param( 'post_id' );

        if ( ! $post_id ) {
            return current_user_can( 'edit_posts' );
        }

        return current_user_can( 'edit_post', $post_id );
    }

    // ============================================
    // SESIONES
    // ============================================

    /**
     * Obtener estado de la sesion de colaboracion
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function get_session_status( $request ) {
        $post_id = absint( $request['post_id'] );
        $user_id = get_current_user_id();

        $session_data   = $this->get_session_data( $post_id );
        $locks_data     = $this->get_all_locks( $post_id );
        $awareness_data = $this->get_all_awareness( $post_id );
        $chat_messages  = $this->get_chat_messages_data( $post_id );
        $comments_data  = $this->get_all_comments( $post_id );

        return rest_ensure_response(
            array(
                'success'       => true,
                'post_id'       => $post_id,
                'users'         => $session_data['users'],
                'cursors'       => $session_data['cursors'],
                'selections'    => $session_data['selections'],
                'locks'         => $locks_data,
                'awareness'     => $awareness_data,
                'chat_messages' => $chat_messages,
                'comments'      => $comments_data,
                'current_user'  => $user_id,
                'timestamp'     => time(),
            )
        );
    }

    /**
     * Unirse a una sesion de colaboracion
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function join_session( $request ) {
        $post_id   = absint( $request->get_param( 'post_id' ) );
        $user_id   = get_current_user_id();
        $awareness = $request->get_param( 'awareness' );

        if ( ! $post_id || ! $user_id ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'Datos invalidos',
                )
            );
        }

        $user_data = get_userdata( $user_id );
        $color     = $this->get_user_color( $user_id, $post_id );

        $session_entry = array(
            'user_id'      => $user_id,
            'display_name' => $user_data->display_name,
            'avatar'       => get_avatar_url( $user_id, array( 'size' => 32 ) ),
            'color'        => $color,
            'cursor'       => null,
            'selection'    => array(),
            'status'       => 'active',
            'joined_at'    => time(),
            'last_active'  => time(),
        );

        $this->set_user_session( $post_id, $user_id, $session_entry );

        // Guardar awareness inicial
        if ( $awareness ) {
            $this->set_user_awareness( $post_id, $user_id, $awareness );
        }

        // Obtener datos actualizados
        $session_data   = $this->get_session_data( $post_id );
        $locks_data     = $this->get_all_locks( $post_id );
        $chat_messages  = $this->get_chat_messages_data( $post_id );
        $comments_data  = $this->get_all_comments( $post_id );

        return rest_ensure_response(
            array(
                'success'       => true,
                'user'          => $session_entry,
                'users'         => $session_data['users'],
                'locks'         => $locks_data,
                'chat_messages' => $chat_messages,
                'comments'      => $comments_data,
                'color'         => $color,
                'session_id'    => $post_id . '_' . $user_id,
            )
        );
    }

    /**
     * Salir de una sesion
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function leave_session( $request ) {
        $post_id = absint( $request->get_param( 'post_id' ) );
        $user_id = absint( $request->get_param( 'user_id' ) );

        // Validar que es el usuario correcto o admin
        $current_user = get_current_user_id();
        if ( $current_user && $current_user !== $user_id && ! current_user_can( 'manage_options' ) ) {
            $user_id = $current_user;
        }

        if ( $post_id && $user_id ) {
            // Eliminar sesion del usuario
            $this->remove_user_session( $post_id, $user_id );

            // Liberar todos los locks del usuario
            $this->release_all_user_locks( $post_id, $user_id );

            // Limpiar awareness
            $this->remove_user_awareness( $post_id, $user_id );

            // Limpiar following
            $this->remove_user_following( $post_id, $user_id );
        }

        return rest_ensure_response( array( 'success' => true ) );
    }

    /**
     * Actualizar presencia (cursor, seleccion, awareness, typing, following)
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function update_presence( $request ) {
        $post_id   = absint( $request->get_param( 'post_id' ) );
        $user_id   = get_current_user_id();
        $cursor    = $request->get_param( 'cursor' );
        $selection = $request->get_param( 'selection' );
        $awareness = $request->get_param( 'awareness' );
        $typing    = $request->get_param( 'typing' );
        $following = $request->get_param( 'following' );

        if ( ! $post_id || ! $user_id ) {
            return rest_ensure_response( array( 'success' => false ) );
        }

        // Obtener sesion existente
        $session = $this->get_user_session( $post_id, $user_id );

        if ( ! $session ) {
            // Auto-join si no existe sesion
            $user_data = get_userdata( $user_id );
            $color     = $this->get_user_color( $user_id, $post_id );

            $session = array(
                'user_id'      => $user_id,
                'display_name' => $user_data->display_name,
                'avatar'       => get_avatar_url( $user_id, array( 'size' => 32 ) ),
                'color'        => $color,
                'cursor'       => null,
                'selection'    => array(),
                'status'       => 'active',
                'joined_at'    => time(),
                'last_active'  => time(),
            );
        }

        // Actualizar cursor con velocidad
        if ( null !== $cursor ) {
            $session['cursor'] = array(
                'x'         => floatval( $cursor['x'] ?? 0 ),
                'y'         => floatval( $cursor['y'] ?? 0 ),
                'velocity'  => isset( $cursor['velocity'] ) ? array(
                    'x' => floatval( $cursor['velocity']['x'] ?? 0 ),
                    'y' => floatval( $cursor['velocity']['y'] ?? 0 ),
                ) : array( 'x' => 0, 'y' => 0 ),
                'timestamp' => isset( $cursor['timestamp'] ) ? intval( $cursor['timestamp'] ) : time() * 1000,
            );
        }

        if ( null !== $selection && is_array( $selection ) ) {
            $session['selection'] = array_map( 'sanitize_text_field', $selection );
        }

        $session['last_active'] = time();
        $session['status']      = 'active';

        $this->set_user_session( $post_id, $user_id, $session );

        // Actualizar awareness
        if ( $awareness ) {
            $this->set_user_awareness( $post_id, $user_id, $awareness );
        }

        // Actualizar typing indicator
        if ( null !== $typing ) {
            $this->set_user_typing( $post_id, $user_id, (bool) $typing );
        }

        // Actualizar following
        if ( null !== $following ) {
            $this->set_user_following( $post_id, $user_id, $following );
        }

        // Obtener datos actualizados
        $session_data   = $this->get_session_data( $post_id );
        $awareness_data = $this->get_all_awareness( $post_id );
        $typing_data    = $this->get_all_typing( $post_id );
        $followers      = $this->get_followers( $post_id, $user_id );

        return rest_ensure_response(
            array(
                'success'    => true,
                'users'      => $session_data['users'],
                'cursors'    => $session_data['cursors'],
                'selections' => $session_data['selections'],
                'awareness'  => $awareness_data,
                'typing'     => $typing_data,
                'followers'  => $followers,
            )
        );
    }

    // ============================================
    // LOCKS
    // ============================================

    /**
     * Solicitar lock de un elemento
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function request_lock( $request ) {
        $post_id    = absint( $request->get_param( 'post_id' ) );
        $element_id = sanitize_text_field( $request->get_param( 'element_id' ) );
        $user_id    = get_current_user_id();

        if ( ! $post_id || ! $element_id || ! $user_id ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'Datos invalidos',
                )
            );
        }

        // Verificar si ya existe un lock
        $existing_lock = $this->get_element_lock( $post_id, $element_id );

        if ( $existing_lock ) {
            // Si el lock es del mismo usuario, renovarlo
            if ( $existing_lock['locked_by'] === $user_id ) {
                $existing_lock['expires_at'] = time() + $this->lock_ttl;
                $this->set_element_lock( $post_id, $element_id, $existing_lock );

                return rest_ensure_response(
                    array(
                        'success' => true,
                        'lock'    => $existing_lock,
                        'renewed' => true,
                    )
                );
            }

            // Si el lock es de otro usuario y no ha expirado
            if ( $existing_lock['expires_at'] > time() ) {
                $owner_data = get_userdata( $existing_lock['locked_by'] );

                return rest_ensure_response(
                    array(
                        'success'    => false,
                        'message'    => 'Elemento bloqueado por ' . ( $owner_data ? $owner_data->display_name : 'otro usuario' ),
                        'lock'       => $existing_lock,
                        'locked_by'  => $owner_data ? $owner_data->display_name : 'Usuario',
                        'expires_in' => $existing_lock['expires_at'] - time(),
                    )
                );
            }
        }

        // Crear nuevo lock
        $user_data = get_userdata( $user_id );
        $color     = $this->get_user_color( $user_id, $post_id );

        $lock_data = array(
            'element_id'      => $element_id,
            'locked_by'       => $user_id,
            'locked_by_name'  => $user_data->display_name,
            'locked_by_color' => $color,
            'locked_at'       => time(),
            'expires_at'      => time() + $this->lock_ttl,
        );

        $this->set_element_lock( $post_id, $element_id, $lock_data );

        return rest_ensure_response(
            array(
                'success' => true,
                'lock'    => $lock_data,
            )
        );
    }

    /**
     * Liberar lock de un elemento
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function release_lock( $request ) {
        $post_id    = absint( $request->get_param( 'post_id' ) );
        $element_id = sanitize_text_field( $request->get_param( 'element_id' ) );
        $user_id    = get_current_user_id();
        $force      = (bool) $request->get_param( 'force' );

        if ( ! $post_id || ! $element_id ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'Datos invalidos',
                )
            );
        }

        $existing_lock = $this->get_element_lock( $post_id, $element_id );

        if ( ! $existing_lock ) {
            return rest_ensure_response(
                array(
                    'success' => true,
                    'message' => 'No habia lock activo',
                )
            );
        }

        // Solo el dueno o admin puede liberar el lock
        if ( $existing_lock['locked_by'] !== $user_id && ! current_user_can( 'manage_options' ) && ! $force ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'No tienes permiso para liberar este lock',
                )
            );
        }

        $this->delete_element_lock( $post_id, $element_id );

        return rest_ensure_response(
            array(
                'success' => true,
            )
        );
    }

    // ============================================
    // SINCRONIZACION DE CAMBIOS
    // ============================================

    /**
     * Sincronizar cambios con otros usuarios
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function sync_changes( $request ) {
        $post_id          = absint( $request->get_param( 'post_id' ) );
        $user_id          = get_current_user_id();
        $changes          = $request->get_param( 'changes' );
        $last_sync        = absint( $request->get_param( 'last_sync' ) );
        $document_version = absint( $request->get_param( 'document_version' ) );

        if ( ! $post_id || ! $user_id ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'Datos invalidos',
                )
            );
        }

        $timestamp = time();

        // Guardar cambios del usuario actual
        if ( ! empty( $changes ) && is_array( $changes ) ) {
            $this->store_changes( $post_id, $user_id, $changes, $timestamp );
        }

        // Obtener cambios de otros usuarios desde last_sync
        $remote_changes = $this->get_changes_since( $post_id, $last_sync, $user_id );

        // Obtener estado actual
        $session_data   = $this->get_session_data( $post_id );
        $locks_data     = $this->get_all_locks( $post_id );
        $awareness_data = $this->get_all_awareness( $post_id );
        $typing_data    = $this->get_all_typing( $post_id );
        $chat_messages  = $this->get_new_chat_messages( $post_id, $last_sync );

        return rest_ensure_response(
            array(
                'success'        => true,
                'timestamp'      => $timestamp,
                'remote_changes' => $remote_changes,
                'users'          => $session_data['users'],
                'cursors'        => $session_data['cursors'],
                'selections'     => $session_data['selections'],
                'locks'          => $locks_data,
                'awareness'      => $awareness_data,
                'typing'         => $typing_data,
                'chat_messages'  => $chat_messages,
            )
        );
    }

    /**
     * Long-polling endpoint (fallback cuando Heartbeat no esta disponible)
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function long_poll( $request ) {
        $post_id   = absint( $request['post_id'] );
        $last_sync = absint( $request->get_param( 'last_sync' ) );
        $timeout   = min( absint( $request->get_param( 'timeout' ) ), 25 ); // Max 25 segundos
        $user_id   = get_current_user_id();

        if ( ! $timeout ) {
            $timeout = 15;
        }

        $start_time = time();

        // Actualizar presencia del usuario
        $session = $this->get_user_session( $post_id, $user_id );
        if ( $session ) {
            $session['last_active'] = time();
            $session['status']      = 'active';
            $this->set_user_session( $post_id, $user_id, $session );
        }

        // Esperar hasta que haya cambios o timeout
        $remote_changes = array();
        while ( ( time() - $start_time ) < $timeout ) {
            $remote_changes = $this->get_changes_since( $post_id, $last_sync, $user_id );

            if ( ! empty( $remote_changes ) ) {
                break;
            }

            // Dormir 500ms antes de verificar de nuevo
            usleep( 500000 );
        }

        $session_data   = $this->get_session_data( $post_id );
        $locks_data     = $this->get_all_locks( $post_id );
        $awareness_data = $this->get_all_awareness( $post_id );

        return rest_ensure_response(
            array(
                'success'        => true,
                'timestamp'      => time(),
                'remote_changes' => $remote_changes,
                'users'          => $session_data['users'],
                'cursors'        => $session_data['cursors'],
                'selections'     => $session_data['selections'],
                'locks'          => $locks_data,
                'awareness'      => $awareness_data,
            )
        );
    }

    // ============================================
    // CHAT
    // ============================================

    /**
     * Enviar mensaje de chat
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function send_chat_message( $request ) {
        $post_id = absint( $request->get_param( 'post_id' ) );
        $user_id = get_current_user_id();
        $message = $request->get_param( 'message' );

        if ( ! $post_id || ! $user_id || empty( $message ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'Datos invalidos',
                )
            );
        }

        // Sanitizar mensaje
        $sanitized_message = array(
            'id'         => sanitize_text_field( $message['id'] ?? uniqid( 'msg_' ) ),
            'userId'     => $user_id,
            'userName'   => sanitize_text_field( $message['userName'] ?? '' ),
            'userColor'  => sanitize_hex_color( $message['userColor'] ?? '#3b82f6' ),
            'userAvatar' => esc_url_raw( $message['userAvatar'] ?? '' ),
            'content'    => sanitize_textarea_field( $message['content'] ?? '' ),
            'type'       => sanitize_text_field( $message['type'] ?? 'text' ),
            'mentions'   => isset( $message['mentions'] ) ? array_map( 'absint', $message['mentions'] ) : array(),
            'elementRef' => sanitize_text_field( $message['elementRef'] ?? '' ),
            'timestamp'  => time() * 1000,
            'reactions'  => array(),
        );

        // Almacenar mensaje
        $this->store_chat_message( $post_id, $sanitized_message );

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => $sanitized_message,
            )
        );
    }

    /**
     * Agregar reaccion a mensaje
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function add_chat_reaction( $request ) {
        $post_id    = absint( $request->get_param( 'post_id' ) );
        $user_id    = get_current_user_id();
        $message_id = sanitize_text_field( $request->get_param( 'message_id' ) );
        $emoji      = sanitize_text_field( $request->get_param( 'emoji' ) );
        $action     = sanitize_text_field( $request->get_param( 'action' ) ); // add or remove

        if ( ! $post_id || ! $message_id || ! $emoji ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'Datos invalidos',
                )
            );
        }

        $this->update_message_reaction( $post_id, $message_id, $user_id, $emoji, $action );

        return rest_ensure_response(
            array(
                'success' => true,
            )
        );
    }

    /**
     * Obtener mensajes de chat
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function get_chat_messages( $request ) {
        $post_id = absint( $request['post_id'] );

        return rest_ensure_response(
            array(
                'success'  => true,
                'messages' => $this->get_chat_messages_data( $post_id ),
            )
        );
    }

    // ============================================
    // COMENTARIOS
    // ============================================

    /**
     * Crear comentario en elemento
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function create_comment( $request ) {
        $post_id = absint( $request->get_param( 'post_id' ) );
        $user_id = get_current_user_id();
        $comment = $request->get_param( 'comment' );

        if ( ! $post_id || ! $user_id || empty( $comment ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'Datos invalidos',
                )
            );
        }

        // Sanitizar comentario
        $sanitized_comment = array(
            'id'         => sanitize_text_field( $comment['id'] ?? uniqid( 'cmt_' ) ),
            'elementId'  => sanitize_text_field( $comment['elementId'] ?? '' ),
            'userId'     => $user_id,
            'userName'   => sanitize_text_field( $comment['userName'] ?? '' ),
            'userColor'  => sanitize_hex_color( $comment['userColor'] ?? '#3b82f6' ),
            'userAvatar' => esc_url_raw( $comment['userAvatar'] ?? '' ),
            'content'    => sanitize_textarea_field( $comment['content'] ?? '' ),
            'position'   => isset( $comment['position'] ) ? array(
                'x' => floatval( $comment['position']['x'] ?? 0 ),
                'y' => floatval( $comment['position']['y'] ?? 0 ),
            ) : null,
            'resolved'   => false,
            'timestamp'  => time() * 1000,
            'replies'    => array(),
        );

        // Almacenar comentario
        $this->store_comment( $post_id, $sanitized_comment );

        return rest_ensure_response(
            array(
                'success' => true,
                'comment' => $sanitized_comment,
            )
        );
    }

    /**
     * Responder a comentario
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function reply_to_comment( $request ) {
        $post_id    = absint( $request->get_param( 'post_id' ) );
        $user_id    = get_current_user_id();
        $comment_id = sanitize_text_field( $request->get_param( 'comment_id' ) );
        $reply      = $request->get_param( 'reply' );

        if ( ! $post_id || ! $comment_id || empty( $reply ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'Datos invalidos',
                )
            );
        }

        // Sanitizar respuesta
        $sanitized_reply = array(
            'id'         => sanitize_text_field( $reply['id'] ?? uniqid( 'cmt_' ) ),
            'parentId'   => $comment_id,
            'userId'     => $user_id,
            'userName'   => sanitize_text_field( $reply['userName'] ?? '' ),
            'userColor'  => sanitize_hex_color( $reply['userColor'] ?? '#3b82f6' ),
            'userAvatar' => esc_url_raw( $reply['userAvatar'] ?? '' ),
            'content'    => sanitize_textarea_field( $reply['content'] ?? '' ),
            'timestamp'  => time() * 1000,
        );

        // Agregar respuesta
        $this->add_comment_reply( $post_id, $comment_id, $sanitized_reply );

        return rest_ensure_response(
            array(
                'success' => true,
                'reply'   => $sanitized_reply,
            )
        );
    }

    /**
     * Resolver comentario
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function resolve_comment( $request ) {
        $post_id    = absint( $request->get_param( 'post_id' ) );
        $user_id    = get_current_user_id();
        $comment_id = sanitize_text_field( $request->get_param( 'comment_id' ) );

        if ( ! $post_id || ! $comment_id ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => 'Datos invalidos',
                )
            );
        }

        $this->set_comment_resolved( $post_id, $comment_id, $user_id );

        return rest_ensure_response(
            array(
                'success' => true,
            )
        );
    }

    /**
     * Obtener todos los comentarios
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response
     */
    public function get_comments( $request ) {
        $post_id = absint( $request['post_id'] );

        return rest_ensure_response(
            array(
                'success'  => true,
                'comments' => $this->get_all_comments( $post_id ),
            )
        );
    }

    // ============================================
    // HEARTBEAT INTEGRATION
    // ============================================

    /**
     * Configurar frecuencia del heartbeat
     *
     * @param array $settings Configuracion actual.
     * @return array
     */
    public function heartbeat_settings( $settings ) {
        // Aumentar frecuencia cuando hay colaboracion activa
        if ( isset( $_GET['page'] ) && 'vbp-editor' === $_GET['page'] ) {
            $settings['interval'] = 5; // 5 segundos durante edicion
        }

        return $settings;
    }

    /**
     * Procesar datos recibidos en heartbeat
     *
     * @param array $response Respuesta actual.
     * @param array $data     Datos recibidos.
     * @return array
     */
    public function heartbeat_received( $response, $data ) {
        if ( empty( $data['vbp_realtime'] ) ) {
            return $response;
        }

        $realtime_data = $data['vbp_realtime'];
        $post_id       = absint( $realtime_data['post_id'] ?? 0 );
        $user_id       = get_current_user_id();

        if ( ! $post_id || ! $user_id ) {
            return $response;
        }

        // Actualizar presencia
        $cursor    = $realtime_data['cursor'] ?? null;
        $selection = $realtime_data['selection'] ?? array();
        $awareness = $realtime_data['awareness'] ?? null;
        $following = $realtime_data['following'] ?? null;

        $session = $this->get_user_session( $post_id, $user_id );

        if ( ! $session ) {
            $user_data = get_userdata( $user_id );
            $color     = $this->get_user_color( $user_id, $post_id );

            $session = array(
                'user_id'      => $user_id,
                'display_name' => $user_data->display_name,
                'avatar'       => get_avatar_url( $user_id, array( 'size' => 32 ) ),
                'color'        => $color,
                'cursor'       => null,
                'selection'    => array(),
                'status'       => 'active',
                'joined_at'    => time(),
                'last_active'  => time(),
            );
        }

        // Actualizar cursor con velocidad
        if ( null !== $cursor ) {
            $session['cursor'] = array(
                'x'         => floatval( $cursor['x'] ?? 0 ),
                'y'         => floatval( $cursor['y'] ?? 0 ),
                'velocity'  => isset( $cursor['velocity'] ) ? array(
                    'x' => floatval( $cursor['velocity']['x'] ?? 0 ),
                    'y' => floatval( $cursor['velocity']['y'] ?? 0 ),
                ) : array( 'x' => 0, 'y' => 0 ),
                'timestamp' => isset( $cursor['timestamp'] ) ? intval( $cursor['timestamp'] ) : time() * 1000,
            );
        }

        if ( is_array( $selection ) ) {
            $session['selection'] = array_map( 'sanitize_text_field', $selection );
        }

        $session['last_active'] = time();
        $session['status']      = 'active';
        $this->set_user_session( $post_id, $user_id, $session );

        // Actualizar awareness
        if ( $awareness ) {
            $this->set_user_awareness( $post_id, $user_id, $awareness );
        }

        // Actualizar following
        if ( null !== $following ) {
            $this->set_user_following( $post_id, $user_id, $following );
        }

        // Procesar cambios enviados
        $changes = $realtime_data['changes'] ?? array();
        if ( ! empty( $changes ) && is_array( $changes ) ) {
            $this->store_changes( $post_id, $user_id, $changes, time() );
        }

        // Renovar locks activos
        $active_locks = $realtime_data['active_locks'] ?? array();
        foreach ( $active_locks as $element_id ) {
            $lock = $this->get_element_lock( $post_id, $element_id );
            if ( $lock && $lock['locked_by'] === $user_id ) {
                $lock['expires_at'] = time() + $this->lock_ttl;
                $this->set_element_lock( $post_id, $element_id, $lock );
            }
        }

        return $response;
    }

    /**
     * Enviar datos en respuesta de heartbeat
     *
     * @param array  $response  Respuesta actual.
     * @param string $screen_id ID de pantalla.
     * @return array
     */
    public function heartbeat_send( $response, $screen_id ) {
        if ( ! isset( $_GET['page'] ) || 'vbp-editor' !== $_GET['page'] ) {
            return $response;
        }

        $post_id   = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
        $last_sync = isset( $_POST['data']['vbp_realtime']['last_sync'] )
            ? absint( $_POST['data']['vbp_realtime']['last_sync'] )
            : 0;
        $user_id   = get_current_user_id();

        if ( ! $post_id ) {
            return $response;
        }

        // Obtener datos de colaboracion
        $session_data   = $this->get_session_data( $post_id );
        $locks_data     = $this->get_all_locks( $post_id );
        $remote_changes = $this->get_changes_since( $post_id, $last_sync, $user_id );
        $awareness_data = $this->get_all_awareness( $post_id );
        $typing_data    = $this->get_all_typing( $post_id );
        $followers      = $this->get_followers( $post_id, $user_id );
        $chat_messages  = $this->get_new_chat_messages( $post_id, $last_sync );

        $response['vbp_realtime'] = array(
            'timestamp'      => time(),
            'users'          => $session_data['users'],
            'cursors'        => $session_data['cursors'],
            'selections'     => $session_data['selections'],
            'locks'          => $locks_data,
            'remote_changes' => $remote_changes,
            'awareness'      => $awareness_data,
            'typing'         => $typing_data,
            'followers'      => $followers,
            'chat_messages'  => $chat_messages,
        );

        return $response;
    }

    // ============================================
    // HELPERS DE ALMACENAMIENTO - SESIONES
    // ============================================

    /**
     * Obtener datos de sesion de un documento
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function get_session_data( $post_id ) {
        global $wpdb;

        $users      = array();
        $cursors    = array();
        $selections = array();

        $prefix  = '_transient_' . $this->session_prefix . $post_id . '_';
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
                if ( ( $current_time - $data['last_active'] ) < $this->session_ttl ) {
                    $users[] = array(
                        'id'          => $data['user_id'],
                        'name'        => $data['display_name'],
                        'avatar'      => $data['avatar'],
                        'color'       => $data['color'],
                        'status'      => $data['status'] ?? 'active',
                        'joined_at'   => $data['joined_at'],
                        'last_active' => $data['last_active'],
                    );

                    if ( ! empty( $data['cursor'] ) ) {
                        $cursors[ $data['user_id'] ] = $data['cursor'];
                    }

                    if ( ! empty( $data['selection'] ) ) {
                        $selections[ $data['user_id'] ] = $data['selection'];
                    }
                }
            }
        }

        return array(
            'users'      => $users,
            'cursors'    => $cursors,
            'selections' => $selections,
        );
    }

    /**
     * Obtener sesion de un usuario especifico
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario.
     * @return array|null
     */
    private function get_user_session( $post_id, $user_id ) {
        $key = $this->session_prefix . $post_id . '_' . $user_id;
        return get_transient( $key );
    }

    /**
     * Guardar sesion de usuario
     *
     * @param int   $post_id ID del post.
     * @param int   $user_id ID del usuario.
     * @param array $data    Datos de sesion.
     */
    private function set_user_session( $post_id, $user_id, $data ) {
        $key = $this->session_prefix . $post_id . '_' . $user_id;
        set_transient( $key, $data, $this->session_ttl );
    }

    /**
     * Eliminar sesion de usuario
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario.
     */
    private function remove_user_session( $post_id, $user_id ) {
        $key = $this->session_prefix . $post_id . '_' . $user_id;
        delete_transient( $key );
    }

    // ============================================
    // HELPERS DE ALMACENAMIENTO - LOCKS
    // ============================================

    /**
     * Obtener lock de un elemento
     *
     * @param int    $post_id    ID del post.
     * @param string $element_id ID del elemento.
     * @return array|null
     */
    private function get_element_lock( $post_id, $element_id ) {
        $key  = $this->lock_prefix . $post_id . '_' . $element_id;
        $lock = get_transient( $key );

        if ( $lock && $lock['expires_at'] > time() ) {
            return $lock;
        }

        // Limpiar lock expirado
        if ( $lock ) {
            delete_transient( $key );
        }

        return null;
    }

    /**
     * Establecer lock de elemento
     *
     * @param int    $post_id    ID del post.
     * @param string $element_id ID del elemento.
     * @param array  $data       Datos del lock.
     */
    private function set_element_lock( $post_id, $element_id, $data ) {
        $key = $this->lock_prefix . $post_id . '_' . $element_id;
        set_transient( $key, $data, $this->lock_ttl );
    }

    /**
     * Eliminar lock de elemento
     *
     * @param int    $post_id    ID del post.
     * @param string $element_id ID del elemento.
     */
    private function delete_element_lock( $post_id, $element_id ) {
        $key = $this->lock_prefix . $post_id . '_' . $element_id;
        delete_transient( $key );
    }

    /**
     * Obtener todos los locks activos de un documento
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function get_all_locks( $post_id ) {
        global $wpdb;

        $locks = array();

        $prefix  = '_transient_' . $this->lock_prefix . $post_id . '_';
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

            if ( is_array( $data ) && isset( $data['expires_at'] ) ) {
                if ( $data['expires_at'] > $current_time ) {
                    $locks[ $data['element_id'] ] = $data;
                }
            }
        }

        return $locks;
    }

    /**
     * Liberar todos los locks de un usuario
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario.
     */
    private function release_all_user_locks( $post_id, $user_id ) {
        $all_locks = $this->get_all_locks( $post_id );

        foreach ( $all_locks as $element_id => $lock ) {
            if ( $lock['locked_by'] === $user_id ) {
                $this->delete_element_lock( $post_id, $element_id );
            }
        }
    }

    // ============================================
    // HELPERS DE ALMACENAMIENTO - CAMBIOS
    // ============================================

    /**
     * Almacenar cambios de un usuario
     *
     * @param int   $post_id   ID del post.
     * @param int   $user_id   ID del usuario.
     * @param array $changes   Cambios realizados.
     * @param int   $timestamp Timestamp de los cambios.
     */
    private function store_changes( $post_id, $user_id, $changes, $timestamp ) {
        $key            = $this->changes_prefix . $post_id;
        $stored_changes = get_transient( $key );

        if ( ! is_array( $stored_changes ) ) {
            $stored_changes = array();
        }

        // Anadir nuevos cambios
        foreach ( $changes as $change ) {
            $stored_changes[] = array(
                'user_id'   => $user_id,
                'change'    => $change,
                'timestamp' => $timestamp,
            );
        }

        // Limitar a ultimos 100 cambios
        if ( count( $stored_changes ) > 100 ) {
            $stored_changes = array_slice( $stored_changes, -100 );
        }

        set_transient( $key, $stored_changes, $this->changes_ttl );
    }

    /**
     * Obtener cambios desde un timestamp
     *
     * @param int $post_id         ID del post.
     * @param int $since_timestamp Timestamp desde el cual obtener cambios.
     * @param int $exclude_user_id ID de usuario a excluir.
     * @return array
     */
    private function get_changes_since( $post_id, $since_timestamp, $exclude_user_id ) {
        $key            = $this->changes_prefix . $post_id;
        $stored_changes = get_transient( $key );

        if ( ! is_array( $stored_changes ) ) {
            return array();
        }

        $filtered_changes = array();

        foreach ( $stored_changes as $entry ) {
            if ( $entry['timestamp'] > $since_timestamp && $entry['user_id'] !== $exclude_user_id ) {
                $filtered_changes[] = $entry;
            }
        }

        return $filtered_changes;
    }

    // ============================================
    // HELPERS DE ALMACENAMIENTO - AWARENESS
    // ============================================

    /**
     * Establecer awareness de usuario
     *
     * @param int   $post_id   ID del post.
     * @param int   $user_id   ID del usuario.
     * @param array $awareness Datos de awareness.
     */
    private function set_user_awareness( $post_id, $user_id, $awareness ) {
        $key = $this->awareness_prefix . $post_id . '_' . $user_id;
        set_transient( $key, $awareness, $this->awareness_ttl );
    }

    /**
     * Obtener awareness de usuario
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario.
     * @return array|null
     */
    private function get_user_awareness( $post_id, $user_id ) {
        $key = $this->awareness_prefix . $post_id . '_' . $user_id;
        return get_transient( $key );
    }

    /**
     * Eliminar awareness de usuario
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario.
     */
    private function remove_user_awareness( $post_id, $user_id ) {
        $key = $this->awareness_prefix . $post_id . '_' . $user_id;
        delete_transient( $key );
    }

    /**
     * Obtener todo el awareness de un documento
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function get_all_awareness( $post_id ) {
        global $wpdb;

        $awareness_data = array();

        $prefix  = '_transient_' . $this->awareness_prefix . $post_id . '_';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                WHERE option_name LIKE %s",
                $prefix . '%'
            )
        );

        foreach ( $results as $row ) {
            $data = maybe_unserialize( $row->option_value );
            if ( is_array( $data ) && isset( $data['userId'] ) ) {
                $awareness_data[ $data['userId'] ] = $data;
            }
        }

        return $awareness_data;
    }

    // ============================================
    // HELPERS DE ALMACENAMIENTO - TYPING
    // ============================================

    /**
     * Establecer estado de typing
     *
     * @param int  $post_id   ID del post.
     * @param int  $user_id   ID del usuario.
     * @param bool $is_typing Estado de typing.
     */
    private function set_user_typing( $post_id, $user_id, $is_typing ) {
        $key = $this->typing_prefix . $post_id . '_' . $user_id;
        if ( $is_typing ) {
            set_transient( $key, true, $this->typing_ttl );
        } else {
            delete_transient( $key );
        }
    }

    /**
     * Obtener todos los usuarios escribiendo
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function get_all_typing( $post_id ) {
        global $wpdb;

        $typing_data = array();

        $prefix  = '_transient_' . $this->typing_prefix . $post_id . '_';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                WHERE option_name LIKE %s",
                $prefix . '%'
            )
        );

        foreach ( $results as $row ) {
            // Extraer user_id del nombre del transient
            $user_id = str_replace( $prefix, '', str_replace( '_transient_', '', $row->option_name ) );
            if ( is_numeric( $user_id ) ) {
                $typing_data[ intval( $user_id ) ] = true;
            }
        }

        return $typing_data;
    }

    // ============================================
    // HELPERS DE ALMACENAMIENTO - FOLLOWING
    // ============================================

    /**
     * Establecer a quien sigue un usuario
     *
     * @param int      $post_id      ID del post.
     * @param int      $user_id      ID del usuario que sigue.
     * @param int|null $following_id ID del usuario seguido (null para dejar de seguir).
     */
    private function set_user_following( $post_id, $user_id, $following_id ) {
        $key = $this->following_prefix . $post_id . '_' . $user_id;
        if ( $following_id ) {
            set_transient( $key, absint( $following_id ), $this->session_ttl );
        } else {
            delete_transient( $key );
        }
    }

    /**
     * Eliminar following de usuario
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario.
     */
    private function remove_user_following( $post_id, $user_id ) {
        $key = $this->following_prefix . $post_id . '_' . $user_id;
        delete_transient( $key );
    }

    /**
     * Obtener seguidores de un usuario
     *
     * @param int $post_id ID del post.
     * @param int $user_id ID del usuario.
     * @return array Lista de IDs de usuarios que siguen a este usuario.
     */
    private function get_followers( $post_id, $user_id ) {
        global $wpdb;

        $followers = array();

        $prefix  = '_transient_' . $this->following_prefix . $post_id . '_';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                WHERE option_name LIKE %s",
                $prefix . '%'
            )
        );

        foreach ( $results as $row ) {
            $following_user_id = maybe_unserialize( $row->option_value );
            if ( intval( $following_user_id ) === $user_id ) {
                // Extraer follower_id del nombre del transient
                $follower_id = str_replace( $prefix, '', str_replace( '_transient_', '', $row->option_name ) );
                if ( is_numeric( $follower_id ) ) {
                    $followers[] = intval( $follower_id );
                }
            }
        }

        return $followers;
    }

    // ============================================
    // HELPERS DE ALMACENAMIENTO - CHAT
    // ============================================

    /**
     * Almacenar mensaje de chat
     *
     * @param int   $post_id ID del post.
     * @param array $message Datos del mensaje.
     */
    private function store_chat_message( $post_id, $message ) {
        $key      = $this->chat_prefix . $post_id;
        $messages = get_transient( $key );

        if ( ! is_array( $messages ) ) {
            $messages = array();
        }

        $messages[] = $message;

        // Limitar a ultimos 100 mensajes
        if ( count( $messages ) > 100 ) {
            $messages = array_slice( $messages, -100 );
        }

        set_transient( $key, $messages, $this->chat_ttl );
    }

    /**
     * Actualizar reaccion de mensaje
     *
     * @param int    $post_id    ID del post.
     * @param string $message_id ID del mensaje.
     * @param int    $user_id    ID del usuario.
     * @param string $emoji      Emoji de reaccion.
     * @param string $action     Accion (add o remove).
     */
    private function update_message_reaction( $post_id, $message_id, $user_id, $emoji, $action ) {
        $key      = $this->chat_prefix . $post_id;
        $messages = get_transient( $key );

        if ( ! is_array( $messages ) ) {
            return;
        }

        foreach ( $messages as &$message ) {
            if ( $message['id'] === $message_id ) {
                if ( ! isset( $message['reactions'][ $emoji ] ) ) {
                    $message['reactions'][ $emoji ] = array();
                }

                if ( 'add' === $action ) {
                    if ( ! in_array( $user_id, $message['reactions'][ $emoji ], true ) ) {
                        $message['reactions'][ $emoji ][] = $user_id;
                    }
                } else {
                    $index = array_search( $user_id, $message['reactions'][ $emoji ], true );
                    if ( false !== $index ) {
                        array_splice( $message['reactions'][ $emoji ], $index, 1 );
                    }
                }
                break;
            }
        }

        set_transient( $key, $messages, $this->chat_ttl );
    }

    /**
     * Obtener todos los mensajes de chat
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function get_chat_messages_data( $post_id ) {
        $key      = $this->chat_prefix . $post_id;
        $messages = get_transient( $key );

        return is_array( $messages ) ? $messages : array();
    }

    /**
     * Obtener mensajes nuevos desde un timestamp
     *
     * @param int $post_id   ID del post.
     * @param int $since     Timestamp desde el cual obtener mensajes.
     * @return array
     */
    private function get_new_chat_messages( $post_id, $since ) {
        $all_messages = $this->get_chat_messages_data( $post_id );
        $since_ms     = $since * 1000;

        return array_filter(
            $all_messages,
            function ( $msg ) use ( $since_ms ) {
                return $msg['timestamp'] > $since_ms;
            }
        );
    }

    // ============================================
    // HELPERS DE ALMACENAMIENTO - COMENTARIOS
    // ============================================

    /**
     * Almacenar comentario
     *
     * @param int   $post_id ID del post.
     * @param array $comment Datos del comentario.
     */
    private function store_comment( $post_id, $comment ) {
        $key      = $this->comments_prefix . $post_id;
        $comments = get_transient( $key );

        if ( ! is_array( $comments ) ) {
            $comments = array();
        }

        $element_id = $comment['elementId'];
        if ( ! isset( $comments[ $element_id ] ) ) {
            $comments[ $element_id ] = array();
        }

        $comments[ $element_id ][] = $comment;

        set_transient( $key, $comments, $this->comments_ttl );

        // Tambien guardar en post meta para persistencia
        $this->persist_comments( $post_id, $comments );
    }

    /**
     * Agregar respuesta a comentario
     *
     * @param int    $post_id    ID del post.
     * @param string $comment_id ID del comentario padre.
     * @param array  $reply      Datos de la respuesta.
     */
    private function add_comment_reply( $post_id, $comment_id, $reply ) {
        $key      = $this->comments_prefix . $post_id;
        $comments = get_transient( $key );

        if ( ! is_array( $comments ) ) {
            $comments = $this->load_persisted_comments( $post_id );
        }

        foreach ( $comments as $element_id => &$element_comments ) {
            foreach ( $element_comments as &$comment ) {
                if ( $comment['id'] === $comment_id ) {
                    if ( ! isset( $comment['replies'] ) ) {
                        $comment['replies'] = array();
                    }
                    $comment['replies'][] = $reply;
                    break 2;
                }
            }
        }

        set_transient( $key, $comments, $this->comments_ttl );
        $this->persist_comments( $post_id, $comments );
    }

    /**
     * Marcar comentario como resuelto
     *
     * @param int    $post_id    ID del post.
     * @param string $comment_id ID del comentario.
     * @param int    $user_id    ID del usuario que resuelve.
     */
    private function set_comment_resolved( $post_id, $comment_id, $user_id ) {
        $key      = $this->comments_prefix . $post_id;
        $comments = get_transient( $key );

        if ( ! is_array( $comments ) ) {
            $comments = $this->load_persisted_comments( $post_id );
        }

        foreach ( $comments as $element_id => &$element_comments ) {
            foreach ( $element_comments as &$comment ) {
                if ( $comment['id'] === $comment_id ) {
                    $comment['resolved']   = true;
                    $comment['resolvedBy'] = $user_id;
                    $comment['resolvedAt'] = time() * 1000;
                    break 2;
                }
            }
        }

        set_transient( $key, $comments, $this->comments_ttl );
        $this->persist_comments( $post_id, $comments );
    }

    /**
     * Obtener todos los comentarios
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function get_all_comments( $post_id ) {
        $key      = $this->comments_prefix . $post_id;
        $comments = get_transient( $key );

        if ( ! is_array( $comments ) ) {
            $comments = $this->load_persisted_comments( $post_id );
        }

        return $comments;
    }

    /**
     * Persistir comentarios en post meta
     *
     * @param int   $post_id  ID del post.
     * @param array $comments Comentarios a persistir.
     */
    private function persist_comments( $post_id, $comments ) {
        update_post_meta( $post_id, '_vbp_comments', $comments );
    }

    /**
     * Cargar comentarios persistidos
     *
     * @param int $post_id ID del post.
     * @return array
     */
    private function load_persisted_comments( $post_id ) {
        $comments = get_post_meta( $post_id, '_vbp_comments', true );
        return is_array( $comments ) ? $comments : array();
    }

    // ============================================
    // HELPERS GENERALES
    // ============================================

    /**
     * Obtener color para un usuario
     *
     * @param int $user_id ID del usuario.
     * @param int $post_id ID del post.
     * @return string Color hex.
     */
    private function get_user_color( $user_id, $post_id ) {
        // Usar colores consistentes basados en user_id
        $color_index = $user_id % count( $this->user_colors );
        return $this->user_colors[ $color_index ];
    }
}

// Inicializar
Flavor_VBP_Realtime_Server::get_instance();
