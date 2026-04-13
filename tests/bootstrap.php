<?php
/**
 * PHPUnit bootstrap file for Flavor Chat IA tests
 *
 * @package FlavorPlatform
 */

// Define test constants
if ( ! defined( 'FLAVOR_TESTING' ) ) {
    define( 'FLAVOR_TESTING', true );
}

if ( ! defined( 'FLAVOR_TEST_DIR' ) ) {
    define( 'FLAVOR_TEST_DIR', __DIR__ );
}

if ( ! defined( 'FLAVOR_PLUGIN_DIR' ) ) {
    define( 'FLAVOR_PLUGIN_DIR', dirname( __DIR__ ) );
}

if ( ! defined( 'FLAVOR_PLATFORM_PATH' ) ) {
    define( 'FLAVOR_PLATFORM_PATH', FLAVOR_PLUGIN_DIR . '/' );
}

// Definir ABSPATH antes de cargar archivos del plugin (requerido por WordPress security checks)
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

// IMPORTANTE: Definir WP_Error ANTES de que el autoloader de Composer lo cargue
// El stub de wordpress-stubs.php tiene una versión incompleta
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = array();
        public $error_data = array();
        protected $additional_data = array();

        public function __construct($code = '', $message = '', $data = '') {
            if (empty($code)) {
                return;
            }
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        public function get_error_codes() {
            if (empty($this->errors)) {
                return array();
            }
            return array_keys($this->errors);
        }

        public function get_error_code() {
            $codes = $this->get_error_codes();
            if (empty($codes)) {
                return '';
            }
            return $codes[0];
        }

        public function get_error_messages($code = '') {
            if (empty($code)) {
                $all_messages = array();
                foreach ($this->errors as $code_messages) {
                    $all_messages = array_merge($all_messages, $code_messages);
                }
                return $all_messages;
            }
            if (isset($this->errors[$code])) {
                return $this->errors[$code];
            }
            return array();
        }

        public function get_error_message($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            $messages = $this->get_error_messages($code);
            if (empty($messages)) {
                return '';
            }
            return $messages[0];
        }

        public function get_error_data($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            if (isset($this->error_data[$code])) {
                return $this->error_data[$code];
            }
            return null;
        }

        public function has_errors() {
            if (!empty($this->errors)) {
                return true;
            }
            return false;
        }

        public function add($code, $message, $data = '') {
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        public function add_data($data, $code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            $this->error_data[$code] = $data;
        }

        public function remove($code) {
            unset($this->errors[$code]);
            unset($this->error_data[$code]);
        }
    }
}

// Mock de is_wp_error - también al inicio
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return ($thing instanceof WP_Error);
    }
}

// Constantes de WordPress para queries
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}
if (!defined('OBJECT_K')) {
    define('OBJECT_K', 'OBJECT_K');
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}

// Constantes de tiempo de WordPress
if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 604800);
}
if (!defined('MONTH_IN_SECONDS')) {
    define('MONTH_IN_SECONDS', 2592000);
}
if (!defined('YEAR_IN_SECONDS')) {
    define('YEAR_IN_SECONDS', 31536000);
}

// Mock de dbDelta para evitar cargar upgrade.php
if (!function_exists('dbDelta')) {
    function dbDelta($queries = '', $execute = true) {
        return array();
    }
}

// Mock de maybe_create_table para tests
if (!function_exists('maybe_create_table')) {
    function maybe_create_table($table_name, $create_ddl) {
        return true;
    }
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

// WordPress salts y keys para tests
if (!defined('NONCE_SALT')) {
    define('NONCE_SALT', 'test-nonce-salt-for-testing-12345');
}

if (!defined('AUTH_SALT')) {
    define('AUTH_SALT', 'test-auth-salt-for-testing-12345');
}

if (!defined('SECURE_AUTH_SALT')) {
    define('SECURE_AUTH_SALT', 'test-secure-auth-salt-12345');
}

if (!defined('LOGGED_IN_SALT')) {
    define('LOGGED_IN_SALT', 'test-logged-in-salt-12345');
}

// Constantes de Flavor Platform
if (!defined('FLAVOR_MAX_POSTS_PER_QUERY')) {
    define('FLAVOR_MAX_POSTS_PER_QUERY', 200);
}

if (!defined('FLAVOR_PLATFORM_TEXT_DOMAIN')) {
    define('FLAVOR_PLATFORM_TEXT_DOMAIN', 'flavor-platform');
}

if (!defined('FLAVOR_PLATFORM_VERSION')) {
    define('FLAVOR_PLATFORM_VERSION', '3.5.0');
}

if (!defined('FLAVOR_PLATFORM_URL')) {
    define('FLAVOR_PLATFORM_URL', 'https://example.com/wp-content/plugins/flavor-platform/');
}

// Cargar autoloader de Composer si existe
$composer_autoload = FLAVOR_PLUGIN_DIR . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

// Mock de funciones de WordPress para tests unitarios
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data) {
        return $data;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($title, $fallback_title = '', $context = 'save') {
        return strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $title));
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name($filename) {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }
}

if (!function_exists('get_locale')) {
    function get_locale() {
        return 'es_ES';
    }
}

if (!function_exists('determine_locale')) {
    function determine_locale() {
        return get_locale();
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) {
        if (is_object($args)) {
            $parsed_args = get_object_vars($args);
        } elseif (is_array($args)) {
            $parsed_args = $args;
        } else {
            parse_str($args, $parsed_args);
        }
        return array_merge($defaults, $parsed_args);
    }
}

if (!function_exists('wp_list_pluck')) {
    function wp_list_pluck($list, $field, $index_key = null) {
        $result = array();
        foreach ($list as $key => $value) {
            if (is_object($value)) {
                $result[$key] = $value->$field ?? null;
            } elseif (is_array($value)) {
                $result[$key] = $value[$field] ?? null;
            }
        }
        return $result;
    }
}

if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs((int) $maybeint);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        if ($type === 'mysql') {
            return date('Y-m-d H:i:s');
        }
        return time();
    }
}

if (!function_exists('wp_salt')) {
    function wp_salt($scheme = 'auth') {
        $salts = array(
            'auth'        => defined('AUTH_SALT') ? AUTH_SALT : 'test-auth-salt',
            'secure_auth' => 'test-secure-auth-salt',
            'logged_in'   => 'test-logged-in-salt',
        );

        return $salts[$scheme] ?? 'test-generic-salt';
    }
}

// Mock de get_option para tests
if (!function_exists('get_option')) {
    function get_option($optionName, $default = false) {
        static $optionsMock = [];
        return $optionsMock[$optionName] ?? $default;
    }
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($response) {
        return $response;
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = array(), $override = false) {
        return true;
    }
}

if (!function_exists('get_site_url')) {
    function get_site_url($blog_id = null, $path = '', $scheme = null) {
        return 'https://example.com';
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '', $filter = 'raw') {
        if ($show === 'version') {
            return '6.4';
        }

        return 'Flavor Test Site';
    }
}

if (!function_exists('is_multisite')) {
    function is_multisite() {
        return false;
    }
}

if (!function_exists('rest_get_server')) {
    function rest_get_server() {
        global $flavor_test_rest_server;

        if ( isset( $flavor_test_rest_server ) ) {
            return $flavor_test_rest_server;
        }

        return new Flavor_Test_REST_Server();
    }
}

// Mock de update_option para tests
if (!function_exists('update_option')) {
    function update_option($optionName, $value, $autoload = null) {
        return true;
    }
}

// Mock de get_transient para tests
if (!function_exists('get_transient')) {
    function get_transient($transientName) {
        return false;
    }
}

// Mock de set_transient para tests
if (!function_exists('set_transient')) {
    function set_transient($transientName, $value, $expiration = 0) {
        return true;
    }
}

// Mock de delete_transient para tests
if (!function_exists('delete_transient')) {
    function delete_transient($transientName) {
        return true;
    }
}

// Mock de apply_filters para tests
if (!function_exists('apply_filters')) {
    function apply_filters($hookName, $value, ...$arguments) {
        return $value;
    }
}

// Mock de do_action para tests
if (!function_exists('do_action')) {
    function do_action($hookName, ...$arguments) {
        // No hacer nada en tests
    }
}

// Mock de add_action para tests
if (!function_exists('add_action')) {
    function add_action($hookName, $callback, $priority = 10, $acceptedArguments = 1) {
        return true;
    }
}

// Mock de add_filter para tests
if (!function_exists('add_filter')) {
    function add_filter($hookName, $callback, $priority = 10, $acceptedArguments = 1) {
        return true;
    }
}

// Mock de plugin_dir_path para tests
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

// Mock de plugin_dir_url para tests
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'https://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

// Mock de plugin_basename para tests
if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

// Mock de is_admin para tests
if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

// Mock de wp_generate_password para tests
if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $specialChars = true, $extraSpecialChars = false) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}

// Mock de user_can para tests
if (!function_exists('user_can')) {
    function user_can($user, $capability, ...$arguments) {
        return true;
    }
}

// Mock de get_current_user_id para tests
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 0;
    }
}

// Mock de current_user_can para tests
if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$arguments) {
        return true;
    }
}

// Mock de esc_url para tests
if (!function_exists('esc_url')) {
    function esc_url($url, $protocols = null, $context = 'display') {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

// Mock de wp_remote_post para tests
if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $arguments = []) {
        return [
            'response' => ['code' => 200],
            'body' => '{"success": true}',
        ];
    }
}

// Mock de wp_remote_retrieve_response_code para tests
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return $response['response']['code'] ?? 0;
    }
}

// Mock de wp_remote_retrieve_body para tests
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'] ?? '';
    }
}

// Mock de wp_hash para tests
if (!function_exists('wp_hash')) {
    function wp_hash($data, $scheme = 'auth') {
        $salt = wp_salt($scheme);
        return hash_hmac('md5', $data, $salt);
    }
}

// Mock de sanitize_key para tests
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}

// Mock de wp_cache_delete para tests
if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($key, $group = '') {
        return true;
    }
}

// Mock de wp_cache_get para tests
if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '', $force = false, &$found = null) {
        $found = false;
        return false;
    }
}

// Mock de wp_cache_set para tests
if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $data, $group = '', $expire = 0) {
        return true;
    }
}

// Mock de home_url para tests
if (!function_exists('home_url')) {
    function home_url($path = '', $scheme = null) {
        return 'https://example.com' . $path;
    }
}

// Mock de _e para tests
if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

// Mock de esc_html__ para tests
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return esc_html($text);
    }
}

// Mock de esc_attr__ para tests
if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default') {
        return esc_attr($text);
    }
}

// Mock de wp_get_environment_type para tests
if (!function_exists('wp_get_environment_type')) {
    function wp_get_environment_type() {
        return 'local';
    }
}

// Mock de $wpdb global para tests
if (!isset($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = new class {
        public $prefix = 'wp_';
        public $posts = 'wp_posts';
        public $postmeta = 'wp_postmeta';
        public $users = 'wp_users';
        public $usermeta = 'wp_usermeta';
        public $options = 'wp_options';
        public $terms = 'wp_terms';
        public $term_taxonomy = 'wp_term_taxonomy';
        public $term_relationships = 'wp_term_relationships';
        public $last_error = '';
        public $insert_id = 0;

        public function prepare($query, ...$args) {
            if (empty($args)) {
                return $query;
            }
            // Flatten args if first arg is an array
            if (count($args) === 1 && is_array($args[0])) {
                $args = $args[0];
            }
            // Simple placeholder replacement
            $i = 0;
            return preg_replace_callback('/%[sd]/', function($m) use (&$i, $args) {
                $val = isset($args[$i]) ? $args[$i++] : '';
                if (is_array($val)) {
                    return "'" . implode("','", array_map('addslashes', $val)) . "'";
                }
                return "'" . addslashes((string)$val) . "'";
            }, $query);
        }

        public function get_results($query = null, $output = OBJECT) {
            return array();
        }

        public function get_row($query = null, $output = OBJECT, $y = 0) {
            return null;
        }

        public function get_var($query = null, $x = 0, $y = 0) {
            return null;
        }

        public function get_col($query = null, $x = 0) {
            return array();
        }

        public function insert($table, $data, $format = null) {
            $this->insert_id = rand(1, 10000);
            return 1;
        }

        public function update($table, $data, $where, $format = null, $where_format = null) {
            return 1;
        }

        public function delete($table, $where, $where_format = null) {
            return 1;
        }

        public function query($query) {
            return true;
        }

        public function get_charset_collate() {
            return 'utf8mb4_unicode_ci';
        }
    };
}

// Mock de wp_insert_post para tests
if (!function_exists('wp_insert_post')) {
    function wp_insert_post($postarr, $wp_error = false, $fire_after_hooks = true) {
        static $post_id = 1000;
        return $post_id++;
    }
}

// Mock de wp_update_post para tests
if (!function_exists('wp_update_post')) {
    function wp_update_post($postarr = array(), $wp_error = false, $fire_after_hooks = true) {
        return isset($postarr['ID']) ? $postarr['ID'] : 0;
    }
}

// Mock de wp_delete_post para tests
if (!function_exists('wp_delete_post')) {
    function wp_delete_post($postid = 0, $force_delete = false) {
        return true;
    }
}

// Mock de get_post para tests
if (!function_exists('get_post')) {
    function get_post($post = null, $output = OBJECT, $filter = 'raw') {
        if (is_numeric($post)) {
            return (object) array(
                'ID' => $post,
                'post_title' => 'Test Post',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_author' => 1,
            );
        }
        return $post;
    }
}

// Mock de get_post_meta para tests
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        static $meta = array();
        if (empty($key)) {
            return isset($meta[$post_id]) ? $meta[$post_id] : array();
        }
        if (isset($meta[$post_id][$key])) {
            return $single ? $meta[$post_id][$key][0] : $meta[$post_id][$key];
        }
        return $single ? '' : array();
    }
}

// Mock de update_post_meta para tests
if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '') {
        return true;
    }
}

// Mock de delete_post_meta para tests
if (!function_exists('delete_post_meta')) {
    function delete_post_meta($post_id, $meta_key, $meta_value = '') {
        return true;
    }
}

// Mock de post_type_exists para tests
if (!function_exists('post_type_exists')) {
    function post_type_exists($post_type) {
        return true;
    }
}

// Mock de get_the_title para tests
if (!function_exists('get_the_title')) {
    function get_the_title($post = 0) {
        if (is_object($post) && isset($post->post_title)) {
            return $post->post_title;
        }
        return 'Test Title';
    }
}

// Mock de has_filter para tests
if (!function_exists('has_filter')) {
    function has_filter($hook_name, $callback = false) {
        return false;
    }
}

// Mock de remove_filter para tests
if (!function_exists('remove_filter')) {
    function remove_filter($hook_name, $callback, $priority = 10) {
        return true;
    }
}

// Mock de load_plugin_textdomain para tests
if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) {
        return true;
    }
}

// Mock de register_activation_hook para tests
if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        return true;
    }
}

// Mock de register_deactivation_hook para tests
if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        return true;
    }
}

// Mock de wp_enqueue_style para tests
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        return true;
    }
}

// Mock de wp_enqueue_script para tests
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        return true;
    }
}

// Mock de wp_register_style para tests
if (!function_exists('wp_register_style')) {
    function wp_register_style($handle, $src, $deps = array(), $ver = false, $media = 'all') {
        return true;
    }
}

// Mock de wp_register_script para tests
if (!function_exists('wp_register_script')) {
    function wp_register_script($handle, $src, $deps = array(), $ver = false, $in_footer = false) {
        return true;
    }
}

// Mock de wp_localize_script para tests
if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        return true;
    }
}

// Mock de wp_create_nonce para tests
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'test_nonce_' . md5($action);
    }
}

// Mock de wp_verify_nonce para tests
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return strpos($nonce, 'test_nonce_') === 0;
    }
}

// Mock de check_ajax_referer para tests
if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $die = true) {
        return true;
    }
}

// Mock de admin_url para tests
if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return 'https://example.com/wp-admin/' . $path;
    }
}

// Mocks de funciones flavor_* necesarias para cargar clases del plugin
if (!function_exists('flavor_get_vbp_api_key_from_request')) {
    function flavor_get_vbp_api_key_from_request($request) {
        if (is_object($request) && method_exists($request, 'get_header')) {
            $api_key = $request->get_header('X-VBP-Key');
            if (!empty($api_key)) {
                return $api_key;
            }
        }
        if (is_object($request) && method_exists($request, 'get_param')) {
            return $request->get_param('api_key');
        }
        return '';
    }
}

if (!function_exists('flavor_check_vbp_automation_access')) {
    function flavor_check_vbp_automation_access($key, $scope) {
        // En tests, aceptar la key de test
        return $key === 'test-vbp-key-12345' || $key === 'flavor-vbp-2024';
    }
}

// Cargar clases del plugin que se testean directamente
// (después de que todos los mocks estén definidos)
require_once FLAVOR_PLUGIN_DIR . '/includes/api/class-module-compatibility-api.php';

// Cargar clases VBP para tests
if ( file_exists( FLAVOR_PLUGIN_DIR . '/includes/visual-builder-pro/class-vbp-symbols.php' ) ) {
    require_once FLAVOR_PLUGIN_DIR . '/includes/visual-builder-pro/class-vbp-symbols.php';
}
if ( file_exists( FLAVOR_PLUGIN_DIR . '/includes/visual-builder-pro/class-vbp-branching.php' ) ) {
    require_once FLAVOR_PLUGIN_DIR . '/includes/visual-builder-pro/class-vbp-branching.php';
}
if ( file_exists( FLAVOR_PLUGIN_DIR . '/includes/visual-builder-pro/class-vbp-global-styles.php' ) ) {
    require_once FLAVOR_PLUGIN_DIR . '/includes/visual-builder-pro/class-vbp-global-styles.php';
}

// Cargar clase base para tests VBP
if ( file_exists( FLAVOR_TEST_DIR . '/class-vbp-unit-test-case.php' ) ) {
    require_once FLAVOR_TEST_DIR . '/class-vbp-unit-test-case.php';
}

// Clase base para tests
class Flavor_Test_REST_Server {
    public function get_routes( $namespace = null ) {
        return array(
            '/flavor-eventos/v1/items' => array(),
            '/flavor-socios/v1/items'  => array(),
            '/flavor-foros/v1/items'   => array(),
        );
    }
}

abstract class Flavor_TestCase extends \PHPUnit\Framework\TestCase {

    /**
     * Setup antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();
    }

    /**
     * Teardown después de cada test
     */
    protected function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Helper para crear mock de $wpdb
     */
    protected function createWpdbMock() {
        $wpdb = $this->createMock(\stdClass::class);
        $wpdb->prefix = 'wp_';
        return $wpdb;
    }
}
