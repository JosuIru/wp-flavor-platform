<?php
/**
 * API REST para Analytics de Apps Móviles
 *
 * Recibe eventos de tracking desde las apps Flutter y genera estadísticas.
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API de Analytics
 */
class Flavor_Analytics_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_Analytics_API|null
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
     * Obtiene la instancia singleton
     *
     * @return Flavor_Analytics_API
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

        // Limpiar datos antiguos periódicamente
        add_action( 'flavor_cleanup_analytics', array( $this, 'cleanup_old_data' ) );
        if ( ! wp_next_scheduled( 'flavor_cleanup_analytics' ) ) {
            wp_schedule_event( time(), 'daily', 'flavor_cleanup_analytics' );
        }
    }

    /**
     * Verificar permisos de API
     */
    public function check_api_permission( $request ) {
        $api_key = $request->get_header( 'X-VBP-Key' );
        if ( empty( $api_key ) ) {
            $api_key = $request->get_param( 'api_key' );
        }
        if ( $api_key !== $this->api_key ) {
            return new WP_Error( 'rest_forbidden', 'API key inválida', array( 'status' => 403 ) );
        }
        return true;
    }

    /**
     * Permiso público
     */
    public function public_permission() {
        return true;
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // Enviar evento de analytics
        register_rest_route( self::NAMESPACE, '/analytics/event', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'track_event' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // Enviar batch de eventos
        register_rest_route( self::NAMESPACE, '/analytics/batch', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'track_batch' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // Obtener resumen de analytics
        register_rest_route( self::NAMESPACE, '/analytics/summary', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_summary' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener eventos por tipo
        register_rest_route( self::NAMESPACE, '/analytics/events', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_events' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener estadísticas de usuarios
        register_rest_route( self::NAMESPACE, '/analytics/users', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_user_stats' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener estadísticas de módulos
        register_rest_route( self::NAMESPACE, '/analytics/modules', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_module_stats' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener estadísticas de dispositivos
        register_rest_route( self::NAMESPACE, '/analytics/devices', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_device_stats' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Exportar datos
        register_rest_route( self::NAMESPACE, '/analytics/export', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'export_analytics' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );
    }

    /**
     * POST /analytics/event
     *
     * Registra un evento de analytics.
     */
    public function track_event( $request ) {
        $event_name = $request->get_param( 'event' );
        $properties = $request->get_param( 'properties' ) ?? array();
        $device_id = $request->get_param( 'device_id' );
        $user_id = $request->get_param( 'user_id' );
        $session_id = $request->get_param( 'session_id' );
        $timestamp = $request->get_param( 'timestamp' );

        if ( empty( $event_name ) ) {
            return new WP_Error( 'missing_event', 'Se requiere nombre del evento', array( 'status' => 400 ) );
        }

        $event_data = array(
            'event'      => sanitize_text_field( $event_name ),
            'properties' => $this->sanitize_properties( $properties ),
            'device_id'  => sanitize_text_field( $device_id ?? '' ),
            'user_id'    => $user_id ? absint( $user_id ) : null,
            'session_id' => sanitize_text_field( $session_id ?? '' ),
            'timestamp'  => $timestamp ? sanitize_text_field( $timestamp ) : current_time( 'c' ),
            'ip'         => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        );

        $this->store_event( $event_data );

        return rest_ensure_response( array(
            'success' => true,
            'event'   => $event_name,
        ) );
    }

    /**
     * POST /analytics/batch
     *
     * Registra múltiples eventos.
     */
    public function track_batch( $request ) {
        $events = $request->get_param( 'events' );
        $device_id = $request->get_param( 'device_id' );

        if ( empty( $events ) || ! is_array( $events ) ) {
            return new WP_Error( 'missing_events', 'Se requiere array de eventos', array( 'status' => 400 ) );
        }

        $stored = 0;
        foreach ( $events as $event ) {
            if ( empty( $event['event'] ) ) continue;

            $event_data = array(
                'event'      => sanitize_text_field( $event['event'] ),
                'properties' => $this->sanitize_properties( $event['properties'] ?? array() ),
                'device_id'  => sanitize_text_field( $event['device_id'] ?? $device_id ?? '' ),
                'user_id'    => isset( $event['user_id'] ) ? absint( $event['user_id'] ) : null,
                'session_id' => sanitize_text_field( $event['session_id'] ?? '' ),
                'timestamp'  => isset( $event['timestamp'] ) ? sanitize_text_field( $event['timestamp'] ) : current_time( 'c' ),
                'ip'         => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            );

            $this->store_event( $event_data );
            $stored++;
        }

        return rest_ensure_response( array(
            'success' => true,
            'stored'  => $stored,
            'total'   => count( $events ),
        ) );
    }

    /**
     * GET /analytics/summary
     *
     * Obtiene resumen de analytics.
     */
    public function get_summary( $request ) {
        $period = $request->get_param( 'period' ) ?? '7d';
        $events = $this->get_events_data( $period );

        // Calcular métricas
        $total_events = count( $events );
        $unique_users = count( array_unique( array_filter( array_column( $events, 'user_id' ) ) ) );
        $unique_devices = count( array_unique( array_filter( array_column( $events, 'device_id' ) ) ) );
        $unique_sessions = count( array_unique( array_filter( array_column( $events, 'session_id' ) ) ) );

        // Eventos por tipo
        $events_by_type = array_count_values( array_column( $events, 'event' ) );
        arsort( $events_by_type );

        // Eventos por día
        $events_by_day = array();
        foreach ( $events as $event ) {
            $day = substr( $event['timestamp'], 0, 10 );
            if ( ! isset( $events_by_day[ $day ] ) ) {
                $events_by_day[ $day ] = 0;
            }
            $events_by_day[ $day ]++;
        }
        ksort( $events_by_day );

        return rest_ensure_response( array(
            'period'         => $period,
            'total_events'   => $total_events,
            'unique_users'   => $unique_users,
            'unique_devices' => $unique_devices,
            'unique_sessions' => $unique_sessions,
            'events_by_type' => array_slice( $events_by_type, 0, 20, true ),
            'events_by_day'  => $events_by_day,
            'generated_at'   => current_time( 'c' ),
        ) );
    }

    /**
     * GET /analytics/events
     *
     * Lista eventos con filtros.
     */
    public function get_events( $request ) {
        $period = $request->get_param( 'period' ) ?? '7d';
        $event_type = $request->get_param( 'type' );
        $limit = min( (int) ( $request->get_param( 'limit' ) ?? 100 ), 500 );
        $offset = (int) ( $request->get_param( 'offset' ) ?? 0 );

        $events = $this->get_events_data( $period );

        // Filtrar por tipo si se especifica
        if ( $event_type ) {
            $events = array_filter( $events, fn( $e ) => $e['event'] === $event_type );
        }

        // Ordenar por timestamp descendente
        usort( $events, fn( $a, $b ) => strcmp( $b['timestamp'], $a['timestamp'] ) );

        // Paginar
        $total = count( $events );
        $events = array_slice( $events, $offset, $limit );

        return rest_ensure_response( array(
            'events' => $events,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ) );
    }

    /**
     * GET /analytics/users
     */
    public function get_user_stats( $request ) {
        $period = $request->get_param( 'period' ) ?? '30d';
        $events = $this->get_events_data( $period );

        $users = array();
        foreach ( $events as $event ) {
            $user_id = $event['user_id'];
            if ( ! $user_id ) continue;

            if ( ! isset( $users[ $user_id ] ) ) {
                $users[ $user_id ] = array(
                    'user_id'      => $user_id,
                    'events_count' => 0,
                    'sessions'     => array(),
                    'last_seen'    => null,
                    'first_seen'   => null,
                );
            }

            $users[ $user_id ]['events_count']++;
            if ( $event['session_id'] ) {
                $users[ $user_id ]['sessions'][ $event['session_id'] ] = true;
            }

            $ts = $event['timestamp'];
            if ( ! $users[ $user_id ]['first_seen'] || $ts < $users[ $user_id ]['first_seen'] ) {
                $users[ $user_id ]['first_seen'] = $ts;
            }
            if ( ! $users[ $user_id ]['last_seen'] || $ts > $users[ $user_id ]['last_seen'] ) {
                $users[ $user_id ]['last_seen'] = $ts;
            }
        }

        // Convertir sessions a count
        foreach ( $users as &$user ) {
            $user['sessions_count'] = count( $user['sessions'] );
            unset( $user['sessions'] );
        }

        // Ordenar por eventos
        usort( $users, fn( $a, $b ) => $b['events_count'] - $a['events_count'] );

        return rest_ensure_response( array(
            'users'       => array_slice( array_values( $users ), 0, 100 ),
            'total_users' => count( $users ),
            'period'      => $period,
        ) );
    }

    /**
     * GET /analytics/modules
     */
    public function get_module_stats( $request ) {
        $period = $request->get_param( 'period' ) ?? '30d';
        $events = $this->get_events_data( $period );

        $modules = array();
        foreach ( $events as $event ) {
            // Extraer módulo del evento o propiedades
            $module = $event['properties']['module'] ?? null;
            if ( ! $module && strpos( $event['event'], 'module_' ) === 0 ) {
                $module = str_replace( 'module_', '', explode( '_', $event['event'], 3 )[1] ?? '' );
            }
            if ( ! $module ) continue;

            if ( ! isset( $modules[ $module ] ) ) {
                $modules[ $module ] = array(
                    'module'       => $module,
                    'events_count' => 0,
                    'unique_users' => array(),
                    'event_types'  => array(),
                );
            }

            $modules[ $module ]['events_count']++;
            if ( $event['user_id'] ) {
                $modules[ $module ]['unique_users'][ $event['user_id'] ] = true;
            }
            $modules[ $module ]['event_types'][ $event['event'] ] = 
                ( $modules[ $module ]['event_types'][ $event['event'] ] ?? 0 ) + 1;
        }

        // Convertir arrays a counts
        foreach ( $modules as &$module ) {
            $module['unique_users'] = count( $module['unique_users'] );
        }

        // Ordenar por eventos
        usort( $modules, fn( $a, $b ) => $b['events_count'] - $a['events_count'] );

        return rest_ensure_response( array(
            'modules' => array_values( $modules ),
            'period'  => $period,
        ) );
    }

    /**
     * GET /analytics/devices
     */
    public function get_device_stats( $request ) {
        $period = $request->get_param( 'period' ) ?? '30d';
        $events = $this->get_events_data( $period );

        $platforms = array( 'android' => 0, 'ios' => 0, 'web' => 0, 'other' => 0 );
        $app_versions = array();
        $devices = array();

        foreach ( $events as $event ) {
            $device_id = $event['device_id'] ?? '';
            $ua = $event['user_agent'] ?? '';
            $platform = $event['properties']['platform'] ?? $this->detect_platform( $ua );
            $version = $event['properties']['app_version'] ?? 'unknown';

            $platforms[ $platform ] = ( $platforms[ $platform ] ?? 0 ) + 1;
            $app_versions[ $version ] = ( $app_versions[ $version ] ?? 0 ) + 1;

            if ( $device_id && ! isset( $devices[ $device_id ] ) ) {
                $devices[ $device_id ] = array(
                    'platform' => $platform,
                    'version'  => $version,
                );
            }
        }

        arsort( $app_versions );

        return rest_ensure_response( array(
            'platforms'      => $platforms,
            'app_versions'   => array_slice( $app_versions, 0, 10, true ),
            'unique_devices' => count( $devices ),
            'period'         => $period,
        ) );
    }

    /**
     * GET /analytics/export
     */
    public function export_analytics( $request ) {
        $period = $request->get_param( 'period' ) ?? '30d';
        $format = $request->get_param( 'format' ) ?? 'json';

        $events = $this->get_events_data( $period );

        if ( $format === 'csv' ) {
            $csv = "event,timestamp,device_id,user_id,session_id\n";
            foreach ( $events as $event ) {
                $csv .= sprintf(
                    "%s,%s,%s,%s,%s\n",
                    $event['event'],
                    $event['timestamp'],
                    $event['device_id'] ?? '',
                    $event['user_id'] ?? '',
                    $event['session_id'] ?? ''
                );
            }
            return new WP_REST_Response( $csv, 200, array(
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="analytics_export.csv"',
            ) );
        }

        return rest_ensure_response( array(
            'events' => $events,
            'period' => $period,
            'exported_at' => current_time( 'c' ),
        ) );
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    /**
     * Almacena un evento
     */
    private function store_event( $event_data ) {
        $events = get_option( 'flavor_analytics_events', array() );
        $events[] = $event_data;

        // Mantener máximo 10000 eventos en memoria
        if ( count( $events ) > 10000 ) {
            $events = array_slice( $events, -10000 );
        }

        update_option( 'flavor_analytics_events', $events, false );

        // También guardar resumen diario
        $day = substr( $event_data['timestamp'], 0, 10 );
        $daily = get_option( 'flavor_analytics_daily_' . $day, array() );
        $daily[] = array(
            'event' => $event_data['event'],
            'user_id' => $event_data['user_id'],
            'device_id' => $event_data['device_id'],
            'timestamp' => $event_data['timestamp'],
        );
        update_option( 'flavor_analytics_daily_' . $day, $daily, false );
    }

    /**
     * Obtiene eventos por período
     */
    private function get_events_data( $period ) {
        $days = $this->parse_period( $period );
        $events = array();

        // Obtener eventos de los últimos N días
        for ( $i = 0; $i < $days; $i++ ) {
            $day = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
            $daily = get_option( 'flavor_analytics_daily_' . $day, array() );
            $events = array_merge( $events, $daily );
        }

        // También incluir eventos del día actual no procesados
        $current_events = get_option( 'flavor_analytics_events', array() );
        $cutoff = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

        foreach ( $current_events as $event ) {
            if ( substr( $event['timestamp'], 0, 10 ) >= $cutoff ) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Parsea período a días
     */
    private function parse_period( $period ) {
        if ( preg_match( '/^(\d+)d$/', $period, $matches ) ) {
            return (int) $matches[1];
        }
        if ( preg_match( '/^(\d+)w$/', $period, $matches ) ) {
            return (int) $matches[1] * 7;
        }
        if ( preg_match( '/^(\d+)m$/', $period, $matches ) ) {
            return (int) $matches[1] * 30;
        }
        return 7;
    }

    /**
     * Sanitiza propiedades de evento
     */
    private function sanitize_properties( $properties ) {
        if ( ! is_array( $properties ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $properties as $key => $value ) {
            $key = sanitize_key( $key );
            if ( is_array( $value ) ) {
                $sanitized[ $key ] = $this->sanitize_properties( $value );
            } elseif ( is_numeric( $value ) ) {
                $sanitized[ $key ] = $value;
            } else {
                $sanitized[ $key ] = sanitize_text_field( (string) $value );
            }
        }
        return $sanitized;
    }

    /**
     * Obtiene IP del cliente
     */
    private function get_client_ip() {
        $ip = '';
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0];
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var( trim( $ip ), FILTER_VALIDATE_IP ) ?: '';
    }

    /**
     * Detecta plataforma desde User Agent
     */
    private function detect_platform( $ua ) {
        $ua = strtolower( $ua );
        if ( strpos( $ua, 'android' ) !== false ) return 'android';
        if ( strpos( $ua, 'iphone' ) !== false || strpos( $ua, 'ipad' ) !== false ) return 'ios';
        if ( strpos( $ua, 'flutter' ) !== false ) return 'android';
        return 'other';
    }

    /**
     * Limpia datos antiguos
     */
    public function cleanup_old_data() {
        global $wpdb;

        // Eliminar opciones de analytics de hace más de 90 días
        $cutoff = gmdate( 'Y-m-d', strtotime( '-90 days' ) );

        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name < %s",
            'flavor_analytics_daily_%',
            'flavor_analytics_daily_' . $cutoff
        ) );
    }
}

function flavor_analytics_api() {
    return Flavor_Analytics_API::get_instance();
}

add_action( 'plugins_loaded', 'flavor_analytics_api', 15 );
