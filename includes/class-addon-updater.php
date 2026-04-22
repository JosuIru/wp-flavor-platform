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
     * Addons registrados para actualización.
     *
     * Cada entrada incluye, como minimo: slug, file, version, github_repo.
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
     * Registra un addon para actualizaciones automaticas via GitHub Releases.
     *
     * @param string $slug Slug del addon.
     * @param string $archivo_principal Ruta al archivo principal del addon.
     * @param string $version_actual Version actual instalada.
     * @param array  $config Configuracion adicional. Clave requerida:
     *                       - github_repo (string) "owner/repo" del repositorio GitHub del addon.
     *                       Claves opcionales: name, icons, banners, tested, requires_php, beta.
     * @return void
     */
    public function register_addon($slug, $archivo_principal, $version_actual, $config = []) {
        $archivo_principal = is_string($archivo_principal) ? trim($archivo_principal) : '';

        $defaults = [
            'slug'         => $slug,
            'file'         => $archivo_principal,
            'version'      => $version_actual,
            'github_repo'  => '',
            'name'         => $slug,
            'icons'        => [],
            'banners'      => [],
            'tested'       => '',
            'requires_php' => '',
            'beta'         => false,
        ];

        $addon_config = wp_parse_args($config, $defaults);
        $addon_config['file'] = is_string($addon_config['file']) ? trim($addon_config['file']) : '';
        $addon_config['github_repo'] = is_string($addon_config['github_repo']) ? trim($addon_config['github_repo']) : '';

        if ($addon_config['github_repo'] === '' && function_exists('flavor_platform_log')) {
            flavor_platform_log(
                sprintf('Addon "%s" registrado sin github_repo: no recibira actualizaciones automaticas.', $slug),
                'warning'
            );
        }

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
     * Consulta GitHub Releases de cada addon registrado y devuelve el mapa de actualizaciones.
     *
     * Open source: GET anonimo a api.github.com, sin license_key ni telemetria.
     *
     * @return array Mapa slug => info de release (version, url, package, changelog, ...).
     */
    private function obtener_actualizaciones_remotas() {
        $cache_key = 'flavor_updates_check';
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return is_array($cached) ? $cached : [];
        }

        $this->cargar_helper_github();

        $actualizaciones = [];
        foreach ($this->addons_actualizables as $slug => $addon) {
            if (empty($addon['github_repo'])) {
                continue;
            }

            $release_info = Flavor_GitHub_Release_API::fetch_latest_release(
                $addon['github_repo'],
                !empty($addon['beta'])
            );

            if ($release_info === null || empty($release_info['version']) || empty($release_info['zip_url'])) {
                continue;
            }

            $actualizaciones[$slug] = [
                'name'         => $addon['name'] ?? $slug,
                'version'      => $release_info['version'],
                'url'          => $release_info['html_url'] ?: ('https://github.com/' . $addon['github_repo']),
                'package'      => $release_info['zip_url'],
                'changelog'    => $release_info['changelog'],
                'release_date' => $release_info['published_at'],
                'icons'        => $addon['icons'] ?? [],
                'banners'      => $addon['banners'] ?? [],
                'tested'       => $addon['tested'] ?? '',
                'requires_php' => $addon['requires_php'] ?? '',
            ];
        }

        // Cachear 12h tanto si hay updates como si no (evita golpear api.github.com).
        set_transient($cache_key, $actualizaciones, 12 * HOUR_IN_SECONDS);

        return $actualizaciones;
    }

    /**
     * Carga el helper de GitHub si aun no esta en memoria.
     *
     * @return void
     */
    private function cargar_helper_github() {
        if (class_exists('Flavor_GitHub_Release_API')) {
            return;
        }

        $ruta_helper = FLAVOR_PLATFORM_PATH . 'includes/licensing/class-github-release-api.php';
        if (is_readable($ruta_helper)) {
            require_once $ruta_helper;
        }
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
     * Obtiene informacion detallada de un addon desde GitHub Releases.
     *
     * @param string $slug Slug del addon.
     * @return array|false
     */
    private function obtener_info_remota($slug) {
        if (!isset($this->addons_actualizables[$slug])) {
            return false;
        }

        $addon = $this->addons_actualizables[$slug];
        if (empty($addon['github_repo'])) {
            return false;
        }

        $cache_key = 'flavor_addon_info_' . $slug;
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return is_array($cached) ? $cached : false;
        }

        $this->cargar_helper_github();

        $release_info = Flavor_GitHub_Release_API::fetch_latest_release(
            $addon['github_repo'],
            !empty($addon['beta'])
        );

        if ($release_info === null || empty($release_info['version'])) {
            return false;
        }

        $info = [
            'name'           => $addon['name'] ?? $slug,
            'version'        => $release_info['version'],
            'author'         => $addon['author'] ?? '',
            'author_profile' => $addon['author_profile'] ?? '',
            'requires'       => $addon['requires'] ?? '5.8',
            'tested'         => $addon['tested'] ?? '',
            'requires_php'   => $addon['requires_php'] ?? '7.4',
            'description'    => $addon['description'] ?? '',
            'changelog'      => $release_info['changelog'],
            'installation'   => $addon['installation'] ?? '',
            'faq'            => $addon['faq'] ?? '',
            'banners'        => $addon['banners'] ?? [],
            'icons'          => $addon['icons'] ?? [],
            'package'        => $release_info['zip_url'],
            'last_updated'   => $release_info['published_at'],
        ];

        set_transient($cache_key, $info, 6 * HOUR_IN_SECONDS);

        return $info;
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

}

/**
 * Helper para registrar un addon actualizable via GitHub Releases.
 *
 * @param string $slug Slug del addon.
 * @param string $archivo Archivo principal del addon.
 * @param string $version Version actual instalada.
 * @param array  $config Configuracion. Debe incluir github_repo ("owner/repo").
 * @return void
 */
function flavor_register_addon_updates($slug, $archivo, $version, $config = []) {
    $updater = Flavor_Addon_Updater::get_instance();
    $updater->register_addon($slug, $archivo, $version, $config);
}
