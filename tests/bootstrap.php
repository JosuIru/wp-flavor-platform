<?php
/**
 * PHPUnit bootstrap file for Flavor Chat IA tests
 *
 * @package FlavorChatIA
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

if ( ! defined( 'FLAVOR_CHAT_IA_PATH' ) ) {
    define( 'FLAVOR_CHAT_IA_PATH', FLAVOR_PLUGIN_DIR . '/' );
}

// Cargar autoloader de Composer si existe
$composer_autoload = FLAVOR_PLUGIN_DIR . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

require_once FLAVOR_PLUGIN_DIR . '/includes/api/class-module-compatibility-api.php';

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

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
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

// Mock de is_wp_error para tests
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public $code;
        public $message;
        public $data;

        public function __construct( $code = '', $message = '', $data = null ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }
    }
}

// Mock de flavor_chat_ia_log para tests
if (!function_exists('flavor_chat_ia_log')) {
    function flavor_chat_ia_log($message, $level = 'info', $module = '') {
        // No hacer nada en tests, o descomentar para debug:
        // echo "[{$level}] {$module}: {$message}\n";
    }
}

// Mock de flavor_log_debug para tests
if (!function_exists('flavor_log_debug')) {
    function flavor_log_debug($message, $module = '') {
        // No hacer nada en tests
    }
}

// Mock de flavor_log_error para tests
if (!function_exists('flavor_log_error')) {
    function flavor_log_error($message, $module = '') {
        // No hacer nada en tests
    }
}

if ( ! function_exists( 'flavor_get_vbp_api_key_from_request' ) ) {
    function flavor_get_vbp_api_key_from_request( $request ) {
        if ( is_object( $request ) && method_exists( $request, 'get_header' ) ) {
            $api_key = $request->get_header( 'X-VBP-Key' );
            if ( ! empty( $api_key ) ) {
                return $api_key;
            }
        }

        if ( is_object( $request ) && method_exists( $request, 'get_param' ) ) {
            return $request->get_param( 'api_key' );
        }

        return '';
    }
}

if ( ! function_exists( 'flavor_check_vbp_automation_access' ) ) {
    function flavor_check_vbp_automation_access( $key, $scope ) {
        return $key === 'flavor-vbp-2024';
    }
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
