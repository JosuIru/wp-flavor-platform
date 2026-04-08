<?php
/**
 * API REST para Push Notifications (Firebase Cloud Messaging)
 *
 * Gestiona el registro de tokens de dispositivos y el envío de notificaciones push.
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API de Push Notifications
 */
class Flavor_Push_Notifications_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_Push_Notifications_API|null
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-app/v2';

    /**
     * Clave de API
     *
     * @var string
     */
    private $api_key = '';

    /**
     * Configuración de Firebase
     *
     * @var array
     */
    private $firebase_config = array();

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Push_Notifications_API
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
        $this->api_key = flavor_get_vbp_api_key();
        $this->firebase_config = get_option( 'flavor_firebase_config', array() );

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Verificar permisos de API
     *
     * @param WP_REST_Request $request Petición.
     * @return bool|WP_Error
     */
    public function check_api_permission( $request ) {
        $api_key = flavor_get_vbp_api_key_from_request( $request );

        if ( ! flavor_check_vbp_automation_access( $api_key, 'push_admin' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'API key inválida', 'flavor-chat-ia' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Permiso público (para registro de tokens desde app)
     *
     * @return bool
     */
    public function public_permission() {
        return true;
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // === CONFIGURACIÓN ===

        // Obtener estado de push notifications
        register_rest_route( self::NAMESPACE, '/push/status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_push_status' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Configurar Firebase
        register_rest_route( self::NAMESPACE, '/push/config', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'save_firebase_config' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === TOKENS DE DISPOSITIVOS ===

        // Registrar token de dispositivo
        register_rest_route( self::NAMESPACE, '/push/register', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'register_device_token' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // Eliminar token de dispositivo
        register_rest_route( self::NAMESPACE, '/push/unregister', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'unregister_device_token' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // Listar dispositivos registrados
        register_rest_route( self::NAMESPACE, '/push/devices', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_registered_devices' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === ENVÍO DE NOTIFICACIONES ===

        // Enviar notificación a un dispositivo
        register_rest_route( self::NAMESPACE, '/push/send', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'send_notification' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Enviar notificación a un topic
        register_rest_route( self::NAMESPACE, '/push/send/topic', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'send_topic_notification' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Enviar notificación a todos
        register_rest_route( self::NAMESPACE, '/push/send/broadcast', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'broadcast_notification' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === TOPICS ===

        // Suscribir dispositivo a topic
        register_rest_route( self::NAMESPACE, '/push/topics/subscribe', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'subscribe_to_topic' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // Desuscribir dispositivo de topic
        register_rest_route( self::NAMESPACE, '/push/topics/unsubscribe', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'unsubscribe_from_topic' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // === HISTORIAL ===

        // Historial de notificaciones
        register_rest_route( self::NAMESPACE, '/push/history', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_notification_history' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );
    }

    /**
     * GET /flavor-app/v2/push/status
     *
     * Obtiene el estado de las push notifications.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_push_status( $request ) {
        $is_configured = ! empty( $this->firebase_config['server_key'] );
        $devices = get_option( 'flavor_push_devices', array() );

        return rest_ensure_response( array(
            'enabled'     => $is_configured,
            'configured'  => $is_configured,
            'provider'    => 'firebase',
            'devices'     => array(
                'total'   => count( $devices ),
                'android' => count( array_filter( $devices, fn( $d ) => ( $d['platform'] ?? '' ) === 'android' ) ),
                'ios'     => count( array_filter( $devices, fn( $d ) => ( $d['platform'] ?? '' ) === 'ios' ) ),
            ),
            'topics'      => $this->get_available_topics(),
            'config'      => $is_configured ? array(
                'project_id' => $this->firebase_config['project_id'] ?? 'not_set',
            ) : null,
        ) );
    }

    /**
     * POST /flavor-app/v2/push/config
     *
     * Guarda la configuración de Firebase.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function save_firebase_config( $request ) {
        $server_key = $request->get_param( 'server_key' );
        $project_id = $request->get_param( 'project_id' );
        $service_account = $request->get_param( 'service_account' );

        if ( empty( $server_key ) && empty( $service_account ) ) {
            return new WP_Error(
                'missing_config',
                'Se requiere server_key o service_account',
                array( 'status' => 400 )
            );
        }

        $config = array(
            'server_key'      => sanitize_text_field( $server_key ?? '' ),
            'project_id'      => sanitize_text_field( $project_id ?? '' ),
            'service_account' => $service_account, // JSON de service account
            'updated_at'      => current_time( 'c' ),
        );

        update_option( 'flavor_firebase_config', $config );
        $this->firebase_config = $config;

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Configuración de Firebase guardada',
        ) );
    }

    /**
     * POST /flavor-app/v2/push/register
     *
     * Registra el token FCM de un dispositivo.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function register_device_token( $request ) {
        $token = $request->get_param( 'token' );
        $device_id = $request->get_param( 'device_id' );
        $platform = $request->get_param( 'platform' ) ?? 'android';
        $app_version = $request->get_param( 'app_version' );
        $user_id = $request->get_param( 'user_id' );

        if ( empty( $token ) ) {
            return new WP_Error(
                'missing_token',
                'Se requiere el token FCM',
                array( 'status' => 400 )
            );
        }

        $devices = get_option( 'flavor_push_devices', array() );

        // Usar token como clave para evitar duplicados
        $key = md5( $token );

        $devices[ $key ] = array(
            'token'       => $token,
            'device_id'   => sanitize_text_field( $device_id ?? '' ),
            'platform'    => in_array( $platform, array( 'android', 'ios', 'web' ), true ) ? $platform : 'android',
            'app_version' => sanitize_text_field( $app_version ?? '' ),
            'user_id'     => $user_id ? absint( $user_id ) : null,
            'topics'      => array( 'all' ), // Topic por defecto
            'registered_at' => current_time( 'c' ),
            'last_seen'   => current_time( 'c' ),
        );

        update_option( 'flavor_push_devices', $devices );

        return rest_ensure_response( array(
            'success'   => true,
            'device_key' => $key,
            'message'   => 'Dispositivo registrado para notificaciones',
        ) );
    }

    /**
     * POST /flavor-app/v2/push/unregister
     *
     * Elimina el token FCM de un dispositivo.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function unregister_device_token( $request ) {
        $token = $request->get_param( 'token' );
        $device_id = $request->get_param( 'device_id' );

        $devices = get_option( 'flavor_push_devices', array() );

        if ( ! empty( $token ) ) {
            $key = md5( $token );
            if ( isset( $devices[ $key ] ) ) {
                unset( $devices[ $key ] );
            }
        } elseif ( ! empty( $device_id ) ) {
            foreach ( $devices as $key => $device ) {
                if ( ( $device['device_id'] ?? '' ) === $device_id ) {
                    unset( $devices[ $key ] );
                    break;
                }
            }
        }

        update_option( 'flavor_push_devices', $devices );

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Dispositivo eliminado de notificaciones',
        ) );
    }

    /**
     * GET /flavor-app/v2/push/devices
     *
     * Lista los dispositivos registrados.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_registered_devices( $request ) {
        $devices = get_option( 'flavor_push_devices', array() );
        $platform_filter = $request->get_param( 'platform' );

        $result = array();
        foreach ( $devices as $key => $device ) {
            if ( $platform_filter && ( $device['platform'] ?? '' ) !== $platform_filter ) {
                continue;
            }

            $result[] = array(
                'key'         => $key,
                'device_id'   => $device['device_id'] ?? '',
                'platform'    => $device['platform'] ?? 'unknown',
                'app_version' => $device['app_version'] ?? '',
                'user_id'     => $device['user_id'] ?? null,
                'topics'      => $device['topics'] ?? array(),
                'registered_at' => $device['registered_at'] ?? '',
                'last_seen'   => $device['last_seen'] ?? '',
                // No exponer el token completo por seguridad
                'token_prefix' => substr( $device['token'] ?? '', 0, 20 ) . '...',
            );
        }

        return rest_ensure_response( array(
            'devices' => $result,
            'total'   => count( $result ),
        ) );
    }

    /**
     * POST /flavor-app/v2/push/send
     *
     * Envía una notificación a dispositivos específicos.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function send_notification( $request ) {
        $tokens = $request->get_param( 'tokens' );
        $device_ids = $request->get_param( 'device_ids' );
        $title = $request->get_param( 'title' );
        $body = $request->get_param( 'body' );
        $data = $request->get_param( 'data' ) ?? array();
        $image = $request->get_param( 'image' );

        if ( empty( $title ) || empty( $body ) ) {
            return new WP_Error(
                'missing_content',
                'Se requiere título y cuerpo de la notificación',
                array( 'status' => 400 )
            );
        }

        // Resolver tokens desde device_ids si se proporcionan
        if ( ! empty( $device_ids ) && empty( $tokens ) ) {
            $tokens = $this->get_tokens_by_device_ids( (array) $device_ids );
        }

        if ( empty( $tokens ) ) {
            return new WP_Error(
                'no_tokens',
                'No se especificaron dispositivos destino',
                array( 'status' => 400 )
            );
        }

        $result = $this->send_fcm_message(
            (array) $tokens,
            $title,
            $body,
            $data,
            $image
        );

        // Registrar en historial
        $this->log_notification( 'direct', $title, $body, count( (array) $tokens ), $result['success'] );

        return rest_ensure_response( $result );
    }

    /**
     * POST /flavor-app/v2/push/send/topic
     *
     * Envía una notificación a un topic.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function send_topic_notification( $request ) {
        $topic = $request->get_param( 'topic' );
        $title = $request->get_param( 'title' );
        $body = $request->get_param( 'body' );
        $data = $request->get_param( 'data' ) ?? array();
        $image = $request->get_param( 'image' );

        if ( empty( $topic ) || empty( $title ) || empty( $body ) ) {
            return new WP_Error(
                'missing_params',
                'Se requiere topic, título y cuerpo',
                array( 'status' => 400 )
            );
        }

        $result = $this->send_fcm_topic_message(
            $topic,
            $title,
            $body,
            $data,
            $image
        );

        $this->log_notification( 'topic:' . $topic, $title, $body, 0, $result['success'] );

        return rest_ensure_response( $result );
    }

    /**
     * POST /flavor-app/v2/push/send/broadcast
     *
     * Envía una notificación a todos los dispositivos.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function broadcast_notification( $request ) {
        $title = $request->get_param( 'title' );
        $body = $request->get_param( 'body' );
        $data = $request->get_param( 'data' ) ?? array();
        $image = $request->get_param( 'image' );
        $platform = $request->get_param( 'platform' ); // android, ios, o null para todos

        if ( empty( $title ) || empty( $body ) ) {
            return new WP_Error(
                'missing_content',
                'Se requiere título y cuerpo de la notificación',
                array( 'status' => 400 )
            );
        }

        $devices = get_option( 'flavor_push_devices', array() );
        $tokens = array();

        foreach ( $devices as $device ) {
            if ( $platform && ( $device['platform'] ?? '' ) !== $platform ) {
                continue;
            }
            if ( ! empty( $device['token'] ) ) {
                $tokens[] = $device['token'];
            }
        }

        if ( empty( $tokens ) ) {
            return rest_ensure_response( array(
                'success'  => false,
                'error'    => 'No hay dispositivos registrados',
                'sent'     => 0,
            ) );
        }

        // FCM permite máximo 500 tokens por request
        $batches = array_chunk( $tokens, 500 );
        $total_success = 0;
        $total_failure = 0;

        foreach ( $batches as $batch ) {
            $result = $this->send_fcm_message( $batch, $title, $body, $data, $image );
            $total_success += $result['success_count'] ?? 0;
            $total_failure += $result['failure_count'] ?? 0;
        }

        $this->log_notification( 'broadcast', $title, $body, count( $tokens ), $total_success > 0 );

        return rest_ensure_response( array(
            'success'       => $total_success > 0,
            'total_devices' => count( $tokens ),
            'success_count' => $total_success,
            'failure_count' => $total_failure,
        ) );
    }

    /**
     * POST /flavor-app/v2/push/topics/subscribe
     *
     * Suscribe un dispositivo a un topic.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function subscribe_to_topic( $request ) {
        $token = $request->get_param( 'token' );
        $topic = $request->get_param( 'topic' );

        if ( empty( $token ) || empty( $topic ) ) {
            return new WP_Error(
                'missing_params',
                'Se requiere token y topic',
                array( 'status' => 400 )
            );
        }

        // Actualizar topics del dispositivo
        $devices = get_option( 'flavor_push_devices', array() );
        $key = md5( $token );

        if ( isset( $devices[ $key ] ) ) {
            $topics = $devices[ $key ]['topics'] ?? array();
            if ( ! in_array( $topic, $topics, true ) ) {
                $topics[] = $topic;
                $devices[ $key ]['topics'] = $topics;
                update_option( 'flavor_push_devices', $devices );
            }
        }

        // Suscribir en FCM
        $result = $this->fcm_topic_subscription( $token, $topic, true );

        return rest_ensure_response( $result );
    }

    /**
     * POST /flavor-app/v2/push/topics/unsubscribe
     *
     * Desuscribe un dispositivo de un topic.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function unsubscribe_from_topic( $request ) {
        $token = $request->get_param( 'token' );
        $topic = $request->get_param( 'topic' );

        if ( empty( $token ) || empty( $topic ) ) {
            return new WP_Error(
                'missing_params',
                'Se requiere token y topic',
                array( 'status' => 400 )
            );
        }

        // Actualizar topics del dispositivo
        $devices = get_option( 'flavor_push_devices', array() );
        $key = md5( $token );

        if ( isset( $devices[ $key ] ) ) {
            $topics = $devices[ $key ]['topics'] ?? array();
            $topics = array_diff( $topics, array( $topic ) );
            $devices[ $key ]['topics'] = array_values( $topics );
            update_option( 'flavor_push_devices', $devices );
        }

        // Desuscribir en FCM
        $result = $this->fcm_topic_subscription( $token, $topic, false );

        return rest_ensure_response( $result );
    }

    /**
     * GET /flavor-app/v2/push/history
     *
     * Obtiene el historial de notificaciones enviadas.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_notification_history( $request ) {
        $limit = min( (int) ( $request->get_param( 'limit' ) ?? 50 ), 200 );
        $history = get_option( 'flavor_push_history', array() );

        // Ordenar por fecha descendente
        usort( $history, function( $a, $b ) {
            return strtotime( $b['timestamp'] ?? '0' ) - strtotime( $a['timestamp'] ?? '0' );
        } );

        return rest_ensure_response( array(
            'history' => array_slice( $history, 0, $limit ),
            'total'   => count( $history ),
        ) );
    }

    // =========================================================================
    // MÉTODOS PRIVADOS - FCM
    // =========================================================================

    /**
     * Envía mensaje FCM a tokens específicos
     *
     * @param array  $tokens Tokens FCM.
     * @param string $title  Título.
     * @param string $body   Cuerpo.
     * @param array  $data   Datos adicionales.
     * @param string $image  URL de imagen opcional.
     * @return array
     */
    private function send_fcm_message( $tokens, $title, $body, $data = array(), $image = null ) {
        if ( empty( $this->firebase_config['server_key'] ) ) {
            return array(
                'success' => false,
                'error'   => 'Firebase no está configurado',
            );
        }

        $notification = array(
            'title' => $title,
            'body'  => $body,
        );

        if ( ! empty( $image ) ) {
            $notification['image'] = $image;
        }

        $payload = array(
            'registration_ids' => $tokens,
            'notification'     => $notification,
            'data'             => array_merge( $data, array(
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'timestamp'    => (string) time(),
            ) ),
        );

        $response = wp_remote_post( 'https://fcm.googleapis.com/fcm/send', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'key=' . $this->firebase_config['server_key'],
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( $payload ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_response = json_decode( wp_remote_retrieve_body( $response ), true );

        return array(
            'success'       => $status_code === 200,
            'status_code'   => $status_code,
            'success_count' => $body_response['success'] ?? 0,
            'failure_count' => $body_response['failure'] ?? 0,
            'results'       => $body_response['results'] ?? array(),
        );
    }

    /**
     * Envía mensaje FCM a un topic
     *
     * @param string $topic Topic.
     * @param string $title Título.
     * @param string $body  Cuerpo.
     * @param array  $data  Datos adicionales.
     * @param string $image URL de imagen opcional.
     * @return array
     */
    private function send_fcm_topic_message( $topic, $title, $body, $data = array(), $image = null ) {
        if ( empty( $this->firebase_config['server_key'] ) ) {
            return array(
                'success' => false,
                'error'   => 'Firebase no está configurado',
            );
        }

        $notification = array(
            'title' => $title,
            'body'  => $body,
        );

        if ( ! empty( $image ) ) {
            $notification['image'] = $image;
        }

        $payload = array(
            'to'           => '/topics/' . $topic,
            'notification' => $notification,
            'data'         => array_merge( $data, array(
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'topic'        => $topic,
                'timestamp'    => (string) time(),
            ) ),
        );

        $response = wp_remote_post( 'https://fcm.googleapis.com/fcm/send', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'key=' . $this->firebase_config['server_key'],
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( $payload ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_response = json_decode( wp_remote_retrieve_body( $response ), true );

        return array(
            'success'    => $status_code === 200,
            'status_code' => $status_code,
            'message_id' => $body_response['message_id'] ?? null,
        );
    }

    /**
     * Suscribir/Desuscribir de topic en FCM
     *
     * @param string $token     Token FCM.
     * @param string $topic     Topic.
     * @param bool   $subscribe True para suscribir, false para desuscribir.
     * @return array
     */
    private function fcm_topic_subscription( $token, $topic, $subscribe = true ) {
        if ( empty( $this->firebase_config['server_key'] ) ) {
            return array(
                'success' => false,
                'error'   => 'Firebase no está configurado',
            );
        }

        $action = $subscribe ? 'batchAdd' : 'batchRemove';
        $url = "https://iid.googleapis.com/iid/v1:{$action}";

        $response = wp_remote_post( $url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'key=' . $this->firebase_config['server_key'],
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'to'                  => '/topics/' . $topic,
                'registration_tokens' => array( $token ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        return array(
            'success' => $status_code === 200,
            'action'  => $subscribe ? 'subscribed' : 'unsubscribed',
            'topic'   => $topic,
        );
    }

    /**
     * Obtiene tokens por device_ids
     *
     * @param array $device_ids IDs de dispositivos.
     * @return array
     */
    private function get_tokens_by_device_ids( $device_ids ) {
        $devices = get_option( 'flavor_push_devices', array() );
        $tokens = array();

        foreach ( $devices as $device ) {
            if ( in_array( $device['device_id'] ?? '', $device_ids, true ) ) {
                if ( ! empty( $device['token'] ) ) {
                    $tokens[] = $device['token'];
                }
            }
        }

        return $tokens;
    }

    /**
     * Registra una notificación en el historial
     *
     * @param string $target  Destino (topic, broadcast, direct).
     * @param string $title   Título.
     * @param string $body    Cuerpo.
     * @param int    $count   Número de dispositivos.
     * @param bool   $success Si fue exitoso.
     */
    private function log_notification( $target, $title, $body, $count, $success ) {
        $history = get_option( 'flavor_push_history', array() );

        $history[] = array(
            'target'    => $target,
            'title'     => $title,
            'body'      => substr( $body, 0, 100 ),
            'devices'   => $count,
            'success'   => $success,
            'timestamp' => current_time( 'c' ),
        );

        // Mantener solo los últimos 200
        if ( count( $history ) > 200 ) {
            $history = array_slice( $history, -200 );
        }

        update_option( 'flavor_push_history', $history );
    }

    /**
     * Obtiene los topics disponibles
     *
     * @return array
     */
    private function get_available_topics() {
        return array(
            'all'           => 'Todos los usuarios',
            'news'          => 'Noticias y novedades',
            'events'        => 'Eventos',
            'updates'       => 'Actualizaciones de la app',
            'marketplace'   => 'Ofertas del marketplace',
            'community'     => 'Actividad de la comunidad',
        );
    }
}

/**
 * Función helper para obtener instancia
 *
 * @return Flavor_Push_Notifications_API
 */
function flavor_push_api() {
    return Flavor_Push_Notifications_API::get_instance();
}

// Inicializar
add_action( 'plugins_loaded', 'flavor_push_api', 15 );
