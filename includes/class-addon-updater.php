<?php
/**
 * Sistema de Actualizaciones Automáticas de Addons
 *
 * Gestiona actualizaciones de addons desde servidor remoto
 * Compatible con el sistema de actualizaciones de WordPress
 *
 * @package FlavorPlatform
 * @subpackage Addons
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para actualizaciones automáticas
 *
 * @since 3.0.0
 */
class Flavor_Addon_Updater {

    /**
     * Instancia singleton
     *
     * @var Flavor_Addon_Updater
     */
    private static $instancia = null;

    /**
     * URL del servidor de actualizaciones
     *
     * @var string
     */
    private $servidor_actualizaciones = 'https://api.gailu.net/v1/updates';

    /**
     * Addons registrados para actualización
     *
     * @var array
     */
    private $addons_actualizables = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Addon_Updater
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Hooks de WordPress para actualizaciones
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_updates']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);

        // Limpiar cache de actualizaciones después de actualizar
        add_action('upgrader_process_complete', [$this, 'purge_update_cache'], 10, 2);

        // Verificar actualizaciones diariamente
        add_action('flavor_daily_update_check', [$this, 'check_for_updates']);
        if (!wp_next_scheduled('flavor_daily_update_check')) {
            wp_schedule_event(time(), 'daily', 'flavor_daily_update_check');
        }

        // AJAX para verificar actualizaciones manualmente
        add_action('wp_ajax_flavor_check_updates', [$this, 'ajax_check_updates']);
    }

    /**
     * Registra un addon para actualizaciones automáticas
     *
     * @param string $slug Slug del addon
     * @param string $archivo_principal Ruta al archivo principal del addon
     * @param string $version_actual Versión actual instalada
     * @param array $config Configuración adicional
     * @return void
     */
    public function register_addon($slug, $archivo_principal, $version_actual, $config = []) {
        $archivo_principal = is_string($archivo_principal) ? trim($archivo_principal) : '';

        $defaults = [
            'slug' => $slug,
            'file' => $archivo_principal,
            'version' => $version_actual,
            'update_url' => $this->servidor_actualizaciones,
            'license_key' => '', // Para addons premium
            'beta' => false, // Si acepta versiones beta
        ];

        $addon_config = wp_parse_args($config, $defaults);
        $addon_config['file'] = is_string($addon_config['file']) ? trim($addon_config['file']) : '';

        $this->addons_actualizables[$slug] = $addon_config;
    }

    /**
     * Verifica actualizaciones disponibles
     *
     * @param object $transient Transient de actualizaciones de plugins
     * @return object
     */
    public function check_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Obtener actualizaciones del servidor
        $actualizaciones = $this->obtener_actualizaciones_remotas();

        if (empty($actualizaciones)) {
            return $transient;
        }

        foreach ($this->addons_actualizables as $slug => $addon) {
            if (isset($actualizaciones[$slug])) {
                $update = $actualizaciones[$slug];

                // Verificar si hay nueva versión
                if (version_compare($addon['version'], $update['version'], '<')) {
                    $addon_file = isset($addon['file']) && is_string($addon['file']) ? trim($addon['file']) : '';
                    if ($addon_file === '') {
                        flavor_platform_log(
                            sprintf('Addon updater omitido para "%s": file inválido o vacío', $slug),
                            'warning'
                        );
                        continue;
                    }

                    $plugin_file = plugin_basename($addon_file);

                    $transient->response[$plugin_file] = (object) [
                        'slug' => $slug,
                        'plugin' => $plugin_file,
                        'new_version' => $update['version'],
                        'url' => $update['url'],
                        'package' => $update['package'],
                        'icons' => $update['icons'] ?? [],
                        'banners' => $update['banners'] ?? [],
                        'tested' => $update['tested'] ?? get_bloginfo('version'),
                        'requires_php' => $update['requires_php'] ?? '7.4',
                        'compatibility' => new stdClass(),
                    ];
                }
            }
        }

        return $transient;
    }

    /**
     * Obtiene actualizaciones desde el servidor remoto
     *
     * @return array
     */
    private function obtener_actualizaciones_remotas() {
        // Verificar cache primero
        $cache_key = 'flavor_updates_check';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Preparar datos para enviar al servidor
        $addons = [];
        foreach ($this->addons_actualizables as $slug => $addon) {
            $addons[$slug] = [
                'version' => $addon['version'],
                'license' => $addon['license_key'],
                'beta' => $addon['beta'],
            ];
        }

        $request_data = [
            'addons' => $addons,
            'site_url' => get_site_url(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'core_version' => FLAVOR_PLATFORM_VERSION,
        ];

        // Hacer request al servidor
        $response = wp_remote_post($this->servidor_actualizaciones, [
            'body' => json_encode($request_data),
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'FlavorPlatform/' . FLAVOR_PLATFORM_VERSION . '; ' . get_bloginfo('url'),
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            flavor_platform_log('Error verificando actualizaciones: ' . $response->get_error_message(), 'error');
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return [];
        }

        // Cachear por 12 horas
        set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);

        return $data;
    }

    /**
     * Proporciona información del plugin para la pantalla de detalles
     *
     * @param false|object|array $result Resultado
     * @param string $action Acción
     * @param object $args Argumentos
     * @return false|object
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        // Buscar si es uno de nuestros addons
        $addon_encontrado = null;
        foreach ($this->addons_actualizables as $slug => $addon) {
            if ($args->slug === $slug) {
                $addon_encontrado = $addon;
                break;
            }
        }

        if (!$addon_encontrado) {
            return $result;
        }

        // Obtener información del servidor
        $info = $this->obtener_info_remota($args->slug);

        if (!$info) {
            return $result;
        }

        return (object) [
            'name' => $info['name'],
            'slug' => $args->slug,
            'version' => $info['version'],
            'author' => $info['author'],
            'author_profile' => $info['author_profile'] ?? '',
            'requires' => $info['requires'] ?? '5.8',
            'tested' => $info['tested'] ?? get_bloginfo('version'),
            'requires_php' => $info['requires_php'] ?? '7.4',
            'sections' => [
                'description' => $info['description'] ?? '',
                'changelog' => $info['changelog'] ?? '',
                'installation' => $info['installation'] ?? '',
                'faq' => $info['faq'] ?? '',
            ],
            'banners' => $info['banners'] ?? [],
            'icons' => $info['icons'] ?? [],
            'download_link' => $info['package'] ?? '',
            'last_updated' => $info['last_updated'] ?? date('Y-m-d'),
        ];
    }

    /**
     * Obtiene información detallada de un addon desde el servidor
     *
     * @param string $slug Slug del addon
     * @return array|false
     */
    private function obtener_info_remota($slug) {
        $cache_key = 'flavor_addon_info_' . $slug;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $url = add_query_arg([
            'action' => 'addon_info',
            'slug' => $slug,
        ], $this->servidor_actualizaciones);

        $response = wp_remote_get($url, [
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return false;
        }

        // Cachear por 6 horas
        set_transient($cache_key, $data, 6 * HOUR_IN_SECONDS);

        return $data;
    }

    /**
     * Limpia cache después de actualizar
     *
     * @param WP_Upgrader $upgrader Instancia del upgrader
     * @param array $options Opciones de actualización
     * @return void
     */
    public function purge_update_cache($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            delete_transient('flavor_updates_check');

            // Limpiar info de cada addon
            foreach ($this->addons_actualizables as $slug => $addon) {
                delete_transient('flavor_addon_info_' . $slug);
            }
        }
    }

    /**
     * Verifica actualizaciones manualmente
     *
     * @return void
     */
    public function check_for_updates() {
        // Forzar limpieza de cache
        delete_transient('flavor_updates_check');

        // Obtener nuevas actualizaciones
        $this->obtener_actualizaciones_remotas();

        flavor_platform_log('Verificación de actualizaciones completada');
    }

    /**
     * AJAX: Verificar actualizaciones
     *
     * @return void
     */
    public function ajax_check_updates() {
        check_ajax_referer('flavor_updates_nonce', 'nonce');

        if (!current_user_can('update_plugins')) {
            wp_send_json_error(__('No tienes permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $this->check_for_updates();

        $actualizaciones = $this->obtener_actualizaciones_remotas();

        $disponibles = [];
        foreach ($this->addons_actualizables as $slug => $addon) {
            if (isset($actualizaciones[$slug])) {
                $update = $actualizaciones[$slug];
                if (version_compare($addon['version'], $update['version'], '<')) {
                    $disponibles[$slug] = [
                        'name' => $update['name'],
                        'current_version' => $addon['version'],
                        'new_version' => $update['version'],
                        'changelog' => $update['changelog'] ?? '',
                    ];
                }
            }
        }

        wp_send_json_success([
            'count' => count($disponibles),
            'updates' => $disponibles,
        ]);
    }

    /**
     * Obtiene lista de addons con actualizaciones disponibles
     *
     * @return array
     */
    public function get_available_updates() {
        $actualizaciones = $this->obtener_actualizaciones_remotas();
        $disponibles = [];

        foreach ($this->addons_actualizables as $slug => $addon) {
            if (isset($actualizaciones[$slug])) {
                $update = $actualizaciones[$slug];
                if (version_compare($addon['version'], $update['version'], '<')) {
                    $disponibles[$slug] = $update;
                }
            }
        }

        return $disponibles;
    }

    /**
     * Verifica si hay actualizaciones pendientes
     *
     * @return bool
     */
    public function has_updates() {
        return !empty($this->get_available_updates());
    }

    /**
     * Obtiene cantidad de actualizaciones disponibles
     *
     * @return int
     */
    public function get_update_count() {
        return count($this->get_available_updates());
    }

    /**
     * Configurar servidor de actualizaciones personalizado
     *
     * @param string $url URL del servidor
     * @return void
     */
    public function set_update_server($url) {
        $this->servidor_actualizaciones = trailingslashit($url);
    }
}

/**
 * Función helper para registrar addon actualizable
 *
 * @param string $slug Slug del addon
 * @param string $archivo Archivo principal
 * @param string $version Versión actual
 * @param array $config Configuración
 * @return void
 */
function flavor_register_addon_updates($slug, $archivo, $version, $config = []) {
    $updater = Flavor_Addon_Updater::get_instance();
    $updater->register_addon($slug, $archivo, $version, $config);
}
