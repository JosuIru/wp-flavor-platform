<?php
/**
 * API REST Unificada para Apps Móviles - Manifiesto
 *
 * Endpoint único que consolida toda la configuración necesaria para la app.
 * Incluye versionado, validación y sincronización bidireccional.
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API de Manifiesto de App
 */
class Flavor_App_Manifest_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_App_Manifest_API|null
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-app/v2';

    /**
     * Versión del schema
     */
    const SCHEMA_VERSION = '2.0';

    /**
     * Clave de API
     *
     * @var string
     */
    private $api_key = '';

    /**
     * Dependencias entre módulos
     *
     * @var array
     */
    private $module_dependencies = array(
        'grupos_consumo'            => array( 'socios' ),
        'grupos-consumo'            => array( 'socios' ),
        'crowdfunding'              => array( 'socios' ),
        'biblioteca'                => array( 'socios' ),
        'cursos'                    => array( 'socios' ),
        'talleres'                  => array( 'socios' ),
        'presupuestos_participativos' => array( 'socios', 'transparencia' ),
        'presupuestos-participativos' => array( 'socios', 'transparencia' ),
        'banco_tiempo'              => array( 'socios' ),
        'banco-tiempo'              => array( 'socios' ),
        'energia_comunitaria'       => array( 'socios', 'transparencia' ),
        'energia-comunitaria'       => array( 'socios', 'transparencia' ),
        'huertos_urbanos'           => array( 'socios', 'reservas' ),
        'huertos-urbanos'           => array( 'socios', 'reservas' ),
        'carpooling'                => array( 'socios' ),
        'bicicletas_compartidas'    => array( 'reservas' ),
        'bicicletas-compartidas'    => array( 'reservas' ),
        'espacios_comunes'          => array( 'reservas' ),
        'espacios-comunes'          => array( 'reservas' ),
        'circulos_cuidados'         => array( 'socios', 'comunidades' ),
        'circulos-cuidados'         => array( 'socios', 'comunidades' ),
        'colectivos'                => array( 'socios', 'foros' ),
        'economia_don'              => array( 'socios' ),
        'economia-don'              => array( 'socios' ),
    );

    /**
     * Módulos incompatibles entre sí
     *
     * @var array
     */
    private $module_conflicts = array(
        'chat_interno' => array( 'chat_grupos' ), // Solo uno de los dos
    );

    /**
     * Límite de módulos recomendado
     */
    const MAX_MODULES_RECOMMENDED = 15;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_App_Manifest_API
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

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

        // Hook para enviar webhooks de forma asíncrona
        add_action( 'flavor_send_webhook', array( $this, 'process_scheduled_webhook' ), 10, 3 );

        // Hooks para disparar webhooks en cambios de configuración
        add_action( 'update_option_flavor_app_config', array( $this, 'on_config_updated' ), 10, 2 );
        add_action( 'update_option_flavor_apps_config', array( $this, 'on_config_updated' ), 10, 2 );
    }

    /**
     * Procesa un webhook programado
     *
     * @param string $webhook_id ID del webhook.
     * @param string $event      Nombre del evento.
     * @param array  $data       Datos del evento.
     */
    public function process_scheduled_webhook( $webhook_id, $event, $data ) {
        $webhooks = get_option( 'flavor_app_webhooks', array() );

        if ( ! isset( $webhooks[ $webhook_id ] ) ) {
            return;
        }

        $webhook = $webhooks[ $webhook_id ];
        $result = $this->send_webhook_event( $webhook, $event, $data );

        // Actualizar estado del webhook
        $webhooks[ $webhook_id ]['last_called'] = current_time( 'c' );
        $webhooks[ $webhook_id ]['last_status'] = $result['success'] ? 'success' : 'failed';
        update_option( 'flavor_app_webhooks', $webhooks );
    }

    /**
     * Callback cuando se actualiza la configuración
     *
     * @param mixed $old_value Valor anterior.
     * @param mixed $new_value Nuevo valor.
     */
    public function on_config_updated( $old_value, $new_value ) {
        // Detectar qué cambió
        $changes = array();

        if ( is_array( $old_value ) && is_array( $new_value ) ) {
            // Verificar cambios en módulos
            $old_modules = $old_value['modules'] ?? array();
            $new_modules = $new_value['modules'] ?? array();
            if ( $old_modules !== $new_modules ) {
                $changes['modules'] = array(
                    'added'   => array_diff( $new_modules, $old_modules ),
                    'removed' => array_diff( $old_modules, $new_modules ),
                );
                $this->trigger_webhook_event( 'modules.changed', $changes['modules'] );
            }

            // Verificar cambios en tema
            $old_theme = $old_value['theme'] ?? array();
            $new_theme = $new_value['theme'] ?? array();
            if ( $old_theme !== $new_theme ) {
                $this->trigger_webhook_event( 'theme.changed', array(
                    'old_theme' => $old_theme,
                    'new_theme' => $new_theme,
                ) );
            }
        }

        // Siempre disparar evento general de config.updated
        $this->trigger_webhook_event( 'config.updated', array(
            'changes' => $changes,
            'version' => $this->generate_config_version( $new_value ),
        ) );

        // Invalidar caché del manifiesto
        delete_transient( 'flavor_app_manifest_v2' );
    }

    /**
     * Verificar permisos de API
     *
     * @param WP_REST_Request $request Petición.
     * @return bool|WP_Error
     */
    public function check_api_permission( $request ) {
        $api_key = $request->get_header( 'X-VBP-Key' );

        // Usar helper centralizado para verificar API key
        if ( ! flavor_verify_vbp_api_key( $api_key ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'API key inválida', 'flavor-chat-ia' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Permiso público con rate limiting
     *
     * @param WP_REST_Request $request Petición.
     * @return bool
     */
    public function public_permission( $request ) {
        if ( class_exists( 'Flavor_API_Rate_Limiter' ) ) {
            return Flavor_API_Rate_Limiter::check_rate_limit( 'get' );
        }
        return true;
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // === MANIFIESTO UNIFICADO ===

        // Obtener manifiesto completo (público, para apps)
        register_rest_route( self::NAMESPACE, '/manifest', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_manifest' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // Obtener manifiesto con checksum para verificación
        register_rest_route( self::NAMESPACE, '/manifest/check', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'check_manifest_version' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // === SINCRONIZACIÓN ===

        // Confirmar sincronización desde app
        register_rest_route( self::NAMESPACE, '/sync/confirm', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'confirm_sync' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // Estado de sincronización de dispositivos
        register_rest_route( self::NAMESPACE, '/sync/status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_sync_status' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === VALIDACIÓN DE MÓDULOS ===

        // Validar configuración de módulos
        register_rest_route( self::NAMESPACE, '/modules/validate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'validate_modules' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener dependencias de módulos
        register_rest_route( self::NAMESPACE, '/modules/dependencies', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_module_dependencies' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Resolver dependencias automáticamente
        register_rest_route( self::NAMESPACE, '/modules/resolve', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'resolve_module_dependencies' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === CONFIGURACIÓN RÁPIDA ===

        // Configurar app en una sola llamada
        register_rest_route( self::NAMESPACE, '/setup', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'quick_setup' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // === QR CODE SETUP ===

        // Generar QR para setup rápido (requiere autenticación)
        register_rest_route( self::NAMESPACE, '/qr/generate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'generate_qr_code' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Obtener datos de QR (incluye imagen o datos)
        register_rest_route( self::NAMESPACE, '/qr/data', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_qr_data' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Validar código QR escaneado (público, para apps)
        register_rest_route( self::NAMESPACE, '/qr/validate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'validate_qr_code' ),
            'permission_callback' => array( $this, 'public_permission' ),
        ) );

        // === WEBHOOKS ===

        // Listar webhooks configurados
        register_rest_route( self::NAMESPACE, '/webhooks', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_webhooks' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Registrar un nuevo webhook
        register_rest_route( self::NAMESPACE, '/webhooks', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'register_webhook' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Eliminar un webhook
        register_rest_route( self::NAMESPACE, '/webhooks/(?P<id>[a-zA-Z0-9_-]+)', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_webhook' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Probar un webhook
        register_rest_route( self::NAMESPACE, '/webhooks/(?P<id>[a-zA-Z0-9_-]+)/test', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'test_webhook' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );

        // Historial de envíos de webhooks
        register_rest_route( self::NAMESPACE, '/webhooks/history', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_webhook_history' ),
            'permission_callback' => array( $this, 'check_api_permission' ),
        ) );
    }

    /**
     * GET /flavor-app/v2/manifest
     *
     * Devuelve el manifiesto completo de la app.
     * Este endpoint consolida toda la información necesaria.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_manifest( $request ) {
        $force_refresh = $request->get_param( 'refresh' ) === '1';
        $cache_key = 'flavor_app_manifest_v2';
        $cache_duration = 5 * MINUTE_IN_SECONDS;

        // Intentar caché
        if ( ! $force_refresh ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                $cached['from_cache'] = true;
                return rest_ensure_response( $cached );
            }
        }

        $config = get_option( 'flavor_app_config', array() );
        $app_config = get_option( 'flavor_apps_config', array() );

        // Generar versión y checksum
        $config_version = $this->generate_config_version( $config );
        $checksum = $this->generate_config_checksum( $config );

        $manifest = array(
            // Metadatos de versión
            'version'        => $config_version,
            'schema_version' => self::SCHEMA_VERSION,
            'checksum'       => $checksum,
            'generated_at'   => current_time( 'c' ),

            // Información del sitio
            'site' => array(
                'url'         => home_url(),
                'name'        => get_bloginfo( 'name' ),
                'description' => get_bloginfo( 'description' ),
                'language'    => get_locale(),
                'timezone'    => wp_timezone_string(),
            ),

            // Branding de la app
            'branding' => $this->get_branding_data( $config ),

            // Tema y colores
            'theme' => $this->get_theme_data( $config ),

            // Módulos activos con metadata
            'modules' => $this->get_modules_data( $config ),

            // Navegación
            'navigation' => $this->get_navigation_data( $config, $app_config ),

            // Características habilitadas
            'features' => $this->get_features_data( $config, $app_config ),

            // Información de build
            'build' => $this->get_build_data( $config ),

            // Endpoints de API disponibles
            'api_endpoints' => $this->get_api_endpoints(),

            // Estado de sincronización
            'sync' => array(
                'last_update'    => get_option( 'flavor_app_last_update', '' ),
                'update_channel' => 'stable',
            ),

            'from_cache' => false,
        );

        $manifest = $this->sanitize_public_manifest( $manifest );

        // Guardar en caché
        set_transient( $cache_key, $manifest, $cache_duration );

        // Actualizar timestamp de última generación
        update_option( 'flavor_app_last_update', current_time( 'c' ) );

        return rest_ensure_response( $manifest );
    }

    /**
     * GET /flavor-app/v2/manifest/check
     *
     * Verificación rápida de versión sin descargar todo el manifiesto.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function check_manifest_version( $request ) {
        $client_version = $request->get_param( 'version' );
        $client_checksum = $request->get_param( 'checksum' );

        $config = get_option( 'flavor_app_config', array() );
        $current_version = $this->generate_config_version( $config );
        $current_checksum = $this->generate_config_checksum( $config );

        $needs_update = false;
        $reason = 'up_to_date';

        if ( empty( $client_version ) || $client_version !== $current_version ) {
            $needs_update = true;
            $reason = 'version_mismatch';
        } elseif ( ! empty( $client_checksum ) && $client_checksum !== $current_checksum ) {
            $needs_update = true;
            $reason = 'checksum_mismatch';
        }

        return rest_ensure_response( array(
            'needs_update'    => $needs_update,
            'reason'          => $reason,
            'current_version' => $current_version,
            'current_checksum' => $current_checksum,
            'client_version'  => $client_version,
            'client_checksum' => $client_checksum,
        ) );
    }

    /**
     * POST /flavor-app/v2/sync/confirm
     *
     * La app confirma que aplicó una configuración.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function confirm_sync( $request ) {
        $device_id = sanitize_text_field( $request->get_param( 'device_id' ) );
        $config_version = sanitize_text_field( $request->get_param( 'config_version' ) );
        $platform = sanitize_text_field( $request->get_param( 'platform' ) ?? 'unknown' );
        $app_version = sanitize_text_field( $request->get_param( 'app_version' ) ?? '' );

        if ( empty( $device_id ) || empty( $config_version ) ) {
            return new WP_Error(
                'missing_params',
                'Se requiere device_id y config_version',
                array( 'status' => 400 )
            );
        }

        if ( strlen( $device_id ) < 8 || strlen( $device_id ) > 128 ) {
            return new WP_Error(
                'invalid_device_id',
                'device_id inválido',
                array( 'status' => 400 )
            );
        }

        $allowed_platforms = array( 'android', 'ios', 'web', 'unknown' );
        if ( ! in_array( $platform, $allowed_platforms, true ) ) {
            $platform = 'unknown';
        }

        // Obtener registro de dispositivos sincronizados
        $synced_devices = get_option( 'flavor_app_synced_devices', array() );

        // Registrar o actualizar dispositivo
        $synced_devices[ $device_id ] = array(
            'config_version' => $config_version,
            'platform'       => $platform,
            'app_version'    => substr( $app_version, 0, 32 ),
            'synced_at'      => current_time( 'c' ),
            'ip_hash'        => md5( $this->get_client_ip() ),
            'user_agent'     => substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 191 ),
        );

        // Limpiar dispositivos antiguos (más de 30 días)
        $synced_devices = $this->cleanup_old_devices( $synced_devices );

        update_option( 'flavor_app_synced_devices', $synced_devices );

        return rest_ensure_response( array(
            'success'      => true,
            'device_id'    => $device_id,
            'confirmed_at' => current_time( 'c' ),
        ) );
    }

    /**
     * GET /flavor-app/v2/sync/status
     *
     * Estado de sincronización de todos los dispositivos.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_sync_status( $request ) {
        $synced_devices = get_option( 'flavor_app_synced_devices', array() );
        $config = get_option( 'flavor_app_config', array() );
        $current_version = $this->generate_config_version( $config );

        $stats = array(
            'total_devices'     => count( $synced_devices ),
            'up_to_date'        => 0,
            'needs_update'      => 0,
            'by_platform'       => array(),
            'last_sync'         => null,
        );

        $devices_list = array();

        foreach ( $synced_devices as $device_id => $device_info ) {
            $is_current = ( $device_info['config_version'] === $current_version );

            if ( $is_current ) {
                $stats['up_to_date']++;
            } else {
                $stats['needs_update']++;
            }

            $platform = $device_info['platform'] ?? 'unknown';
            if ( ! isset( $stats['by_platform'][ $platform ] ) ) {
                $stats['by_platform'][ $platform ] = 0;
            }
            $stats['by_platform'][ $platform ]++;

            $synced_at = $device_info['synced_at'] ?? '';
            if ( empty( $stats['last_sync'] ) || $synced_at > $stats['last_sync'] ) {
                $stats['last_sync'] = $synced_at;
            }

            $devices_list[] = array(
                'device_id'      => $device_id,
                'platform'       => $platform,
                'app_version'    => $device_info['app_version'] ?? '',
                'config_version' => $device_info['config_version'],
                'is_current'     => $is_current,
                'synced_at'      => $synced_at,
            );
        }

        return rest_ensure_response( array(
            'current_version' => $current_version,
            'stats'           => $stats,
            'devices'         => $devices_list,
        ) );
    }

    /**
     * POST /flavor-app/v2/modules/validate
     *
     * Valida una lista de módulos incluyendo dependencias y conflictos.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function validate_modules( $request ) {
        $modules = $request->get_param( 'modules' );

        if ( ! is_array( $modules ) ) {
            return new WP_Error(
                'invalid_modules',
                'Se requiere un array de módulos',
                array( 'status' => 400 )
            );
        }

        $result = $this->validate_module_list( $modules );

        return rest_ensure_response( $result );
    }

    /**
     * GET /flavor-app/v2/modules/dependencies
     *
     * Devuelve el mapa completo de dependencias.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_module_dependencies( $request ) {
        return rest_ensure_response( array(
            'dependencies' => $this->module_dependencies,
            'conflicts'    => $this->module_conflicts,
            'max_recommended' => self::MAX_MODULES_RECOMMENDED,
        ) );
    }

    /**
     * POST /flavor-app/v2/modules/resolve
     *
     * Dado un conjunto de módulos, devuelve la lista completa
     * incluyendo todas las dependencias necesarias.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function resolve_module_dependencies( $request ) {
        $modules = $request->get_param( 'modules' );

        if ( ! is_array( $modules ) ) {
            return new WP_Error(
                'invalid_modules',
                'Se requiere un array de módulos',
                array( 'status' => 400 )
            );
        }

        $resolved = $this->resolve_dependencies( $modules );

        return rest_ensure_response( array(
            'original_modules' => $modules,
            'resolved_modules' => $resolved['modules'],
            'added_dependencies' => $resolved['added'],
            'total' => count( $resolved['modules'] ),
        ) );
    }

    /**
     * POST /flavor-app/v2/setup
     *
     * Configuración rápida de la app en una sola llamada.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function quick_setup( $request ) {
        $params = $request->get_json_params();
        $results = array();
        $errors = array();

        // 1. Validar y configurar módulos
        if ( isset( $params['modules'] ) ) {
            $modules = $params['modules'];
            $auto_resolve = $params['auto_resolve_dependencies'] ?? true;

            if ( $auto_resolve ) {
                $resolved = $this->resolve_dependencies( $modules );
                $modules = $resolved['modules'];
                $results['modules_resolved'] = $resolved['added'];
            }

            $validation = $this->validate_module_list( $modules );

            if ( ! $validation['valid'] ) {
                $errors['modules'] = $validation['errors'];
            } else {
                $config = get_option( 'flavor_app_config', array() );
                $config['modules'] = $modules;
                update_option( 'flavor_app_config', $config );
                $results['modules'] = $modules;
            }
        }

        // 2. Configurar branding
        if ( isset( $params['branding'] ) ) {
            $config = get_option( 'flavor_app_config', array() );
            $branding_fields = array(
                'app_name', 'app_id', 'app_description',
                'logo_url', 'icon_url', 'splash_url',
            );

            foreach ( $branding_fields as $field ) {
                if ( isset( $params['branding'][ $field ] ) ) {
                    $config['branding'][ $field ] = sanitize_text_field( $params['branding'][ $field ] );
                }
            }

            update_option( 'flavor_app_config', $config );
            $results['branding'] = $config['branding'];
        }

        // 3. Configurar tema (por preset o colores custom)
        if ( isset( $params['theme_preset'] ) ) {
            $preset = $this->apply_theme_preset( $params['theme_preset'] );
            if ( $preset ) {
                $results['theme'] = array( 'preset' => $params['theme_preset'] );
            } else {
                $errors['theme'] = 'Preset no encontrado: ' . $params['theme_preset'];
            }
        } elseif ( isset( $params['theme'] ) ) {
            $config = get_option( 'flavor_app_config', array() );
            $config['theme'] = $this->sanitize_theme( $params['theme'] );
            update_option( 'flavor_app_config', $config );
            $results['theme'] = $config['theme'];
        }

        // 4. Configurar navegación
        if ( isset( $params['navigation'] ) ) {
            $config = get_option( 'flavor_app_config', array() );
            $config['navigation'] = $params['navigation'];
            update_option( 'flavor_app_config', $config );
            $results['navigation'] = 'configured';
        }

        // Invalidar caché
        delete_transient( 'flavor_app_manifest_v2' );
        delete_transient( 'flavor_api_system_info' );

        // Actualizar versión
        $config = get_option( 'flavor_app_config', array() );
        $new_version = $this->generate_config_version( $config );

        return rest_ensure_response( array(
            'success'     => empty( $errors ),
            'new_version' => $new_version,
            'results'     => $results,
            'errors'      => $errors,
        ) );
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Genera versión de configuración basada en contenido
     *
     * @param array $config Configuración.
     * @return string
     */
    private function generate_config_version( $config ) {
        $hash = md5( serialize( $config ) );
        $timestamp = get_option( 'flavor_app_last_update', current_time( 'c' ) );
        $date_part = date( 'Ymd', strtotime( $timestamp ) );

        return 'v' . self::SCHEMA_VERSION . '-' . $date_part . '-' . substr( $hash, 0, 8 );
    }

    /**
     * Genera checksum de configuración
     *
     * @param array $config Configuración.
     * @return string
     */
    private function generate_config_checksum( $config ) {
        return 'sha256:' . hash( 'sha256', serialize( $config ) );
    }

    /**
     * Obtiene datos de branding
     *
     * @param array $config Configuración.
     * @return array
     */
    private function get_branding_data( $config ) {
        $branding = $config['branding'] ?? array();

        return array(
            'app_name'        => $branding['app_name'] ?? get_bloginfo( 'name' ),
            'app_id'          => $branding['app_id'] ?? 'com.flavor.app',
            'app_description' => $branding['app_description'] ?? get_bloginfo( 'description' ),
            'logo_url'        => $branding['logo_url'] ?? $this->get_site_logo_url(),
            'icon_url'        => $branding['icon_url'] ?? '',
            'splash_url'      => $branding['splash_url'] ?? '',
            'developer'       => array(
                'name'    => $branding['developer_name'] ?? '',
                'email'   => $branding['developer_email'] ?? get_option( 'admin_email' ),
                'website' => $branding['developer_website'] ?? home_url(),
            ),
            'legal' => array(
                'privacy_url' => $branding['privacy_policy_url'] ?? '',
                'terms_url'   => $branding['terms_url'] ?? '',
            ),
            'stores' => array(
                'app_store_id'  => $branding['app_store_id'] ?? '',
                'play_store_id' => $branding['play_store_id'] ?? '',
            ),
        );
    }

    /**
     * Sanitiza el manifiesto para exposición pública.
     *
     * @param array $manifest
     * @return array
     */
    private function sanitize_public_manifest( $manifest ) {
        if ( isset( $manifest['branding']['developer']['email'] ) ) {
            unset( $manifest['branding']['developer']['email'] );
        }

        if ( isset( $manifest['branding']['stores']['app_store_id'] ) && '' === $manifest['branding']['stores']['app_store_id'] ) {
            unset( $manifest['branding']['stores']['app_store_id'] );
        }

        if ( isset( $manifest['branding']['stores']['play_store_id'] ) && '' === $manifest['branding']['stores']['play_store_id'] ) {
            unset( $manifest['branding']['stores']['play_store_id'] );
        }

        if ( isset( $manifest['build'] ) ) {
            unset( $manifest['build'] );
        }

        return $manifest;
    }

    /**
     * Obtiene datos de tema
     *
     * @param array $config Configuración.
     * @return array
     */
    private function get_theme_data( $config ) {
        $theme = $config['theme'] ?? $this->get_default_theme();
        $dark_theme = $config['dark_theme'] ?? $this->get_default_dark_theme();

        return array(
            'mode'       => $config['theme_mode'] ?? 'system',
            'light'      => $theme,
            'dark'       => $dark_theme,
            'typography' => array(
                'font_family' => 'system',
                'scale'       => 1.0,
            ),
        );
    }

    /**
     * Obtiene datos de módulos con metadata
     *
     * @param array $config Configuración.
     * @return array
     */
    private function get_modules_data( $config ) {
        $active_modules = $config['modules'] ?? array();
        $all_modules = $this->get_all_modules_catalog();
        $result = array();

        foreach ( $active_modules as $module_id ) {
            $module_id = str_replace( '_', '-', $module_id );
            $catalog_info = $all_modules[ $module_id ] ?? $all_modules[ str_replace( '-', '_', $module_id ) ] ?? array();

            $result[] = array(
                'id'           => $module_id,
                'name'         => $catalog_info['name'] ?? ucwords( str_replace( array( '-', '_' ), ' ', $module_id ) ),
                'description'  => $catalog_info['description'] ?? '',
                'icon'         => $catalog_info['icon'] ?? 'extension',
                'color'        => $catalog_info['color'] ?? '#6366f1',
                'category'     => $catalog_info['category'] ?? 'otros',
                'has_dashboard' => $catalog_info['has_dashboard'] ?? true,
                'dependencies' => $this->module_dependencies[ $module_id ] ?? array(),
            );
        }

        return array(
            'active'    => $result,
            'total'     => count( $result ),
            'available' => count( $all_modules ),
        );
    }

    /**
     * Obtiene datos de navegación
     *
     * @param array $config Configuración.
     * @param array $app_config Configuración de apps.
     * @return array
     */
    private function get_navigation_data( $config, $app_config ) {
        $navigation = $config['navigation'] ?? array();
        $layout = $config['layout'] ?? 'default';

        // Si hay navegación configurada, usarla
        if ( ! empty( $navigation ) ) {
            return array(
                'style'       => $layout,
                'bottom_tabs' => $navigation['bottom_nav'] ?? array(),
                'drawer'      => $navigation['drawer'] ?? array(),
                'show_home'   => $navigation['show_home'] ?? true,
            );
        }

        // Generar navegación desde módulos activos
        $active_modules = $config['modules'] ?? array();
        $all_modules = $this->get_all_modules_catalog();
        $bottom_tabs = array();
        $drawer_items = array();
        $order = 0;

        foreach ( $active_modules as $module_id ) {
            $module_id = str_replace( '_', '-', $module_id );
            $catalog_info = $all_modules[ $module_id ] ?? array();

            $nav_item = array(
                'id'      => $module_id,
                'label'   => $catalog_info['name'] ?? ucwords( str_replace( '-', ' ', $module_id ) ),
                'icon'    => $catalog_info['icon'] ?? 'extension',
                'route'   => '/' . $module_id,
                'order'   => $order++,
                'visible' => true,
            );

            if ( count( $bottom_tabs ) < 5 ) {
                $bottom_tabs[] = $nav_item;
            } else {
                $drawer_items[] = $nav_item;
            }
        }

        return array(
            'style'       => $layout,
            'bottom_tabs' => $bottom_tabs,
            'drawer'      => $drawer_items,
            'show_home'   => true,
        );
    }

    /**
     * Obtiene datos de features
     *
     * @param array $config Configuración.
     * @param array $app_config Configuración de apps.
     * @return array
     */
    private function get_features_data( $config, $app_config ) {
        return array(
            'push_notifications' => $app_config['push_enabled'] ?? false,
            'offline_mode'       => $app_config['offline_mode'] ?? true,
            'biometric_auth'     => $app_config['biometric_auth'] ?? false,
            'dark_mode'          => true,
            'multi_language'     => class_exists( 'Flavor_Multilingual' ),
            'chat_assistant'     => true,
            'qr_scanner'         => $app_config['qr_scanner'] ?? false,
            'deep_linking'       => true,
        );
    }

    /**
     * Obtiene datos de build
     *
     * @param array $config Configuración.
     * @return array
     */
    private function get_build_data( $config ) {
        $build = $config['build'] ?? array();

        return array(
            'version_name'       => $build['version_name'] ?? '1.0.0',
            'version_code'       => $build['version_code'] ?? '1',
            'android' => array(
                'min_sdk'     => $build['android_min_sdk'] ?? '21',
                'target_sdk'  => $build['android_target_sdk'] ?? '34',
                'compile_sdk' => $build['android_compile_sdk'] ?? '34',
            ),
            'ios' => array(
                'min_version'       => $build['ios_min_version'] ?? '12.0',
                'deployment_target' => $build['ios_deployment_target'] ?? '12.0',
            ),
            'flutter' => array(
                'channel' => 'stable',
                'version' => $this->detect_flutter_version(),
            ),
        );
    }

    /**
     * Obtiene endpoints de API disponibles
     *
     * @return array
     */
    private function get_api_endpoints() {
        return array(
            'manifest'  => home_url( '/wp-json/' . self::NAMESPACE . '/manifest' ),
            'sync'      => home_url( '/wp-json/' . self::NAMESPACE . '/sync/confirm' ),
            'chat'      => home_url( '/wp-json/unified-api/v1/chat' ),
            'site_info' => home_url( '/wp-json/app-discovery/v1/info' ),
            'modules'   => home_url( '/wp-json/app-discovery/v1/modules' ),
        );
    }

    /**
     * Valida lista de módulos
     *
     * @param array $modules Lista de módulos.
     * @return array
     */
    private function validate_module_list( $modules ) {
        $errors = array();
        $warnings = array();
        $all_modules = $this->get_all_modules_catalog();
        $valid_ids = array_keys( $all_modules );

        // Normalizar IDs
        $normalized_modules = array();
        foreach ( $modules as $module ) {
            $normalized_modules[] = str_replace( '_', '-', sanitize_key( $module ) );
        }

        // 1. Verificar módulos válidos
        foreach ( $normalized_modules as $module ) {
            $alt_id = str_replace( '-', '_', $module );
            if ( ! in_array( $module, $valid_ids, true ) && ! in_array( $alt_id, $valid_ids, true ) ) {
                $errors[] = array(
                    'type'    => 'invalid_module',
                    'module'  => $module,
                    'message' => "Módulo '$module' no existe",
                );
            }
        }

        // 2. Verificar dependencias
        foreach ( $normalized_modules as $module ) {
            $deps = $this->module_dependencies[ $module ] ?? $this->module_dependencies[ str_replace( '-', '_', $module ) ] ?? array();

            foreach ( $deps as $dep ) {
                $dep_normalized = str_replace( '_', '-', $dep );
                if ( ! in_array( $dep_normalized, $normalized_modules, true ) && ! in_array( $dep, $normalized_modules, true ) ) {
                    $errors[] = array(
                        'type'       => 'missing_dependency',
                        'module'     => $module,
                        'dependency' => $dep,
                        'message'    => "Módulo '$module' requiere '$dep'",
                    );
                }
            }
        }

        // 3. Verificar conflictos
        foreach ( $this->module_conflicts as $module => $conflicts ) {
            if ( in_array( $module, $normalized_modules, true ) ) {
                foreach ( $conflicts as $conflict ) {
                    $conflict_normalized = str_replace( '_', '-', $conflict );
                    if ( in_array( $conflict_normalized, $normalized_modules, true ) || in_array( $conflict, $normalized_modules, true ) ) {
                        $errors[] = array(
                            'type'     => 'conflict',
                            'module'   => $module,
                            'conflict' => $conflict,
                            'message'  => "Módulos '$module' y '$conflict' son incompatibles",
                        );
                    }
                }
            }
        }

        // 4. Warning si excede límite recomendado
        if ( count( $normalized_modules ) > self::MAX_MODULES_RECOMMENDED ) {
            $warnings[] = array(
                'type'    => 'too_many_modules',
                'count'   => count( $normalized_modules ),
                'max'     => self::MAX_MODULES_RECOMMENDED,
                'message' => 'Se recomienda no activar más de ' . self::MAX_MODULES_RECOMMENDED . ' módulos',
            );
        }

        return array(
            'valid'    => empty( $errors ),
            'modules'  => $normalized_modules,
            'errors'   => $errors,
            'warnings' => $warnings,
        );
    }

    /**
     * Resuelve dependencias de módulos
     *
     * @param array $modules Lista de módulos.
     * @return array
     */
    private function resolve_dependencies( $modules ) {
        $resolved = array();
        $added = array();

        // Normalizar
        $normalized = array();
        foreach ( $modules as $module ) {
            $normalized[] = str_replace( '_', '-', sanitize_key( $module ) );
        }

        // Resolver recursivamente
        $to_process = $normalized;
        $processed = array();

        while ( ! empty( $to_process ) ) {
            $module = array_shift( $to_process );

            if ( in_array( $module, $processed, true ) ) {
                continue;
            }

            $processed[] = $module;
            $resolved[] = $module;

            // Obtener dependencias
            $deps = $this->module_dependencies[ $module ] ?? $this->module_dependencies[ str_replace( '-', '_', $module ) ] ?? array();

            foreach ( $deps as $dep ) {
                $dep_normalized = str_replace( '_', '-', $dep );
                if ( ! in_array( $dep_normalized, $processed, true ) && ! in_array( $dep_normalized, $to_process, true ) ) {
                    $to_process[] = $dep_normalized;
                    if ( ! in_array( $dep_normalized, $normalized, true ) ) {
                        $added[] = $dep_normalized;
                    }
                }
            }
        }

        return array(
            'modules' => array_unique( $resolved ),
            'added'   => array_unique( $added ),
        );
    }

    /**
     * Aplica preset de tema
     *
     * @param string $preset_name Nombre del preset.
     * @return bool
     */
    private function apply_theme_preset( $preset_name ) {
        $presets = $this->get_theme_presets();

        if ( ! isset( $presets[ $preset_name ] ) ) {
            return false;
        }

        $preset = $presets[ $preset_name ];
        $config = get_option( 'flavor_app_config', array() );
        $config['theme'] = $preset['light'];
        $config['dark_theme'] = $preset['dark'];

        update_option( 'flavor_app_config', $config );
        delete_transient( 'flavor_app_manifest_v2' );

        return true;
    }

    /**
     * Sanitiza tema
     *
     * @param array $theme Tema.
     * @return array
     */
    private function sanitize_theme( $theme ) {
        $sanitized = array();
        $allowed_keys = array(
            'primary', 'primary_variant', 'secondary', 'secondary_variant',
            'background', 'surface', 'error',
            'on_primary', 'on_secondary', 'on_background', 'on_surface', 'on_error',
        );

        foreach ( $allowed_keys as $key ) {
            if ( isset( $theme[ $key ] ) ) {
                $sanitized[ $key ] = $this->sanitize_color( $theme[ $key ] );
            }
        }

        return $sanitized;
    }

    /**
     * Sanitiza color
     *
     * @param string $color Color.
     * @return string
     */
    private function sanitize_color( $color ) {
        if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
            return strtolower( $color );
        }
        return '#000000';
    }

    /**
     * Limpia dispositivos antiguos
     *
     * @param array $devices Lista de dispositivos.
     * @return array
     */
    private function cleanup_old_devices( $devices ) {
        $cutoff = strtotime( '-30 days' );
        $cleaned = array();

        foreach ( $devices as $device_id => $device_info ) {
            $synced_at = strtotime( $device_info['synced_at'] ?? '1970-01-01' );
            if ( $synced_at > $cutoff ) {
                $cleaned[ $device_id ] = $device_info;
            }
        }

        return $cleaned;
    }

    /**
     * Obtiene IP del cliente
     *
     * @return string
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
        return sanitize_text_field( $ip );
    }

    /**
     * Obtiene URL del logo del sitio
     *
     * @return string
     */
    private function get_site_logo_url() {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        if ( $custom_logo_id ) {
            return wp_get_attachment_image_url( $custom_logo_id, 'full' ) ?: '';
        }
        return '';
    }

    /**
     * Detecta versión de Flutter
     *
     * @return string
     */
    private function detect_flutter_version() {
        $pubspec_path = FLAVOR_CHAT_IA_PATH . 'mobile-apps/pubspec.yaml';

        if ( file_exists( $pubspec_path ) ) {
            $content = file_get_contents( $pubspec_path );
            if ( preg_match( '/sdk:\s*["\']?>=?(\d+\.\d+\.\d+)/i', $content, $matches ) ) {
                return $matches[1];
            }
        }

        return '3.0.0';
    }

    /**
     * Obtiene tema por defecto
     *
     * @return array
     */
    private function get_default_theme() {
        return array(
            'primary'           => '#6366f1',
            'primary_variant'   => '#4f46e5',
            'secondary'         => '#10b981',
            'secondary_variant' => '#059669',
            'background'        => '#ffffff',
            'surface'           => '#f8fafc',
            'error'             => '#ef4444',
            'on_primary'        => '#ffffff',
            'on_secondary'      => '#ffffff',
            'on_background'     => '#1e293b',
            'on_surface'        => '#334155',
            'on_error'          => '#ffffff',
        );
    }

    /**
     * Obtiene tema oscuro por defecto
     *
     * @return array
     */
    private function get_default_dark_theme() {
        return array(
            'primary'           => '#818cf8',
            'primary_variant'   => '#6366f1',
            'secondary'         => '#34d399',
            'secondary_variant' => '#10b981',
            'background'        => '#0f172a',
            'surface'           => '#1e293b',
            'error'             => '#f87171',
            'on_primary'        => '#0f172a',
            'on_secondary'      => '#0f172a',
            'on_background'     => '#f1f5f9',
            'on_surface'        => '#e2e8f0',
            'on_error'          => '#0f172a',
        );
    }

    /**
     * Obtiene presets de tema
     *
     * @return array
     */
    private function get_theme_presets() {
        // Usar la misma definición que class-app-config-api.php
        if ( class_exists( 'Flavor_App_Config_API' ) ) {
            $api = Flavor_App_Config_API::get_instance();
            $response = $api->get_theme_presets( new WP_REST_Request() );
            $data = $response->get_data();
            return $data['presets'] ?? array();
        }

        return array();
    }

    /**
     * Obtiene catálogo de módulos
     *
     * @return array
     */
    private function get_all_modules_catalog() {
        return array(
            // Comunidad
            'eventos'      => array( 'name' => 'Eventos', 'category' => 'comunidad', 'icon' => 'calendar_today', 'color' => '#3b82f6', 'description' => 'Gestión de eventos y actividades', 'has_dashboard' => true ),
            'foros'        => array( 'name' => 'Foros', 'category' => 'comunidad', 'icon' => 'forum', 'color' => '#8b5cf6', 'description' => 'Foros de discusión', 'has_dashboard' => true ),
            'socios'       => array( 'name' => 'Socios', 'category' => 'comunidad', 'icon' => 'people', 'color' => '#10b981', 'description' => 'Gestión de membresías', 'has_dashboard' => true ),
            'comunidades'  => array( 'name' => 'Comunidades', 'category' => 'comunidad', 'icon' => 'groups', 'color' => '#f59e0b', 'description' => 'Comunidades y grupos', 'has_dashboard' => true ),

            // Economía
            'marketplace'     => array( 'name' => 'Marketplace', 'category' => 'economia', 'icon' => 'storefront', 'color' => '#ec4899', 'description' => 'Tienda y productos', 'has_dashboard' => true ),
            'grupos-consumo'  => array( 'name' => 'Grupos de Consumo', 'category' => 'economia', 'icon' => 'shopping_basket', 'color' => '#22c55e', 'description' => 'Consumo colaborativo', 'has_dashboard' => true ),
            'banco-tiempo'    => array( 'name' => 'Banco de Tiempo', 'category' => 'economia', 'icon' => 'schedule', 'color' => '#06b6d4', 'description' => 'Intercambio de servicios', 'has_dashboard' => true ),
            'crowdfunding'    => array( 'name' => 'Crowdfunding', 'category' => 'economia', 'icon' => 'volunteer_activism', 'color' => '#f97316', 'description' => 'Financiación colectiva', 'has_dashboard' => true ),

            // Reservas
            'reservas'           => array( 'name' => 'Reservas', 'category' => 'reservas', 'icon' => 'event_available', 'color' => '#14b8a6', 'description' => 'Sistema de reservas', 'has_dashboard' => true ),
            'espacios-comunes'   => array( 'name' => 'Espacios Comunes', 'category' => 'reservas', 'icon' => 'meeting_room', 'color' => '#6366f1', 'description' => 'Reserva de espacios', 'has_dashboard' => true ),
            'bicicletas-compartidas' => array( 'name' => 'Bicicletas', 'category' => 'reservas', 'icon' => 'pedal_bike', 'color' => '#84cc16', 'description' => 'Bicicletas compartidas', 'has_dashboard' => true ),
            'parkings'           => array( 'name' => 'Parkings', 'category' => 'reservas', 'icon' => 'local_parking', 'color' => '#64748b', 'description' => 'Gestión de parkings', 'has_dashboard' => true ),

            // Formación
            'cursos'     => array( 'name' => 'Cursos', 'category' => 'formacion', 'icon' => 'school', 'color' => '#0ea5e9', 'description' => 'Cursos online', 'has_dashboard' => true ),
            'talleres'   => array( 'name' => 'Talleres', 'category' => 'formacion', 'icon' => 'construction', 'color' => '#a855f7', 'description' => 'Talleres presenciales', 'has_dashboard' => true ),
            'biblioteca' => array( 'name' => 'Biblioteca', 'category' => 'formacion', 'icon' => 'local_library', 'color' => '#78716c', 'description' => 'Préstamo de libros', 'has_dashboard' => true ),

            // Participación
            'encuestas'                  => array( 'name' => 'Encuestas', 'category' => 'participacion', 'icon' => 'poll', 'color' => '#eab308', 'description' => 'Encuestas y votaciones', 'has_dashboard' => true ),
            'presupuestos-participativos' => array( 'name' => 'Presupuestos', 'category' => 'participacion', 'icon' => 'account_balance', 'color' => '#2563eb', 'description' => 'Presupuestos participativos', 'has_dashboard' => true ),
            'campanias'                  => array( 'name' => 'Campañas', 'category' => 'participacion', 'icon' => 'campaign', 'color' => '#dc2626', 'description' => 'Campañas de firmas', 'has_dashboard' => true ),
            'participacion'              => array( 'name' => 'Participación', 'category' => 'participacion', 'icon' => 'how_to_vote', 'color' => '#7c3aed', 'description' => 'Participación ciudadana', 'has_dashboard' => true ),

            // Social
            'red-social'   => array( 'name' => 'Red Social', 'category' => 'social', 'icon' => 'public', 'color' => '#0891b2', 'description' => 'Red social interna', 'has_dashboard' => true ),
            'chat-interno' => array( 'name' => 'Chat', 'category' => 'social', 'icon' => 'chat', 'color' => '#059669', 'description' => 'Mensajería interna', 'has_dashboard' => false ),

            // Movilidad
            'carpooling' => array( 'name' => 'Carpooling', 'category' => 'movilidad', 'icon' => 'directions_car', 'color' => '#16a34a', 'description' => 'Viajes compartidos', 'has_dashboard' => true ),

            // Gestión
            'incidencias'   => array( 'name' => 'Incidencias', 'category' => 'gestion', 'icon' => 'report_problem', 'color' => '#f59e0b', 'description' => 'Gestión de incidencias', 'has_dashboard' => true ),
            'tramites'      => array( 'name' => 'Trámites', 'category' => 'gestion', 'icon' => 'description', 'color' => '#0284c7', 'description' => 'Trámites online', 'has_dashboard' => true ),
            'transparencia' => array( 'name' => 'Transparencia', 'category' => 'gestion', 'icon' => 'visibility', 'color' => '#4f46e5', 'description' => 'Portal de transparencia', 'has_dashboard' => true ),

            // Cultura
            'kulturaka'  => array( 'name' => 'Cultura', 'category' => 'cultura', 'icon' => 'theater_comedy', 'color' => '#db2777', 'description' => 'Eventos culturales', 'has_dashboard' => true ),
            'multimedia' => array( 'name' => 'Multimedia', 'category' => 'cultura', 'icon' => 'video_library', 'color' => '#be123c', 'description' => 'Galería multimedia', 'has_dashboard' => true ),
            'radio'      => array( 'name' => 'Radio', 'category' => 'cultura', 'icon' => 'radio', 'color' => '#9333ea', 'description' => 'Radio comunitaria', 'has_dashboard' => true ),
            'podcast'    => array( 'name' => 'Podcast', 'category' => 'cultura', 'icon' => 'podcasts', 'color' => '#c026d3', 'description' => 'Podcasts', 'has_dashboard' => true ),
        );
    }

    // =========================================================================
    // QR CODE SETUP METHODS
    // =========================================================================

    /**
     * POST /flavor-app/v2/qr/generate
     *
     * Genera un código QR para setup rápido de la app.
     * El QR contiene datos cifrados del sitio.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function generate_qr_code( $request ) {
        $expiration_hours = $request->get_param( 'expiration' ) ?? 24;
        $include_modules = $request->get_param( 'include_modules' ) ?? true;

        // Generar datos del QR
        $qr_data = $this->generate_qr_payload( $expiration_hours, $include_modules );

        if ( is_wp_error( $qr_data ) ) {
            return rest_ensure_response( $qr_data );
        }

        // Guardar código para validación posterior
        $this->store_qr_code( $qr_data['code'], $expiration_hours );

        // Generar URL del QR (puede usar servicio externo o librería local)
        $qr_url = $this->generate_qr_image_url( $qr_data['payload'] );

        return rest_ensure_response( array(
            'success'    => true,
            'code'       => $qr_data['code'],
            'payload'    => $qr_data['payload'],
            'qr_url'     => $qr_url,
            'expires_at' => $qr_data['expires_at'],
            'site'       => array(
                'name' => get_bloginfo( 'name' ),
                'url'  => home_url(),
            ),
            'instructions' => array(
                'es' => 'Escanea este código QR con la app Flavor para conectar automáticamente.',
                'en' => 'Scan this QR code with the Flavor app to connect automatically.',
                'eu' => 'Eskaneatu QR kode hau Flavor aplikazioarekin automatikoki konektatzeko.',
            ),
        ) );
    }

    /**
     * GET /flavor-app/v2/qr/data
     *
     * Obtiene los datos crudos para generar QR externamente.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_qr_data( $request ) {
        $code = $request->get_param( 'code' );

        if ( ! empty( $code ) ) {
            // Verificar código existente
            $stored = get_transient( 'flavor_qr_code_' . $code );
            if ( $stored ) {
                return rest_ensure_response( array(
                    'success' => true,
                    'code'    => $code,
                    'payload' => $stored['payload'],
                    'expires_at' => $stored['expires_at'],
                    'valid'   => true,
                ) );
            }

            return rest_ensure_response( array(
                'success' => false,
                'error'   => 'QR code not found or expired',
            ) );
        }

        // Generar nuevo
        $qr_data = $this->generate_qr_payload( 24, true );
        $this->store_qr_code( $qr_data['code'], 24 );

        return rest_ensure_response( array(
            'success'    => true,
            'code'       => $qr_data['code'],
            'payload'    => $qr_data['payload'],
            'expires_at' => $qr_data['expires_at'],
            'qr_content' => $qr_data['qr_content'],
        ) );
    }

    /**
     * POST /flavor-app/v2/qr/validate
     *
     * Valida un código QR escaneado por la app.
     * Devuelve la configuración completa si es válido.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function validate_qr_code( $request ) {
        $payload = $request->get_param( 'payload' );
        $device_id = $request->get_param( 'device_id' );

        if ( empty( $payload ) ) {
            return new WP_Error(
                'missing_payload',
                'Se requiere el payload del QR',
                array( 'status' => 400 )
            );
        }

        // Decodificar y verificar payload
        $decoded = $this->decode_qr_payload( $payload );

        if ( is_wp_error( $decoded ) ) {
            return rest_ensure_response( array(
                'valid'   => false,
                'error'   => $decoded->get_error_message(),
            ) );
        }

        // Verificar que el código no ha expirado
        if ( isset( $decoded['exp'] ) && $decoded['exp'] < time() ) {
            return rest_ensure_response( array(
                'valid'   => false,
                'error'   => 'QR code has expired',
                'expired_at' => gmdate( 'c', $decoded['exp'] ),
            ) );
        }

        // Verificar que el código existe en la base de datos
        $stored = get_transient( 'flavor_qr_code_' . $decoded['code'] );
        if ( ! $stored ) {
            return rest_ensure_response( array(
                'valid'   => false,
                'error'   => 'QR code not found or already used',
            ) );
        }

        // Registrar dispositivo si se proporciona
        if ( ! empty( $device_id ) ) {
            $this->register_device_from_qr( $device_id, $decoded['code'] );
        }

        // Devolver configuración completa
        $config = get_option( 'flavor_app_config', array() );

        return rest_ensure_response( array(
            'valid'   => true,
            'site'    => array(
                'url'         => home_url(),
                'name'        => get_bloginfo( 'name' ),
                'description' => get_bloginfo( 'description' ),
                'api_base'    => rest_url( self::NAMESPACE ),
            ),
            'manifest_url' => rest_url( self::NAMESPACE . '/manifest' ),
            'modules'      => $decoded['modules'] ?? array(),
            'branding'     => $this->get_branding_data( $config ),
            'theme'        => $this->get_theme_data( $config ),
            'auto_connect' => true,
            'device_registered' => ! empty( $device_id ),
        ) );
    }

    /**
     * Genera el payload del QR code
     *
     * @param int  $expiration_hours Horas de validez.
     * @param bool $include_modules  Incluir módulos.
     * @return array|WP_Error
     */
    private function generate_qr_payload( $expiration_hours = 24, $include_modules = true ) {
        $config = get_option( 'flavor_app_config', array() );

        // Generar código único
        $code = wp_generate_password( 12, false );
        $expires_at = time() + ( $expiration_hours * HOUR_IN_SECONDS );

        // Datos básicos
        $data = array(
            'v'    => 1, // Versión del formato QR
            'code' => $code,
            'url'  => home_url(),
            'name' => substr( get_bloginfo( 'name' ), 0, 50 ),
            'api'  => rest_url( self::NAMESPACE ),
            'exp'  => $expires_at,
        );

        // Incluir módulos si se solicita
        if ( $include_modules && ! empty( $config['modules'] ) ) {
            $data['mods'] = array_slice( $config['modules'], 0, 10 ); // Máximo 10 para no exceder tamaño
        }

        // Incluir tema resumido
        $theme = $config['theme'] ?? array();
        if ( ! empty( $theme['primary'] ) ) {
            $data['color'] = $theme['primary'];
        }

        // Codificar en base64
        $json = wp_json_encode( $data );
        $payload = base64_encode( $json );

        // Generar contenido para el QR (URL con payload)
        $qr_content = add_query_arg( array(
            'action'  => 'flavor_app_setup',
            'payload' => $payload,
        ), home_url( '/wp-json/' . self::NAMESPACE . '/qr/validate' ) );

        return array(
            'code'       => $code,
            'payload'    => $payload,
            'qr_content' => $qr_content,
            'expires_at' => gmdate( 'c', $expires_at ),
            'data'       => $data,
        );
    }

    /**
     * Decodifica un payload de QR
     *
     * @param string $payload Payload codificado.
     * @return array|WP_Error
     */
    private function decode_qr_payload( $payload ) {
        // Intentar decodificar base64
        $decoded_str = base64_decode( $payload, true );

        if ( false === $decoded_str ) {
            return new WP_Error( 'invalid_payload', 'Invalid base64 payload' );
        }

        // Parsear JSON
        $data = json_decode( $decoded_str, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'invalid_json', 'Invalid JSON in payload' );
        }

        // Verificar campos requeridos
        if ( empty( $data['code'] ) || empty( $data['url'] ) ) {
            return new WP_Error( 'missing_fields', 'Missing required fields in QR data' );
        }

        // Verificar que la URL coincide con este sitio
        if ( trailingslashit( $data['url'] ) !== trailingslashit( home_url() ) ) {
            return new WP_Error( 'url_mismatch', 'QR code is for a different site' );
        }

        return $data;
    }

    /**
     * Almacena un código QR para validación posterior
     *
     * @param string $code             Código único.
     * @param int    $expiration_hours Horas de validez.
     */
    private function store_qr_code( $code, $expiration_hours ) {
        $config = get_option( 'flavor_app_config', array() );
        $expires_at = time() + ( $expiration_hours * HOUR_IN_SECONDS );

        $data = array(
            'code'       => $code,
            'created_at' => current_time( 'c' ),
            'expires_at' => gmdate( 'c', $expires_at ),
            'payload'    => base64_encode( wp_json_encode( array(
                'code' => $code,
                'url'  => home_url(),
                'exp'  => $expires_at,
            ) ) ),
            'modules'    => $config['modules'] ?? array(),
        );

        set_transient( 'flavor_qr_code_' . $code, $data, $expiration_hours * HOUR_IN_SECONDS );

        // Registrar en log de QR generados
        $qr_log = get_option( 'flavor_qr_codes_log', array() );
        $qr_log[] = array(
            'code'       => $code,
            'created_at' => current_time( 'c' ),
            'expires_at' => gmdate( 'c', $expires_at ),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        );

        // Mantener solo los últimos 50 registros
        if ( count( $qr_log ) > 50 ) {
            $qr_log = array_slice( $qr_log, -50 );
        }

        update_option( 'flavor_qr_codes_log', $qr_log );
    }

    /**
     * Genera URL de imagen QR
     *
     * @param string $payload Payload para el QR.
     * @return string
     */
    private function generate_qr_image_url( $payload ) {
        // Usar servicio de Google Charts para generar QR
        // En producción se podría usar una librería local como phpqrcode
        $qr_content = add_query_arg( array(
            'action'  => 'flavor_app_setup',
            'payload' => $payload,
        ), home_url( '/wp-json/' . self::NAMESPACE . '/qr/validate' ) );

        $size = 300;
        $qr_url = add_query_arg( array(
            'cht'  => 'qr',
            'chs'  => $size . 'x' . $size,
            'chl'  => urlencode( $qr_content ),
            'choe' => 'UTF-8',
            'chld' => 'M|2', // Error correction level M, margin 2
        ), 'https://chart.googleapis.com/chart' );

        return $qr_url;
    }

    /**
     * Registra un dispositivo que conectó via QR
     *
     * @param string $device_id ID del dispositivo.
     * @param string $qr_code   Código QR usado.
     */
    private function register_device_from_qr( $device_id, $qr_code ) {
        $devices = get_option( 'flavor_app_devices', array() );

        $devices[ $device_id ] = array(
            'device_id'      => $device_id,
            'connected_via'  => 'qr_code',
            'qr_code'        => $qr_code,
            'connected_at'   => current_time( 'c' ),
            'last_seen'      => current_time( 'c' ),
            'platform'       => 'flutter_mobile',
        );

        update_option( 'flavor_app_devices', $devices );

        // Invalidar el código QR después de uso (opcional, quitar para permitir múltiples usos)
        // delete_transient( 'flavor_qr_code_' . $qr_code );
    }

    // =========================================================================
    // WEBHOOK METHODS
    // =========================================================================

    /**
     * GET /flavor-app/v2/webhooks
     *
     * Lista los webhooks configurados.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_webhooks( $request ) {
        $webhooks = get_option( 'flavor_app_webhooks', array() );

        $result = array();
        foreach ( $webhooks as $id => $webhook ) {
            $result[] = array(
                'id'          => $id,
                'name'        => $webhook['name'] ?? '',
                'url'         => $webhook['url'],
                'events'      => $webhook['events'] ?? array( 'config.updated' ),
                'secret'      => ! empty( $webhook['secret'] ) ? '***' : null,
                'active'      => $webhook['active'] ?? true,
                'created_at'  => $webhook['created_at'] ?? '',
                'last_called' => $webhook['last_called'] ?? null,
                'last_status' => $webhook['last_status'] ?? null,
            );
        }

        return rest_ensure_response( array(
            'webhooks' => $result,
            'total'    => count( $result ),
            'events'   => $this->get_available_webhook_events(),
        ) );
    }

    /**
     * POST /flavor-app/v2/webhooks
     *
     * Registra un nuevo webhook.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function register_webhook( $request ) {
        $url = $request->get_param( 'url' );
        $name = $request->get_param( 'name' ) ?? '';
        $events = $request->get_param( 'events' ) ?? array( 'config.updated' );
        $secret = $request->get_param( 'secret' );

        // Validar URL
        if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return new WP_Error(
                'invalid_url',
                'Se requiere una URL válida para el webhook',
                array( 'status' => 400 )
            );
        }

        // Validar eventos
        $valid_events = array_keys( $this->get_available_webhook_events() );
        $events = array_intersect( (array) $events, $valid_events );
        if ( empty( $events ) ) {
            $events = array( 'config.updated' );
        }

        // Generar ID único
        $webhook_id = 'wh_' . wp_generate_password( 12, false );

        // Generar secret si no se proporciona
        if ( empty( $secret ) ) {
            $secret = wp_generate_password( 32, true, true );
        }

        $webhooks = get_option( 'flavor_app_webhooks', array() );
        $webhooks[ $webhook_id ] = array(
            'name'       => sanitize_text_field( $name ),
            'url'        => esc_url_raw( $url ),
            'events'     => $events,
            'secret'     => $secret,
            'active'     => true,
            'created_at' => current_time( 'c' ),
        );

        update_option( 'flavor_app_webhooks', $webhooks );

        return rest_ensure_response( array(
            'success' => true,
            'id'      => $webhook_id,
            'secret'  => $secret,
            'message' => 'Webhook registrado correctamente',
        ) );
    }

    /**
     * DELETE /flavor-app/v2/webhooks/{id}
     *
     * Elimina un webhook.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function delete_webhook( $request ) {
        $webhook_id = $request->get_param( 'id' );
        $webhooks = get_option( 'flavor_app_webhooks', array() );

        if ( ! isset( $webhooks[ $webhook_id ] ) ) {
            return new WP_Error(
                'webhook_not_found',
                'Webhook no encontrado',
                array( 'status' => 404 )
            );
        }

        unset( $webhooks[ $webhook_id ] );
        update_option( 'flavor_app_webhooks', $webhooks );

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Webhook eliminado correctamente',
        ) );
    }

    /**
     * POST /flavor-app/v2/webhooks/{id}/test
     *
     * Prueba un webhook enviando un evento de prueba.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function test_webhook( $request ) {
        $webhook_id = $request->get_param( 'id' );
        $webhooks = get_option( 'flavor_app_webhooks', array() );

        if ( ! isset( $webhooks[ $webhook_id ] ) ) {
            return new WP_Error(
                'webhook_not_found',
                'Webhook no encontrado',
                array( 'status' => 404 )
            );
        }

        $webhook = $webhooks[ $webhook_id ];

        // Enviar evento de prueba
        $result = $this->send_webhook_event( $webhook, 'test', array(
            'message' => 'Este es un evento de prueba',
            'timestamp' => current_time( 'c' ),
        ) );

        // Actualizar último estado
        $webhooks[ $webhook_id ]['last_called'] = current_time( 'c' );
        $webhooks[ $webhook_id ]['last_status'] = $result['success'] ? 'success' : 'failed';
        update_option( 'flavor_app_webhooks', $webhooks );

        return rest_ensure_response( $result );
    }

    /**
     * GET /flavor-app/v2/webhooks/history
     *
     * Obtiene el historial de envíos de webhooks.
     *
     * @param WP_REST_Request $request Petición.
     * @return WP_REST_Response
     */
    public function get_webhook_history( $request ) {
        $limit = (int) ( $request->get_param( 'limit' ) ?? 50 );
        $limit = min( $limit, 200 );

        $history = get_option( 'flavor_app_webhook_history', array() );

        // Ordenar por fecha descendente
        usort( $history, function( $a, $b ) {
            return strtotime( $b['timestamp'] ?? '0' ) - strtotime( $a['timestamp'] ?? '0' );
        } );

        // Limitar resultados
        $history = array_slice( $history, 0, $limit );

        return rest_ensure_response( array(
            'history' => $history,
            'total'   => count( $history ),
        ) );
    }

    /**
     * Envía un evento a un webhook
     *
     * @param array  $webhook  Configuración del webhook.
     * @param string $event    Nombre del evento.
     * @param array  $data     Datos del evento.
     * @return array
     */
    private function send_webhook_event( $webhook, $event, $data ) {
        $payload = array(
            'event'     => $event,
            'site_url'  => home_url(),
            'site_name' => get_bloginfo( 'name' ),
            'timestamp' => current_time( 'c' ),
            'data'      => $data,
        );

        $json_payload = wp_json_encode( $payload );

        // Calcular firma HMAC si hay secret
        $signature = '';
        if ( ! empty( $webhook['secret'] ) ) {
            $signature = hash_hmac( 'sha256', $json_payload, $webhook['secret'] );
        }

        $response = wp_remote_post( $webhook['url'], array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type'       => 'application/json',
                'X-Flavor-Event'     => $event,
                'X-Flavor-Signature' => $signature,
                'X-Flavor-Timestamp' => (string) time(),
                'User-Agent'         => 'Flavor-Webhook/1.0',
            ),
            'body' => $json_payload,
        ) );

        $is_error = is_wp_error( $response );
        $status_code = $is_error ? 0 : wp_remote_retrieve_response_code( $response );
        $success = ! $is_error && $status_code >= 200 && $status_code < 300;

        // Registrar en historial
        $this->log_webhook_event( $webhook, $event, $success, $status_code );

        return array(
            'success'     => $success,
            'status_code' => $status_code,
            'error'       => $is_error ? $response->get_error_message() : null,
            'response'    => $success ? wp_remote_retrieve_body( $response ) : null,
        );
    }

    /**
     * Registra un evento de webhook en el historial
     *
     * @param array  $webhook     Configuración del webhook.
     * @param string $event       Nombre del evento.
     * @param bool   $success     Si fue exitoso.
     * @param int    $status_code Código de estado HTTP.
     */
    private function log_webhook_event( $webhook, $event, $success, $status_code ) {
        $history = get_option( 'flavor_app_webhook_history', array() );

        $history[] = array(
            'webhook_name' => $webhook['name'] ?? 'Sin nombre',
            'webhook_url'  => $webhook['url'],
            'event'        => $event,
            'success'      => $success,
            'status_code'  => $status_code,
            'timestamp'    => current_time( 'c' ),
        );

        // Mantener solo los últimos 200 registros
        if ( count( $history ) > 200 ) {
            $history = array_slice( $history, -200 );
        }

        update_option( 'flavor_app_webhook_history', $history );
    }

    /**
     * Dispara un evento a todos los webhooks suscritos
     *
     * @param string $event Nombre del evento.
     * @param array  $data  Datos del evento.
     */
    public function trigger_webhook_event( $event, $data = array() ) {
        $webhooks = get_option( 'flavor_app_webhooks', array() );

        foreach ( $webhooks as $id => $webhook ) {
            // Verificar que el webhook está activo y suscrito al evento
            if ( empty( $webhook['active'] ) ) {
                continue;
            }

            $events = $webhook['events'] ?? array();
            if ( ! in_array( $event, $events, true ) && ! in_array( '*', $events, true ) ) {
                continue;
            }

            // Enviar de forma asíncrona usando wp_schedule_single_event
            wp_schedule_single_event( time(), 'flavor_send_webhook', array( $id, $event, $data ) );
        }
    }

    /**
     * Obtiene la lista de eventos disponibles para webhooks
     *
     * @return array
     */
    private function get_available_webhook_events() {
        return array(
            'config.updated'    => 'Configuración de app actualizada',
            'modules.changed'   => 'Módulos activos cambiaron',
            'theme.changed'     => 'Tema/colores cambiaron',
            'branding.changed'  => 'Branding actualizado',
            'manifest.updated'  => 'Manifiesto regenerado',
            'device.connected'  => 'Nuevo dispositivo conectado',
            'device.synced'     => 'Dispositivo sincronizado',
            'qr.generated'      => 'Código QR generado',
            'qr.used'           => 'Código QR utilizado',
            'test'              => 'Evento de prueba',
            '*'                 => 'Todos los eventos',
        );
    }
}

/**
 * Función helper para obtener instancia
 *
 * @return Flavor_App_Manifest_API
 */
function flavor_app_manifest_api() {
    return Flavor_App_Manifest_API::get_instance();
}

// Inicializar
add_action( 'plugins_loaded', 'flavor_app_manifest_api', 15 );
