<?php
/**
 * PHPUnit bootstrap file for Flavor Chat IA tests
 *
 * @package FlavorChatIA
 */

// Define test constants
define('FLAVOR_TESTING', true);
define('FLAVOR_TEST_DIR', __DIR__);
define('FLAVOR_PLUGIN_DIR', dirname(__DIR__));

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

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

// Clase base para tests
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
