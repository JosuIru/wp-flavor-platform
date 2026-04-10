<?php
/**
 * Flavor Platform - Plataforma de comunidades, IA y herramientas para WordPress
 *
 * @package FlavorPlatform
 *
 * Plugin Name: Flavor Platform
 * Plugin URI: https://gailu.net
 * Description: Plataforma integral para WordPress: Red de Comunidades, Asistente IA, Page Builder, Deep Links, Matching, Newsletter, Sellos de Calidad y más.
 * Version: 3.5.0
 * Author: Gailu Labs
 * Author URI: https://gailu.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flavor-platform
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.4
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Evitar carga múltiple
if (defined('FLAVOR_CHAT_IA_LOADED')) {
    return;
}
define('FLAVOR_CHAT_IA_LOADED', true);
if (!defined('FLAVOR_PLATFORM_LOADED')) {
    define('FLAVOR_PLATFORM_LOADED', true);
}

/**
 * Desactivar display de errores para feeds y REST API
 * Esto evita que los notices de PHP rompan las respuestas XML/JSON
 * Solo en requests que requieren output limpio (feeds, REST, AJAX)
 */
if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $is_flavor_settings_save = (
        isset($_SERVER['REQUEST_METHOD'], $_POST['flavor_chat_ia_action'])
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && $_POST['flavor_chat_ia_action'] === 'save_settings'
    );
    $is_feed_or_api_request = (
        strpos($request_uri, '/wp-json/') !== false ||
        strpos($request_uri, '/feed/') !== false ||
        strpos($request_uri, 'feed=') !== false ||
        (defined('DOING_AJAX') && DOING_AJAX) ||
        (defined('REST_REQUEST') && REST_REQUEST) ||
        (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) ||
        preg_match('/\.(xml|rss|atom|json)$/', $request_uri)
    );

    if ($is_feed_or_api_request || $is_flavor_settings_save) {
        @ini_set('display_errors', 0);
    }
}

// Constantes del plugin (con checks para evitar redefinición en tests)
if (!defined('FLAVOR_RUNTIME_PLUGIN_FILE')) {
    define('FLAVOR_RUNTIME_PLUGIN_FILE', __FILE__);
}
if (!defined('FLAVOR_RUNTIME_PLUGIN_BASENAME')) {
    define('FLAVOR_RUNTIME_PLUGIN_BASENAME', plugin_basename(FLAVOR_RUNTIME_PLUGIN_FILE));
}

// Constantes principales - Flavor Platform (nuevo naming)
if (!defined('FLAVOR_PLATFORM_VERSION')) {
    define('FLAVOR_PLATFORM_VERSION', '3.5.0');
}
if (!defined('FLAVOR_PLATFORM_PATH')) {
    define('FLAVOR_PLATFORM_PATH', plugin_dir_path(__FILE__));
}
if (!defined('FLAVOR_PLATFORM_URL')) {
    define('FLAVOR_PLATFORM_URL', plugin_dir_url(__FILE__));
}
if (!defined('FLAVOR_PLATFORM_BASENAME')) {
    define('FLAVOR_PLATFORM_BASENAME', plugin_basename(__FILE__));
}
if (!defined('FLAVOR_PLATFORM_FILE')) {
    define('FLAVOR_PLATFORM_FILE', __FILE__);
}
if (!defined('FLAVOR_PLATFORM_PLUGIN_SLUG')) {
    define('FLAVOR_PLATFORM_PLUGIN_SLUG', 'flavor-platform');
}
if (!defined('FLAVOR_PLATFORM_MAIN_FILE')) {
    define('FLAVOR_PLATFORM_MAIN_FILE', 'flavor-platform.php');
}

// Alias legacy - Flavor Chat IA (compatibilidad retroactiva)
// Mantienen el nombre técnico legado sin romper instalaciones existentes.
if (!defined('FLAVOR_CHAT_IA_VERSION')) {
    define('FLAVOR_CHAT_IA_VERSION', FLAVOR_PLATFORM_VERSION);
}
if (!defined('FLAVOR_CHAT_IA_PATH')) {
    define('FLAVOR_CHAT_IA_PATH', FLAVOR_PLATFORM_PATH);
}
if (!defined('FLAVOR_CHAT_IA_URL')) {
    define('FLAVOR_CHAT_IA_URL', FLAVOR_PLATFORM_URL);
}
if (!defined('FLAVOR_CHAT_IA_BASENAME')) {
    define('FLAVOR_CHAT_IA_BASENAME', FLAVOR_PLATFORM_BASENAME);
}
if (!defined('FLAVOR_CHAT_IA_FILE')) {
    define('FLAVOR_CHAT_IA_FILE', FLAVOR_PLATFORM_FILE);
}
if (!defined('FLAVOR_CHAT_IA_PLUGIN_SLUG')) {
    define('FLAVOR_CHAT_IA_PLUGIN_SLUG', 'flavor-platform'); // Migrado: ahora apunta al nuevo slug
}
if (!defined('FLAVOR_CHAT_IA_MAIN_FILE')) {
    define('FLAVOR_CHAT_IA_MAIN_FILE', 'flavor-platform.php'); // Migrado: ahora apunta al nuevo archivo
}
// Constantes de configuración - principales
if (!defined('FLAVOR_PLATFORM_SETTINGS_OPTION')) {
    define('FLAVOR_PLATFORM_SETTINGS_OPTION', 'flavor_platform_settings');
}
if (!defined('FLAVOR_PLATFORM_TEXT_DOMAIN')) {
    define('FLAVOR_PLATFORM_TEXT_DOMAIN', 'flavor-platform');
}
if (!defined('FLAVOR_PLATFORM_REST_NAMESPACE')) {
    define('FLAVOR_PLATFORM_REST_NAMESPACE', 'flavor-platform/v1');
}
if (!defined('FLAVOR_PLATFORM_DEBUG')) {
    define('FLAVOR_PLATFORM_DEBUG', defined('WP_DEBUG') && WP_DEBUG);
}

// Constantes de configuración - alias legacy
if (!defined('FLAVOR_CHAT_IA_SETTINGS_OPTION')) {
    define('FLAVOR_CHAT_IA_SETTINGS_OPTION', 'flavor_chat_ia_settings');
}
if (!defined('FLAVOR_CHAT_IA_TEXT_DOMAIN')) {
    define('FLAVOR_CHAT_IA_TEXT_DOMAIN', 'flavor-chat-ia');
}
if (!defined('FLAVOR_CHAT_IA_REST_NAMESPACE')) {
    define('FLAVOR_CHAT_IA_REST_NAMESPACE', 'flavor-chat-ia/v1');
}
if (!defined('FLAVOR_CHAT_IA_DEBUG')) {
    define('FLAVOR_CHAT_IA_DEBUG', FLAVOR_PLATFORM_DEBUG);
}

// Límite máximo de posts por query (evita cargas masivas de memoria)
// Puede sobrescribirse en wp-config.php: define('FLAVOR_MAX_POSTS_PER_QUERY', 500);
if (!defined('FLAVOR_MAX_POSTS_PER_QUERY')) {
    define('FLAVOR_MAX_POSTS_PER_QUERY', 200);
}

/**
 * Logging seguro con niveles y control por entorno
 *
 * Niveles (de menor a mayor severidad):
 * - debug: Solo en desarrollo, información detallada para depuración
 * - info: Información operativa general
 * - warning: Situaciones que requieren atención pero no son críticas
 * - error: Errores que requieren intervención
 *
 * @param string $message Mensaje a loguear
 * @param string $level Nivel: 'debug', 'info', 'warning', 'error'
 * @param string $module Módulo origen (opcional, para filtrado)
 */
function flavor_platform_log( $message, $level = 'info', $module = '' ) {
    // Niveles y su prioridad numérica
    $level_priority = [
        'debug'   => 0,
        'info'    => 1,
        'warning' => 2,
        'error'   => 3,
    ];

    // Nivel mínimo a loguear según entorno
    // En producción (sin WP_DEBUG): solo errores
    // En desarrollo (WP_DEBUG): todo
    // Con FLAVOR_PLATFORM_DEBUG: nivel configurable
    $min_level = 'error'; // Default: solo errores

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $min_level = 'debug'; // Desarrollo: todo
    }

    // Permitir override con constante específica
    if ( defined( 'FLAVOR_LOG_LEVEL' ) ) {
        $min_level = FLAVOR_LOG_LEVEL;
    }

    // Verificar si el nivel actual cumple el mínimo
    $current_priority = isset( $level_priority[ $level ] ) ? $level_priority[ $level ] : 1;
    $min_priority = isset( $level_priority[ $min_level ] ) ? $level_priority[ $min_level ] : 3;

    if ( $current_priority < $min_priority ) {
        return;
    }

    // Construir prefijo
    $prefix = '[Flavor ' . strtoupper( $level ) . ']';
    if ( ! empty( $module ) ) {
        $prefix .= ' [' . $module . ']';
    }

    // FIX: Sanitizar mensaje para no exponer secrets (API keys, tokens, passwords)
    $sanitized_message = preg_replace(
        [
            '/([a-f0-9]{32,})/i',                           // Hashes MD5, API keys hex
            '/([A-Za-z0-9_-]{20,})/i',                      // Tokens largos alfanuméricos
            '/(Bearer\s+[a-zA-Z0-9._-]+)/i',                // Bearer tokens
            '/(api[_-]?key[=:]\s*)[^\s&]+/i',               // api_key=value
            '/(password[=:]\s*)[^\s&]+/i',                  // password=value
            '/(secret[=:]\s*)[^\s&]+/i',                    // secret=value
            '/(X-VBP-Key[=:]\s*)[^\s&]+/i',                 // X-VBP-Key header
        ],
        [
            '[REDACTED_HASH]',
            '[REDACTED_TOKEN]',
            'Bearer [REDACTED]',
            '$1[REDACTED]',
            '$1[REDACTED]',
            '$1[REDACTED]',
            '$1[REDACTED]',
        ],
        $message
    );

    error_log( $prefix . ' ' . $sanitized_message );
}

/**
 * Alias legacy de logging para compatibilidad retroactiva.
 *
 * @param string $message Mensaje a loguear.
 * @param string $level Nivel: 'debug', 'info', 'warning', 'error'.
 * @param string $module Módulo origen.
 */
function flavor_chat_ia_log( $message, $level = 'info', $module = '' ) {
    flavor_platform_log( $message, $level, $module );
}

/**
 * Devuelve el option name principal para la configuración de plataforma.
 *
 * @return string
 */
function flavor_get_primary_settings_option() {
    return FLAVOR_PLATFORM_SETTINGS_OPTION;
}

/**
 * Devuelve el text domain principal para código nuevo.
 *
 * @return string
 */
function flavor_get_primary_text_domain() {
    return FLAVOR_PLATFORM_TEXT_DOMAIN;
}

/**
 * Devuelve el namespace REST principal para código nuevo.
 *
 * @return string
 */
function flavor_get_primary_rest_namespace() {
    return FLAVOR_PLATFORM_REST_NAMESPACE;
}

/**
 * Obtiene el slug técnico actual en runtime.
 *
 * Se mantiene legacy hasta migrar carpeta y archivo principal.
 *
 * @return string
 */
function flavor_get_runtime_plugin_slug() {
    return FLAVOR_CHAT_IA_PLUGIN_SLUG;
}

/**
 * Obtiene el slug de distribución/comercial.
 *
 * @return string
 */
function flavor_get_distribution_plugin_slug() {
    return FLAVOR_PLATFORM_PLUGIN_SLUG;
}

/**
 * Obtiene el basename técnico actual del plugin en runtime.
 *
 * @return string
 */
function flavor_get_runtime_plugin_basename() {
    return FLAVOR_RUNTIME_PLUGIN_BASENAME;
}

/**
 * Obtiene la ruta absoluta del archivo principal runtime.
 *
 * @return string
 */
function flavor_get_runtime_plugin_file() {
    return FLAVOR_RUNTIME_PLUGIN_FILE;
}

/**
 * Obtiene el basename previsto para artefactos de distribución.
 *
 * @return string
 */
function flavor_get_distribution_plugin_basename() {
    return FLAVOR_PLATFORM_PLUGIN_SLUG . '/' . FLAVOR_PLATFORM_MAIN_FILE;
}

/**
 * Resuelve el nombre de clase runtime preferido para compatibilidad dual.
 *
 * Cuando existe un alias o una clase canónica `Flavor_Platform_*`, la usa.
 * Si todavía no existe, devuelve el nombre legacy `Flavor_Chat_*` para no
 * romper integraciones durante la migración profunda.
 *
 * @param string $legacy_class Nombre de clase legacy.
 * @return string
 */
function flavor_get_runtime_class_name( $legacy_class ) {
    if ( ! is_string( $legacy_class ) || $legacy_class === '' ) {
        return $legacy_class;
    }

    if ( strpos( $legacy_class, 'Flavor_Chat_' ) !== 0 ) {
        return $legacy_class;
    }

    $platform_class = 'Flavor_Platform_' . substr( $legacy_class, strlen( 'Flavor_Chat_' ) );

    if ( class_exists( $platform_class, false ) || interface_exists( $platform_class, false ) ) {
        return $platform_class;
    }

    return $legacy_class;
}

/**
 * Devuelve los namespaces REST soportados para un namespace dado.
 *
 * @param string $namespace Namespace solicitado.
 * @return array
 */
function flavor_get_rest_namespaces( $namespace ) {
    if ( $namespace === FLAVOR_CHAT_IA_REST_NAMESPACE || $namespace === FLAVOR_PLATFORM_REST_NAMESPACE ) {
        return array(
            FLAVOR_CHAT_IA_REST_NAMESPACE,
            FLAVOR_PLATFORM_REST_NAMESPACE,
        );
    }

    return array( $namespace );
}

/**
 * Registra una ruta REST en todos los namespaces equivalentes soportados.
 *
 * @param string $route_namespace Namespace REST.
 * @param string $route           Ruta.
 * @param array  $args            Argumentos de registro.
 * @param bool   $override        Si se permite override.
 * @return void
 */
function flavor_register_rest_route( $route_namespace, $route, $args = array(), $override = false ) {
    foreach ( flavor_get_rest_namespaces( $route_namespace ) as $namespace ) {
        register_rest_route( $namespace, $route, $args, $override );
    }
}

/**
 * Obtiene los settings principales con compatibilidad entre nombre legado y nuevo.
 *
 * @return array
 */
function flavor_get_main_settings() {
    $platform_settings = get_option( FLAVOR_PLATFORM_SETTINGS_OPTION, null );
    if ( is_array( $platform_settings ) ) {
        return $platform_settings;
    }

    $legacy_settings = get_option( FLAVOR_CHAT_IA_SETTINGS_OPTION, array() );
    return is_array( $legacy_settings ) ? $legacy_settings : array();
}

/**
 * Persiste los settings principales en la opción legacy y en la nueva.
 *
 * @param array $settings Settings a guardar.
 * @param bool  $autoload Autoload de la opción.
 * @return bool True si AMBAS opciones se actualizaron correctamente.
 */
function flavor_update_main_settings( array $settings, $autoload = true ) {
    $updated_legacy = update_option( FLAVOR_CHAT_IA_SETTINGS_OPTION, $settings, $autoload );
    $updated_platform = update_option( FLAVOR_PLATFORM_SETTINGS_OPTION, $settings, $autoload );

    wp_cache_delete( FLAVOR_CHAT_IA_SETTINGS_OPTION, 'options' );
    wp_cache_delete( FLAVOR_PLATFORM_SETTINGS_OPTION, 'options' );

    // FIX: Invalidar también el cache estático
    flavor_invalidate_settings_cache( 'main' );

    // FIX: Retornar true solo si ambas opciones se actualizaron
    return $updated_legacy && $updated_platform;
}

/**
 * Devuelve los nombres de opción soportados para la configuración de un módulo.
 *
 * @param string $module_id ID del módulo.
 * @return array{legacy:string,platform:string}
 */
function flavor_get_module_option_names( $module_id ) {
    // FIX: Validar tipo y longitud del module_id
    $module_id = (string) $module_id;

    // Longitud máxima razonable para un ID de módulo (50 caracteres)
    if ( empty( $module_id ) || strlen( $module_id ) > 50 ) {
        return array(
            'legacy'   => 'flavor_chat_ia_module_invalid',
            'platform' => 'flavor_platform_module_invalid',
        );
    }

    $normalized_module_id = str_replace( '-', '_', sanitize_key( $module_id ) );

    // FIX: Verificar que después de sanitizar sigue siendo válido
    if ( empty( $normalized_module_id ) ) {
        return array(
            'legacy'   => 'flavor_chat_ia_module_invalid',
            'platform' => 'flavor_platform_module_invalid',
        );
    }

    return array(
        'legacy'   => 'flavor_chat_ia_module_' . $normalized_module_id,
        'platform' => 'flavor_platform_module_' . $normalized_module_id,
    );
}

/**
 * Obtiene la configuración de un módulo con compatibilidad entre nombre legado y nuevo.
 *
 * @param string $module_id ID del módulo.
 * @return array
 */
function flavor_get_module_settings( $module_id ) {
    $option_names = flavor_get_module_option_names( $module_id );

    $platform_settings = get_option( $option_names['platform'], null );
    if ( is_array( $platform_settings ) ) {
        return $platform_settings;
    }

    $legacy_settings = get_option( $option_names['legacy'], array() );
    return is_array( $legacy_settings ) ? $legacy_settings : array();
}

/**
 * Persiste la configuración de un módulo en la opción legacy y en la nueva.
 *
 * @param string $module_id ID del módulo.
 * @param array  $settings  Configuración del módulo.
 * @param bool   $autoload  Autoload de la opción.
 * @return bool
 */
function flavor_update_module_settings( $module_id, array $settings, $autoload = true ) {
    $option_names = flavor_get_module_option_names( $module_id );

    $updated_legacy = update_option( $option_names['legacy'], $settings, $autoload );
    $updated_platform = update_option( $option_names['platform'], $settings, $autoload );

    wp_cache_delete( $option_names['legacy'], 'options' );
    wp_cache_delete( $option_names['platform'], 'options' );
    flavor_invalidate_settings_cache( $module_id );

    return $updated_legacy && $updated_platform;
}

/**
 * Sincroniza la opción nueva cuando se actualiza la legacy.
 *
 * @param array $old_value Valor anterior.
 * @param array $value     Valor nuevo.
 * @return void
 */
function flavor_sync_platform_settings_option( $old_value, $value ) {
    if ( is_array( $value ) ) {
        update_option( FLAVOR_PLATFORM_SETTINGS_OPTION, $value );
        wp_cache_delete( FLAVOR_PLATFORM_SETTINGS_OPTION, 'options' );
    }
}

add_action( 'update_option_' . FLAVOR_CHAT_IA_SETTINGS_OPTION, 'flavor_sync_platform_settings_option', 10, 2 );

/**
 * Carga el dominio legado y el nuevo dominio comercial.
 *
 * Mientras el código siga usando mayoritariamente `flavor-chat-ia`,
 * mantenemos ambos dominios activos. Si todavía no existe un archivo `.mo`
 * con el nombre nuevo, reutilizamos el archivo legado para que la transición
 * no deje el plugin sin traducciones.
 *
 * @return void
 */
function flavor_load_textdomains() {
    $languages_rel_path = dirname( FLAVOR_PLATFORM_BASENAME ) . '/languages/';

    load_plugin_textdomain(
        FLAVOR_CHAT_IA_TEXT_DOMAIN,
        false,
        $languages_rel_path
    );

    load_plugin_textdomain(
        FLAVOR_PLATFORM_TEXT_DOMAIN,
        false,
        $languages_rel_path
    );

    $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
    if ( ! is_string( $locale ) || $locale === '' ) {
        return;
    }

    $domain_registry = $GLOBALS['l10n'] ?? [];
    if ( isset( $domain_registry[ FLAVOR_PLATFORM_TEXT_DOMAIN ] ) ) {
        return;
    }

    $new_domain_mofile = FLAVOR_PLATFORM_PATH . 'languages/' . FLAVOR_PLATFORM_TEXT_DOMAIN . '-' . $locale . '.mo';
    if ( file_exists( $new_domain_mofile ) ) {
        load_textdomain( FLAVOR_PLATFORM_TEXT_DOMAIN, $new_domain_mofile );
        return;
    }

    $legacy_domain_mofile = FLAVOR_PLATFORM_PATH . 'languages/' . FLAVOR_CHAT_IA_TEXT_DOMAIN . '-' . $locale . '.mo';
    if ( file_exists( $legacy_domain_mofile ) ) {
        load_textdomain( FLAVOR_PLATFORM_TEXT_DOMAIN, $legacy_domain_mofile );
    }
}

/**
 * Shorthand para log de debug (solo en desarrollo)
 */
function flavor_log_debug( $message, $module = '' ) {
    flavor_chat_ia_log( $message, 'debug', $module );
}

/**
 * Shorthand para log de error (siempre se loguea)
 */
function flavor_log_error( $message, $module = '' ) {
    flavor_chat_ia_log( $message, 'error', $module );
}

/**
 * Obtiene la API key de VBP de forma segura
 *
 * Prioridad:
 * 1. Key configurada en opciones (flavor_vbp_settings['api_key'])
 * 2. Key única generada por instalación (usando NONCE_SALT)
 * 3. En desarrollo con FLAVOR_VBP_ALLOW_LEGACY_KEY: permite 'flavor-vbp-2024'
 *
 * @global string|null $flavor_vbp_api_key_cache Cache de la API key.
 * @return string La API key válida
 */
function flavor_get_vbp_api_key() {
    global $flavor_vbp_api_key_cache;

    if ( null !== $flavor_vbp_api_key_cache ) {
        return $flavor_vbp_api_key_cache;
    }

    $settings = flavor_get_cached_settings( 'vbp' );

    // Prioridad 1: Key configurada explícitamente
    if ( ! empty( $settings['api_key'] ) ) {
        $flavor_vbp_api_key_cache = $settings['api_key'];
        return $flavor_vbp_api_key_cache;
    }

    // Prioridad 2: Key única por instalación (segura)
    $flavor_vbp_api_key_cache = wp_hash( 'flavor-vbp-' . NONCE_SALT );
    return $flavor_vbp_api_key_cache;
}

/**
 * Verifica si una API key es válida para VBP
 *
 * FIX v3.5.1: Eliminada key legacy hardcodeada, agregado rate limiting.
 *
 * @param string $key Key a verificar.
 * @return bool True si la key es válida.
 */
function flavor_verify_vbp_api_key( $key ) {
    if ( empty( $key ) ) {
        return false;
    }

    // Rate limiting: máximo 5 intentos fallidos por IP en 5 minutos
    $remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'unknown';
    $transient_key = 'flavor_vbp_auth_attempts_' . md5( $remote_ip );
    $attempts = (int) get_transient( $transient_key );

    if ( $attempts >= 5 ) {
        flavor_log_warning( 'VBP API: Rate limit alcanzado para IP ' . $remote_ip, 'Security' );
        return false;
    }

    // Verificar contra key configurada
    $valid_key = flavor_get_vbp_api_key();
    if ( hash_equals( $valid_key, $key ) ) {
        // Reset contador en auth exitoso
        delete_transient( $transient_key );
        return true;
    }

    // Incrementar contador de intentos fallidos
    set_transient( $transient_key, $attempts + 1, 5 * MINUTE_IN_SECONDS );
    flavor_log_debug( 'VBP API: Intento de autenticación fallido desde ' . $remote_ip, 'Security' );

    return false;
}

/**
 * Almacén de cache de settings compartido entre funciones.
 *
 * FIX: Usar variable global en lugar de static para sincronizar
 * entre flavor_get_cached_settings() y flavor_invalidate_settings_cache().
 *
 * @since 3.5.1
 * @global array $flavor_settings_cache
 */
global $flavor_settings_cache;
$flavor_settings_cache = array();

/**
 * Obtiene los settings del plugin con cache estático
 *
 * Evita múltiples llamadas a get_option() en la misma request.
 * WordPress ya cachea opciones, pero esto elimina overhead adicional.
 *
 * @param string $option_name Nombre de la opción ('vbp' o 'main').
 * @return array Settings cacheados.
 */
function flavor_get_cached_settings( $option_name = 'main' ) {
    global $flavor_settings_cache;

    if ( isset( $flavor_settings_cache[ $option_name ] ) ) {
        return $flavor_settings_cache[ $option_name ];
    }

    switch ( $option_name ) {
        case 'vbp':
            $flavor_settings_cache[ $option_name ] = get_option( 'flavor_vbp_settings', array() );
            break;
        case 'main':
        default:
            $flavor_settings_cache[ $option_name ] = flavor_get_main_settings();
            break;
    }

    return $flavor_settings_cache[ $option_name ];
}

/**
 * Invalida el cache de settings (usar después de update_option)
 *
 * @param string $option_name Nombre de la opción a invalidar, o 'all'.
 */
function flavor_invalidate_settings_cache( $option_name = 'all' ) {
    global $flavor_settings_cache;

    if ( $option_name === 'all' ) {
        $flavor_settings_cache = array();
    } else {
        unset( $flavor_settings_cache[ $option_name ] );
    }
}

/**
 * Obtiene el límite seguro de posts por query
 *
 * Evita cargas masivas de memoria en queries sin límite.
 * Usar en lugar de posts_per_page => -1 en APIs y admin.
 *
 * @param int $requested Límite solicitado (-1 para "todos").
 * @param int $max       Límite máximo personalizado (opcional).
 * @return int Límite seguro a usar.
 */
function flavor_safe_posts_limit( $requested = -1, $max = null ) {
    $max_limit = $max ?? FLAVOR_MAX_POSTS_PER_QUERY;

    if ( $requested === -1 || $requested > $max_limit ) {
        return $max_limit;
    }

    return max( 1, (int) $requested );
}

/**
 * Indica si la automatización VBP externa está habilitada para un scope.
 *
 * Scopes actuales:
 * - site_builder: creación/configuración remota de sitios
 * - claude_batch: operaciones batch del editor VBP
 *
 * @param string $scope Scope a consultar.
 * @return bool
 */
function flavor_vbp_automation_enabled( $scope = 'default' ) {
    $settings = flavor_get_cached_settings( 'vbp' );

    $enabled = true;
    if ( array_key_exists( 'enable_automation_api', $settings ) ) {
        $enabled = ! empty( $settings['enable_automation_api'] );
    }

    /**
     * Permite desactivar scopes concretos desde código sin tocar la UI.
     *
     * @param bool   $enabled  Estado calculado.
     * @param string $scope    Scope solicitado.
     * @param array  $settings Ajustes actuales de VBP.
     */
    return (bool) apply_filters( 'flavor_vbp_automation_enabled', $enabled, $scope, $settings );
}

/**
 * Devuelve los scopes permitidos para la API de automatización VBP.
 *
 * @return string[]
 */
function flavor_get_vbp_automation_scopes() {
    $settings = flavor_get_cached_settings( 'vbp' );
    $scopes   = $settings['automation_scopes'] ?? array( 'site_builder', 'claude_batch' );

    if ( ! is_array( $scopes ) ) {
        $scopes = array( 'site_builder', 'claude_batch' );
    }

    $scopes = array_filter( array_map( 'sanitize_key', $scopes ) );

    /**
     * Permite ajustar scopes autorizados para la API externa.
     *
     * @param string[] $scopes   Scopes autorizados.
     * @param array    $settings Ajustes actuales de VBP.
     */
    $scopes = apply_filters( 'flavor_vbp_automation_scopes', $scopes, $settings );

    if ( ! is_array( $scopes ) ) {
        return array( 'site_builder', 'claude_batch' );
    }

    return array_values( array_unique( array_filter( array_map( 'sanitize_key', $scopes ) ) ) );
}

/**
 * Verifica acceso a automatización VBP por clave y scope.
 *
 * @param string $key   API key recibida.
 * @param string $scope Scope solicitado.
 * @return bool
 */
if ( ! function_exists( 'flavor_check_vbp_automation_access' ) ) {
    function flavor_check_vbp_automation_access( $key, $scope ) {
        $scope = sanitize_key( (string) $scope );
        if ( '' === $scope ) {
            return false;
        }

        if ( ! flavor_vbp_automation_enabled( $scope ) ) {
            return false;
        }

        if ( ! flavor_verify_vbp_api_key( $key ) ) {
            return false;
        }

        return in_array( $scope, flavor_get_vbp_automation_scopes(), true );
    }
}

/**
 * Extrae la API key VBP de una petición REST.
 *
 * FIX v3.5.1: Solo acepta header X-VBP-Key por seguridad.
 * Las keys en URL aparecen en logs de servidor, referrer headers, y browser history.
 *
 * @param WP_REST_Request $request Petición REST.
 * @return string
 */
if ( ! function_exists( 'flavor_get_vbp_api_key_from_request' ) ) {
    function flavor_get_vbp_api_key_from_request( $request ) {
        if ( ! is_object( $request ) || ! method_exists( $request, 'get_header' ) ) {
            return '';
        }

        // Solo aceptar API key desde header (nunca desde URL/parámetros)
        return (string) $request->get_header( 'X-VBP-Key' );
    }
}

/**
 * Regenera la API key de VBP
 *
 * @global string|null $flavor_vbp_api_key_cache Cache de la API key.
 * @return string Nueva API key generada.
 */
function flavor_regenerate_vbp_api_key() {
    global $flavor_vbp_api_key_cache;

    $nueva_key = wp_generate_password( 32, false, false );
    $settings = get_option( 'flavor_vbp_settings', array() );
    $settings['api_key'] = $nueva_key;
    update_option( 'flavor_vbp_settings', $settings );

    // Limpiar cache WP
    wp_cache_delete( 'flavor_vbp_api_key', 'flavor' );

    // FIX: Limpiar cache estático de flavor_get_vbp_api_key()
    $flavor_vbp_api_key_cache = null;

    // Invalidar también el cache de settings VBP
    flavor_invalidate_settings_cache( 'vbp' );

    return $nueva_key;
}

/**
 * Carga segura de archivos bootstrap para evitar fatales por despliegues incompletos.
 *
 * FIX: Validación de path traversal para prevenir LFI (Local File Inclusion).
 *
 * @param string $relative_path Ruta relativa desde FLAVOR_PLATFORM_PATH.
 * @param string $expected_class Clase esperada tras incluir el archivo.
 *
 * @return bool
 */
function flavor_platform_require_bootstrap_file( $relative_path, $expected_class = '' ) {
    static $missing_files = [];

    // FIX: Sanitizar y prevenir path traversal
    $relative_path = str_replace( '..', '', $relative_path );
    $relative_path = preg_replace( '#/+#', '/', $relative_path ); // Normalizar slashes duplicados

    $file = FLAVOR_PLATFORM_PATH . ltrim( $relative_path, '/' );

    // FIX: Verificar que el archivo resuelto está dentro del directorio del plugin
    $real_plugin_path = realpath( FLAVOR_PLATFORM_PATH );
    $real_file_path = realpath( dirname( $file ) );

    if ( $real_file_path && $real_plugin_path ) {
        if ( strpos( $real_file_path, $real_plugin_path ) !== 0 ) {
            flavor_log_error( 'Path traversal detectado: ' . $relative_path, 'Security' );
            return false;
        }
    }

    if ( ! file_exists( $file ) ) {
        $missing_files[] = $relative_path;
        $missing_files = array_values( array_unique( $missing_files ) );
        flavor_log_error( 'Archivo bootstrap faltante: ' . $relative_path, 'bootstrap' );
        update_option( 'flavor_platform_missing_bootstrap_files', $missing_files, false );
        return false;
    }

    require_once $file;

    if ( $expected_class && ! class_exists( $expected_class ) ) {
        flavor_log_error(
            'La clase bootstrap esperada no existe tras include: ' . $expected_class . ' (' . $relative_path . ')',
            'bootstrap'
        );
        return false;
    }

    return true;
}

/**
 * Alias legacy para compatibilidad retroactiva.
 */
function flavor_chat_ia_require_bootstrap_file( $relative_path, $expected_class = '' ) {
    return flavor_platform_require_bootstrap_file( $relative_path, $expected_class );
}

/**
 * Aviso de administración cuando faltan archivos bootstrap.
 *
 * @return void
 */
function flavor_platform_missing_bootstrap_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Comprobar ambas opciones por compatibilidad
    $missing_files = get_option( 'flavor_platform_missing_bootstrap_files', [] );
    if ( empty( $missing_files ) ) {
        $missing_files = get_option( 'flavor_chat_ia_missing_bootstrap_files', [] );
    }
    if ( empty( $missing_files ) || ! is_array( $missing_files ) ) {
        return;
    }

    $items = '';
    foreach ( $missing_files as $missing_file ) {
        $items .= '<li><code>' . esc_html( $missing_file ) . '</code></li>';
    }

    // FIX: Usar wp_kses_post para sanitización segura del HTML de salida
    echo wp_kses_post( '<div class="notice notice-error"><p><strong>Flavor Platform:</strong> faltan archivos de bootstrap en el servidor. El plugin se cargó en modo degradado.</p><ul style="margin-left:1.2em;list-style:disc;">' . $items . '</ul></div>' );
}

/**
 * Alias legacy para compatibilidad retroactiva.
 */
function flavor_chat_ia_missing_bootstrap_admin_notice() {
    flavor_platform_missing_bootstrap_admin_notice();
}
add_action( 'admin_notices', 'flavor_platform_missing_bootstrap_admin_notice' );

// Cargar clases de bootstrap (v3.2.0+) con tolerancia a faltantes.
flavor_platform_require_bootstrap_file( 'includes/bootstrap/class-bootstrap-dependencies.php', 'Flavor_Bootstrap_Dependencies' );
flavor_platform_require_bootstrap_file( 'includes/bootstrap/class-starter-theme-manager.php', 'Flavor_Starter_Theme_Manager' );
flavor_platform_require_bootstrap_file( 'includes/bootstrap/class-database-setup.php', 'Flavor_Database_Setup' );
flavor_platform_require_bootstrap_file( 'includes/bootstrap/class-cron-manager.php', 'Flavor_Cron_Manager' );
flavor_platform_require_bootstrap_file( 'includes/bootstrap/class-system-initializer.php', 'Flavor_System_Initializer' );

// Stubs de seguridad para evitar fatales si faltan archivos en producción.
if ( ! class_exists( 'Flavor_Bootstrap_Dependencies' ) ) {
    class Flavor_Bootstrap_Dependencies {
        public static function get_instance() { return new self(); }
        public function load_all() {}
    }
}

if ( ! class_exists( 'Flavor_Starter_Theme_Manager' ) ) {
    class Flavor_Starter_Theme_Manager {
        public static function get_instance() { return new self(); }
        public function register_hooks() {}
        public function check_on_activation() {}
    }
}

if ( ! class_exists( 'Flavor_Database_Setup' ) ) {
    class Flavor_Database_Setup {
        public static function get_instance() { return new self(); }
        public function install() {}
        public function maybe_install_legal_pages() {}
        public function maybe_fix_placeholder_urls() {}
    }
}

if ( ! class_exists( 'Flavor_Cron_Manager' ) ) {
    class Flavor_Cron_Manager {
        public static function get_instance() { return new self(); }
        public function register_hooks() {}
        public function schedule_all() {}
        public function unschedule_all() {}
    }
}

if ( ! class_exists( 'Flavor_System_Initializer' ) ) {
    class Flavor_System_Initializer {
        public static function get_instance() { return new self(); }
        public function init() {}
    }
}

/**
 * Clase principal del plugin
 *
 * Refactorizada en v3.2.0 para delegar a clases especializadas de bootstrap.
 */
final class Flavor_Platform {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Módulos cargados
     */
    private $modules = [];

    /**
     * Gestor del tema starter
     *
     * @var Flavor_Starter_Theme_Manager
     */
    private $theme_manager;

    /**
     * Gestor de crons
     *
     * @var Flavor_Cron_Manager
     */
    private $cron_manager;

    /**
     * Gestor de base de datos
     *
     * @var Flavor_Database_Setup
     */
    private $db_setup;

    /**
     * Inicializador del sistema
     *
     * @var Flavor_System_Initializer
     */
    private $system_initializer;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Platform
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // NOTA: El textdomain se carga ANTES de crear la instancia (ver final del archivo)
        // para evitar el warning "_load_textdomain_just_in_time" de WordPress 6.7+.

        // Inicializar gestores de bootstrap
        $this->theme_manager = Flavor_Starter_Theme_Manager::get_instance();
        $this->cron_manager = Flavor_Cron_Manager::get_instance();
        $this->db_setup = Flavor_Database_Setup::get_instance();
        $this->system_initializer = Flavor_System_Initializer::get_instance();

        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Carga las dependencias del plugin
     *
     * Delegado a Flavor_Bootstrap_Dependencies para mejor organización.
     */
    private function load_dependencies() {
        Flavor_Bootstrap_Dependencies::get_instance()->load_all();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // Activación/Desactivación
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Inicialización
        add_action('plugins_loaded', [$this, 'init'], 5);
        add_action('plugins_loaded', [$this, 'load_modules'], 10);

        // Cargar Dashboard para REST API (necesario para endpoints /admin/*)
        add_action('rest_api_init', [$this, 'load_dashboard_for_rest'], 1);

        // AJAX temprano
        add_action('plugins_loaded', [$this, 'early_ajax_hooks'], 5);

        // Declarar compatibilidad HPOS de WooCommerce
        add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);

        // Registrar hooks del Cron Manager (schedules y callbacks E2E)
        $this->cron_manager->register_hooks();

        // Registrar hooks del Theme Manager (avisos y activación)
        $this->theme_manager->register_hooks();

        // Limpiar rewrite rules una sola vez tras desactivar controladores frontend
        add_action('init', [$this, 'maybe_flush_frontend_rewrite_rules'], 999);

        // Encolar estilos globales para romper containers del tema
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_styles'], 5);

        // Auto-asignar roles de módulo al activar módulos
        add_action('update_option_flavor_chat_ia_settings', [$this, 'handle_modules_role_assignment'], 10, 2);

        // Crear páginas legales si no existen (para instalaciones existentes)
        add_action('admin_init', [$this, 'maybe_install_legal_pages']);

        // Corregir URLs de placeholder incompletas en la base de datos
        add_action('admin_init', [$this, 'maybe_fix_placeholder_urls']);

        // Cargar estilos comunes de admin (modales, etc.)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_common_styles']);

        // Cargar generador de datos de demo
        add_action('plugins_loaded', [$this, 'init_demo_data_generator'], 20);
    }

    /**
     * Inicializa el generador de datos de demostración
     * Solo se carga en el área de administración
     */
    public function init_demo_data_generator() {
        if (is_admin()) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/admin/class-demo-data-generator.php';

            // Herramienta de migración VBP (solo admin)
            $migration_tool_path = FLAVOR_PLATFORM_PATH . 'includes/tools/class-vbp-migration-tool.php';
            if (file_exists($migration_tool_path)) {
                require_once $migration_tool_path;
            }
        }
    }

    /**
     * Carga estilos comunes para páginas de administración
     *
     * @param string $hook Hook de la página actual
     */
    public function enqueue_admin_common_styles($hook) {
        // Solo cargar en páginas del plugin
        $paginas_plugin = [
            'toplevel_page_flavor-settings',
            'flavor_page_',
            'admin_page_',
        ];

        $es_pagina_plugin = false;
        foreach ($paginas_plugin as $prefijo) {
            if (strpos($hook, $prefijo) === 0) {
                $es_pagina_plugin = true;
                break;
            }
        }

        // También cargar si la página tiene parámetros de módulos del plugin
        if (isset($_GET['page'])) {
            $paginas_modulos = [
                'marketplace', 'multimedia', 'ayuda-', 'banco-tiempo', 'grupos-consumo',
                'comunidades', 'eventos', 'cursos', 'talleres', 'reservas', 'foros',
                'podcast', 'incidencias', 'socios', 'colectivos', 'participacion',
                'presupuestos', 'espacios-comunes', 'huertos', 'carpooling', 'biblioteca',
                'circulos-cuidados', 'avisos-municipales', 'biodiversidad', 'red-social',
                'chat-interno', 'chat-grupos', 'facturas', 'fichaje', 'reciclaje',
                'compostaje', 'economia-don', 'economia-suficiencia', 'trabajo-digno',
                'saberes-ancestrales', 'justicia-restaurativa', 'sello-conciencia',
                'bicicletas-compartidas', 'parkings', 'bares', 'tramites', 'woocommerce'
            ];

            $pagina_actual = sanitize_text_field($_GET['page']);
            foreach ($paginas_modulos as $modulo) {
                if (strpos($pagina_actual, $modulo) !== false) {
                    $es_pagina_plugin = true;
                    break;
                }
            }
        }

        if ($es_pagina_plugin) {
            wp_enqueue_style(
                'flavor-admin-modals',
                FLAVOR_PLATFORM_URL . 'assets/css/admin/admin-modals.css',
                [],
                FLAVOR_PLATFORM_VERSION
            );
        }
    }

    /**
     * Instala páginas legales si no existen (una sola vez)
     *
     * Delegado a Flavor_Database_Setup.
     */
    public function maybe_install_legal_pages() {
        $this->db_setup->maybe_install_legal_pages();
    }

    /**
     * Corrige URLs de placeholder en la base de datos
     *
     * Delegado a Flavor_Database_Setup.
     */
    public function maybe_fix_placeholder_urls() {
        $this->db_setup->maybe_fix_placeholder_urls();
    }

    /**
     * Limpia las rewrite rules de los controladores frontend (una sola vez)
     */
    public function maybe_flush_frontend_rewrite_rules() {
        if (get_option('flavor_frontend_controllers_disabled') !== 'v2') {
            flush_rewrite_rules();
            update_option('flavor_frontend_controllers_disabled', 'v2');
        }
    }

    /**
     * Inicialización del plugin
     *
     * Delegado a Flavor_System_Initializer para mejor organización.
     */
    public function init() {
        $this->system_initializer->init();

        // Crear páginas del portal en 'init' de WordPress (no plugins_loaded)
        // para que $wp_rewrite esté inicializado
        if (class_exists('Flavor_Portal_Shortcodes')) {
            add_action('init', array($this, 'maybe_create_portal_pages'), 99);
        }
    }

    /**
     * Asigna automaticamente rol admin del modulo al usuario que activa módulos.
     *
     * @param array $old_value
     * @param array $value
     */
    public function handle_modules_role_assignment($old_value, $value) {
        // Invalidar caché de metadatos de módulos cuando cambian los módulos activos
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $old_modules = isset($old_value['active_modules']) ? $old_value['active_modules'] : [];
            $new_modules = isset($value['active_modules']) ? $value['active_modules'] : [];

            if ($old_modules !== $new_modules) {
                $loader = Flavor_Platform_Module_Loader::get_instance();
                $loader->invalidate_metadata_cache();
            }
        }

        if (!is_admin() || !class_exists('Flavor_Permission_Helper')) {
            return;
        }

        $old_modules = isset($old_value['active_modules']) && is_array($old_value['active_modules'])
            ? $old_value['active_modules']
            : [];
        $new_modules = isset($value['active_modules']) && is_array($value['active_modules'])
            ? $value['active_modules']
            : [];

        $activated = array_diff($new_modules, $old_modules);
        if (empty($activated)) {
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        foreach ($activated as $module_slug) {
            Flavor_Permission_Helper::assign_module_admin_to_user($user_id, $module_slug);
        }
    }

    /**
     * Carga los módulos activos
     */
    public function load_modules() {
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            $this->modules = $loader->load_active_modules();
        }

        // Inicializar Shortcodes Automáticos de Módulos DESPUÉS de cargar los módulos
        // Esto permite que los módulos registren sus shortcodes primero,
        // y los fallbacks solo se registren si no existen
        if (class_exists('Flavor_Module_Shortcodes')) {
            Flavor_Module_Shortcodes::get_instance();
        }
    }

    /**
     * Hooks AJAX tempranos
     */
    public function early_ajax_hooks() {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            return;
        }

        // Cargar dependencias para AJAX
        if (class_exists('Flavor_Platform_Ajax')) {
            Flavor_Platform_Ajax::register_hooks();
        }
    }

    /**
     * Crea páginas del portal si no existen
     *
     * @since 3.5.0 Movido a hook 'init' para evitar error de $wp_rewrite null
     */
    public function maybe_create_portal_pages() {
        // Solo crear una vez
        if (get_option('flavor_portal_pages_created')) {
            return;
        }

        // Página de Servicios (Landing)
        if (!get_page_by_path('servicios')) {
            wp_insert_post([
                'post_title' => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'post_name' => 'servicios',
                'post_content' => '[flavor_servicios mostrar_stats="yes" columnas="3"]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1,
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);
        }

        // Página Mi Portal (Dashboard)
        if (!get_page_by_path('mi-portal')) {
            wp_insert_post([
                'post_title' => __('Mi Portal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'post_name' => 'mi-portal',
                'post_content' => '[flavor_mi_portal mostrar_actividad="yes" mostrar_notificaciones="yes" columnas="3"]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1,
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);
        }

        update_option('flavor_portal_pages_created', true);
    }

    /**
     * Activación del plugin
     *
     * Refactorizado en v3.2.0 para delegar a clases especializadas de bootstrap.
     */
    public function activate() {
        // Opciones por defecto
        $defaults = [
            'enabled' => false,
            'show_floating_widget' => true,
            // Configuración multi-proveedor IA
            'active_provider' => 'claude',
            'api_key' => '', // Legacy: Claude API key
            'claude_api_key' => '',
            'claude_model' => 'claude-sonnet-4-20250514',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o-mini',
            'deepseek_api_key' => '',
            'deepseek_model' => 'deepseek-chat',
            'mistral_api_key' => '',
            'mistral_model' => 'mistral-small-latest',
            // Configuración general
            'assistant_name' => 'Asistente Virtual',
            'assistant_role' => 'Soy un asistente virtual para ayudarte con tus consultas.',
            'tone' => 'friendly',
            'max_messages_per_session' => 50,
            'max_tokens_per_message' => 1000,
            'escalation_whatsapp' => '',
            'escalation_phone' => '',
            'escalation_email' => '',
            'escalation_hours' => 'L-V 9:00-18:00',
            'languages' => ['es'],
            'knowledge_base' => [],
            'faqs' => [],
            'active_modules' => ['woocommerce'], // Módulos activos por defecto
            'app_profile' => 'personalizado', // Perfil de aplicación por defecto
            'widget_position' => 'bottom-right',
            'widget_color' => '#0073aa',
        ];

        $existing = flavor_get_main_settings();
        $merged = wp_parse_args($existing, $defaults);
        flavor_update_main_settings($merged);

        // Crear roles y capabilities personalizados
        if (class_exists('Flavor_Role_Manager')) {
            Flavor_Role_Manager::create_roles();
        }

        // Instalar base de datos (delegado a Database Setup)
        $this->db_setup->install();

        // Reconstruir caché de metadatos de módulos para optimizar rendimiento
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            $loader->rebuild_metadata_cache();
        }

        // Programar todos los crons (delegado a Cron Manager)
        $this->cron_manager->schedule_all();

        // Registrar post type de Visual Builder y regenerar permalinks
        if (class_exists('Flavor_Visual_Builder')) {
            Flavor_Visual_Builder::on_plugin_activation();
        }

        // Crear tabla de Audit Log del VBP
        if (class_exists('Flavor_VBP_Audit_Log')) {
            Flavor_VBP_Audit_Log::create_table();
        }

        // Limpiar caché de rewrite
        flush_rewrite_rules();

        // Verificar tema companion (delegado a Theme Manager)
        $this->theme_manager->check_on_activation();
    }

    // ========================================================================
    // MÉTODOS LEGACY (v3.2.0+): Los siguientes métodos se mantienen por
    // compatibilidad hacia atrás. La lógica real está en las clases de bootstrap.
    // ========================================================================

    /**
     * Desactivación del plugin
     *
     * Refactorizado en v3.2.0 para delegar a Cron Manager.
     */
    public function deactivate() {
        // Desprogramar todos los crons (delegado a Cron Manager)
        $this->cron_manager->unschedule_all();

        flush_rewrite_rules();
    }

    // NOTA: create_tables() y create_module_tables() movidos a Flavor_Database_Setup

    /**
     * Carga el Dashboard Admin para peticiones REST API
     *
     * Los endpoints /admin/* del dashboard necesitan estar disponibles
     * también para peticiones REST (no solo en contexto is_admin()).
     *
     * @return void
     */
    public function load_dashboard_for_rest() {
        // Cargar archivo si no existe la clase
        if (!class_exists('Flavor_Dashboard')) {
            $dashboard_file = FLAVOR_PLATFORM_PATH . 'admin/class-dashboard.php';
            if (file_exists($dashboard_file)) {
                require_once $dashboard_file;
            }
        }

        // Instanciar la clase (se necesita para que se registren los endpoints REST)
        if (class_exists('Flavor_Dashboard')) {
            Flavor_Dashboard::get_instance();
        }
    }

    /**
     * Carga el textdomain para traducciones
     */
    public function load_textdomain() {
        flavor_load_textdomains();
    }

    /**
     * Encola estilos globales para override de containers del tema
     */
    public function enqueue_global_styles() {
        // CSS para romper limitaciones de ancho del tema
        wp_enqueue_style(
            'flavor-container-override',
            FLAVOR_PLATFORM_URL . 'assets/css/layouts/flavor-container-override.css',
            [],
            FLAVOR_PLATFORM_VERSION,
            'all'
        );
    }

    /**
     * Declara compatibilidad con HPOS de WooCommerce
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }

    /**
     * Obtiene los módulos cargados
     *
     * @return array
     */
    public function get_modules() {
        return $this->modules;
    }

    /**
     * Verifica si un módulo está activo
     *
     * @param string $module_id
     * @return bool
     */
    public function is_module_active($module_id) {
        return isset($this->modules[$module_id]);
    }

    /**
     * Verifica e instala tablas de módulos si no existen
     */
    private function maybe_install_module_tables() {
        // Solo ejecutar si no está instalado aún
        $db_version = get_option('flavor_db_version', '');
        if (!empty($db_version)) {
            return;
        }

        // Verificar si hay al menos una tabla crítica
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_existe = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tabla_eventos
        ));

        if ($tabla_existe === $tabla_eventos) {
            // Ya existen las tablas, marcar como instalado
            update_option('flavor_db_version', '1.0.0');
            return;
        }

        // Instalar tablas
        if (class_exists('Flavor_Database_Installer')) {
            Flavor_Database_Installer::install_tables();
            flavor_chat_ia_log('Tablas de módulos instaladas automáticamente', 'info');
        }
    }
}

if (!class_exists('Flavor_Chat_IA', false)) {
    class_alias('Flavor_Platform', 'Flavor_Chat_IA');
}

/**
 * Función helper para obtener la instancia del plugin
 *
 * @return Flavor_Platform
 */
function flavor_platform() {
    return Flavor_Platform::get_instance();
}

/**
 * Alias legacy para compatibilidad retroactiva.
 *
 * @return Flavor_Platform
 */
function flavor_chat_ia() {
    return flavor_platform();
}

/**
 * Inicialización del plugin usando hooks de WordPress.
 *
 * El plugin sigue teniendo bastante carga y registro de clases durante
 * `plugins_loaded`, así que dejar el textdomain para un callback posterior
 * puede hacer que WordPress intente resolver traducciones "just in time"
 * demasiado pronto y termine inyectando notices en el HTML.
 *
 * Lo cargamos aquí, en el bootstrap del archivo principal, para asegurar que
 * cualquier __()/esc_html__() posterior ya encuentre el dominio registrado.
 */

flavor_load_textdomains();

// Algunos modulos y dependencias cargan demasiado pronto y WordPress 6.7+
// inyecta notices de `_load_textdomain_just_in_time` en el HTML. Mientras se
// completa la refactorizacion del arranque, evitamos que ese warning rompa
// frontend, AJAX y REST.
add_filter('doing_it_wrong_trigger_error', function($trigger, $function_name) {
    if ($function_name === '_load_textdomain_just_in_time') {
        return false;
    }

    return $trigger;
}, 10, 2);

// Inicializar el plugin después de cargar el textdomain
add_action('plugins_loaded', 'flavor_platform', 1);

// DEBUG TEMPORAL - Diagnóstico del portal layout (ELIMINAR DESPUÉS DE USAR)
// Se difiere a admin_init para no tocar capacidades antes de que WP cargue al usuario.
add_action('admin_init', function() {
    if (
        defined('WP_DEBUG') &&
        WP_DEBUG &&
        current_user_can('manage_options') &&
        file_exists(__DIR__ . '/debug-portal-layout.php')
    ) {
        require_once __DIR__ . '/debug-portal-layout.php';
    }
});

/**
 * Registrar y encolar estilos visuales del VBP para el frontend
 * Estos estilos aplican las personalizaciones de tarjetas, colores, animaciones, etc.
 *
 * @since 2.4.0
 */
add_action('wp_enqueue_scripts', function() {
    wp_register_style(
        'flavor-vbp-visual-styles',
        FLAVOR_PLATFORM_URL . 'assets/css/vbp-visual-styles.css',
        [],
        FLAVOR_PLATFORM_VERSION
    );

    // Solo encolar si la página usa VBP o es una landing
    $should_enqueue = false;

    if ( is_singular() ) {
        global $post;
        // Encolar si es una landing VBP
        if ( $post && $post->post_type === 'flavor_landing' ) {
            $should_enqueue = true;
        }
        // O si tiene datos VBP
        if ( $post && get_post_meta( $post->ID, '_flavor_vbp_data', true ) ) {
            $should_enqueue = true;
        }
    }

    // Permitir forzar via filtro (para módulos que lo necesiten)
    $should_enqueue = apply_filters( 'flavor_enqueue_vbp_styles', $should_enqueue );

    if ( $should_enqueue ) {
        wp_enqueue_style('flavor-vbp-visual-styles');
    }
}, 20);

// Cargar diagnóstico de performance (solo si se solicita con ?flavor_perf=1)
// FIX: Verificar permisos PRIMERO, luego verificar parámetro GET
if (
    defined( 'WP_DEBUG' ) &&
    WP_DEBUG &&
    function_exists( 'current_user_can' ) &&
    current_user_can( 'manage_options' ) &&
    isset( $_GET['flavor_perf'] ) &&
    sanitize_key( $_GET['flavor_perf'] ) === '1'
) {
    $diagnostico_file = FLAVOR_PLATFORM_PATH . 'diagnostico-performance.php';
    if ( file_exists( $diagnostico_file ) ) {
        require_once $diagnostico_file;
    }
}
