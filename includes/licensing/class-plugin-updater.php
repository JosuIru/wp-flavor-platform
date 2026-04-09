<?php
/**
 * Sistema de Actualización Automática de Plugin
 *
 * Conecta con el servidor de licencias para verificar y descargar actualizaciones
 *
 * @package FlavorChatIA
 * @subpackage Licensing
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar actualizaciones automáticas
 *
 * @since 3.2.0
 */
class Flavor_Plugin_Updater {

    /**
     * Instancia singleton
     *
     * @var Flavor_Plugin_Updater
     */
    private static $instance = null;

    /**
     * Slug del plugin
     *
     * @var string
     */
    private $plugin_slug = FLAVOR_PLATFORM_TEXT_DOMAIN;

    /**
     * Archivo principal del plugin
     *
     * @var string
     */
    private $plugin_file;

    /**
     * URL del servidor de licencias
     *
     * @var string
     */
    private $api_url;

    /**
     * Caché de actualización
     *
     * @var string
     */
    private const CACHE_KEY = 'flavor_plugin_update_check';

    /**
     * TTL del caché (12 horas)
     *
     * @var int
     */
    private const CACHE_TTL = 43200;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Plugin_Updater
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
        $this->plugin_file = FLAVOR_CHAT_IA_BASENAME;
        $this->api_url = $this->get_api_url();

        $this->init_hooks();
    }

    /**
     * Obtiene URL del API
     *
     * @return string
     */
    private function get_api_url() {
        if (defined('FLAVOR_LICENSE_SERVER_URL')) {
            return FLAVOR_LICENSE_SERVER_URL;
        }

        $custom_url = get_option('flavor_license_server_url');
        if (!empty($custom_url)) {
            return trailingslashit($custom_url);
        }

        return apply_filters('flavor_license_server_url', 'https://licencias.gailu.net/wp-json/fls/v1/');
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Verificar actualizaciones
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);

        // Información del plugin en el modal
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);

        // Limpiar caché después de actualizar
        add_action('upgrader_process_complete', [$this, 'clear_cache'], 10, 2);

        // Añadir enlace de verificar actualizaciones
        add_filter('plugin_action_links_' . $this->plugin_file, [$this, 'add_action_links']);

        // AJAX para verificación manual
        add_action('wp_ajax_flavor_check_updates', [$this, 'ajax_check_updates']);
    }

    /**
     * Verifica si hay actualizaciones disponibles
     *
     * @param object $transient Transient de actualizaciones
     * @return object
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Obtener versión actual
        $current_version = $transient->checked[$this->plugin_file] ?? FLAVOR_CHAT_IA_VERSION;

        // Verificar caché
        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false && isset($cached['checked_version']) && $cached['checked_version'] === $current_version) {
            if (!empty($cached['update'])) {
                $transient->response[$this->plugin_file] = $cached['update'];
            }
            return $transient;
        }

        // Consultar al servidor
        $update_info = $this->fetch_update_info($current_version);

        if ($update_info && !empty($update_info['has_update'])) {
            $plugin_data = [
                'id'            => $this->plugin_slug,
                'slug'          => $this->plugin_slug,
                'plugin'        => $this->plugin_file,
                'new_version'   => $update_info['latest_version'],
                'url'           => 'https://gailu.net/flavor-platform/',
                'package'       => $update_info['download_url'] ?? '',
                'icons'         => [
                    '1x' => FLAVOR_CHAT_IA_URL . 'assets/images/icon-128.png',
                    '2x' => FLAVOR_CHAT_IA_URL . 'assets/images/icon-256.png',
                ],
                'banners'       => [
                    'low'  => FLAVOR_CHAT_IA_URL . 'assets/images/banner-772x250.png',
                    'high' => FLAVOR_CHAT_IA_URL . 'assets/images/banner-1544x500.png',
                ],
                'tested'        => $update_info['tested_wp'] ?? '',
                'requires_php'  => $update_info['requires_php'] ?? '7.4',
                'requires'      => $update_info['requires_wp'] ?? '5.8',
            ];

            $transient->response[$this->plugin_file] = (object) $plugin_data;

            // Guardar en caché
            set_transient(self::CACHE_KEY, [
                'checked_version' => $current_version,
                'update' => (object) $plugin_data,
                'info' => $update_info,
            ], self::CACHE_TTL);
        } else {
            // Guardar que no hay actualización
            set_transient(self::CACHE_KEY, [
                'checked_version' => $current_version,
                'update' => null,
            ], self::CACHE_TTL);

            // Eliminar de response si estaba
            unset($transient->response[$this->plugin_file]);
        }

        return $transient;
    }

    /**
     * Proporciona información del plugin para el modal
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

        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        // Obtener información cacheada
        $cached = get_transient(self::CACHE_KEY);
        $update_info = $cached['info'] ?? null;

        if (!$update_info) {
            $update_info = $this->fetch_update_info(FLAVOR_CHAT_IA_VERSION);
        }

        if (!$update_info) {
            return $result;
        }

        $plugin_info = [
            'name'              => 'Flavor Platform',
            'slug'              => $this->plugin_slug,
            'version'           => $update_info['latest_version'] ?? FLAVOR_CHAT_IA_VERSION,
            'author'            => '<a href="https://gailu.net">Gailu Labs</a>',
            'author_profile'    => 'https://gailu.net',
            'homepage'          => 'https://gailu.net/flavor-platform/',
            'short_description' => __('Plataforma modular para comunidades y organizaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sections'          => [
                'description'   => $this->get_plugin_description(),
                'changelog'     => $this->format_changelog($update_info['changelog'] ?? ''),
            ],
            'download_link'     => $update_info['download_url'] ?? '',
            'requires'          => $update_info['requires_wp'] ?? '5.8',
            'tested'            => $update_info['tested_wp'] ?? '',
            'requires_php'      => $update_info['requires_php'] ?? '7.4',
            'last_updated'      => $update_info['release_date'] ?? '',
            'banners'           => [
                'low'  => FLAVOR_CHAT_IA_URL . 'assets/images/banner-772x250.png',
                'high' => FLAVOR_CHAT_IA_URL . 'assets/images/banner-1544x500.png',
            ],
        ];

        return (object) $plugin_info;
    }

    /**
     * Consulta al servidor información de actualización
     *
     * @param string $current_version Versión actual
     * @return array|null
     */
    private function fetch_update_info($current_version) {
        $license_manager = Flavor_License_Manager::get_instance();
        $license_data = $license_manager->get_license_data();
        $license_key = $license_data['key'] ?? '';

        $body = [
            'plugin_slug'     => $this->plugin_slug,
            'current_version' => $current_version,
            'license_key'     => $license_key,
            'site_url'        => $this->get_site_url(),
            'wp_version'      => get_bloginfo('version'),
            'php_version'     => PHP_VERSION,
        ];

        $response = wp_remote_post(
            trailingslashit($this->api_url) . 'updates/check',
            [
                'body'    => wp_json_encode($body),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent'   => 'FlavorPlatform/' . FLAVOR_CHAT_IA_VERSION,
                ],
                'timeout' => 15,
            ]
        );

        if (is_wp_error($response)) {
            if (function_exists('flavor_chat_ia_log')) {
                flavor_chat_ia_log('Error verificando actualizaciones: ' . $response->get_error_message(), 'warning');
            }
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success'])) {
            return null;
        }

        return $body;
    }

    /**
     * Obtiene URL del sitio normalizada
     *
     * @return string
     */
    private function get_site_url() {
        $url = get_site_url();
        $url = preg_replace('#^https?://#', '', $url);
        $url = untrailingslashit($url);
        return strtolower($url);
    }

    /**
     * Limpia caché después de actualizar
     *
     * @param WP_Upgrader $upgrader Objeto upgrader
     * @param array $options Opciones
     * @return void
     */
    public function clear_cache($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            $plugins = $options['plugins'] ?? [];
            if (in_array($this->plugin_file, $plugins)) {
                delete_transient(self::CACHE_KEY);
            }
        }
    }

    /**
     * Añade enlaces de acción al plugin
     *
     * @param array $links Enlaces existentes
     * @return array
     */
    public function add_action_links($links) {
        $check_link = '<a href="#" class="flavor-check-updates" data-nonce="' . wp_create_nonce('flavor_check_updates') . '">'
                    . __('Verificar actualizaciones', FLAVOR_PLATFORM_TEXT_DOMAIN)
                    . '</a>';

        $links['check_updates'] = $check_link;

        return $links;
    }

    /**
     * AJAX: Verifica actualizaciones manualmente
     *
     * @return void
     */
    public function ajax_check_updates() {
        check_ajax_referer('flavor_check_updates', 'nonce');

        if (!current_user_can('update_plugins')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Limpiar caché para forzar verificación
        delete_transient(self::CACHE_KEY);
        delete_site_transient('update_plugins');

        // Forzar verificación
        wp_update_plugins();

        // Verificar resultado
        $update_plugins = get_site_transient('update_plugins');
        $has_update = isset($update_plugins->response[$this->plugin_file]);

        if ($has_update) {
            $update = $update_plugins->response[$this->plugin_file];
            wp_send_json_success([
                'has_update'    => true,
                'new_version'   => $update->new_version,
                'message'       => sprintf(
                    __('Nueva versión disponible: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $update->new_version
                ),
                'update_url'    => admin_url('update-core.php'),
            ]);
        } else {
            wp_send_json_success([
                'has_update' => false,
                'message'    => __('Tu plugin está actualizado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }
    }

    /**
     * Obtiene descripción del plugin
     *
     * @return string
     */
    private function get_plugin_description() {
        return '<p>' . __('Flavor Platform es una plataforma modular para comunidades, cooperativas y organizaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>'
             . '<h4>' . __('Características principales:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h4>'
             . '<ul>'
             . '<li>' . __('Más de 60 módulos integrados', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</li>'
             . '<li>' . __('Sistema de roles y permisos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</li>'
             . '<li>' . __('Dashboard unificado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</li>'
             . '<li>' . __('Soporte multiidioma', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</li>'
             . '<li>' . __('API REST completa', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</li>'
             . '</ul>';
    }

    /**
     * Formatea changelog para mostrar
     *
     * @param string $changelog Changelog raw
     * @return string
     */
    private function format_changelog($changelog) {
        if (empty($changelog)) {
            return '<p>' . __('No hay información de cambios disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        // Si ya es HTML, devolverlo
        if (strpos($changelog, '<') !== false) {
            return wp_kses_post($changelog);
        }

        // Convertir markdown básico a HTML
        $lines = explode("\n", $changelog);
        $html = '<ul>';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Quitar prefijos de lista
            $line = preg_replace('/^[\-\*]\s*/', '', $line);

            if (!empty($line)) {
                $html .= '<li>' . esc_html($line) . '</li>';
            }
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Fuerza verificación de actualizaciones
     *
     * @return array|null
     */
    public function force_check() {
        delete_transient(self::CACHE_KEY);
        return $this->fetch_update_info(FLAVOR_CHAT_IA_VERSION);
    }
}

/**
 * Inicializa el sistema de actualizaciones
 */
add_action('plugins_loaded', function() {
    Flavor_Plugin_Updater::get_instance();
}, 15);
