<?php
/**
 * Mock WordPress Test Framework.
 *
 * Provides minimal WP_UnitTestCase functionality for running tests
 * without the full WordPress test suite.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

// phpcs:disable WordPress.NamingConventions

// Define WordPress constants if not defined.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( dirname( __FILE__ ) ) . '/' );
}

if ( ! defined( 'FLAVOR_PLATFORM_TEXT_DOMAIN' ) ) {
    define( 'FLAVOR_PLATFORM_TEXT_DOMAIN', 'flavor-platform' );
}

// Mock essential WordPress functions.
if ( ! function_exists( 'wp_json_encode' ) ) {
    /**
     * Mock wp_json_encode.
     *
     * @param mixed $data   Data to encode.
     * @param int   $options JSON options.
     * @param int   $depth   Maximum depth.
     * @return string|false
     */
    function wp_json_encode( $data, $options = 0, $depth = 512 ) {
        return json_encode( $data, $options, $depth );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    /**
     * Mock sanitize_text_field.
     *
     * @param string $str String to sanitize.
     * @return string
     */
    function sanitize_text_field( $str ) {
        return htmlspecialchars( strip_tags( trim( $str ) ), ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'sanitize_title' ) ) {
    /**
     * Mock sanitize_title.
     *
     * @param string $title Title to sanitize.
     * @return string
     */
    function sanitize_title( $title ) {
        return strtolower( preg_replace( '/[^a-zA-Z0-9-]/', '-', trim( $title ) ) );
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    /**
     * Mock sanitize_key.
     *
     * @param string $key Key to sanitize.
     * @return string
     */
    function sanitize_key( $key ) {
        return strtolower( preg_replace( '/[^a-z0-9_-]/', '', $key ) );
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    /**
     * Mock sanitize_textarea_field.
     *
     * @param string $str String to sanitize.
     * @return string
     */
    function sanitize_textarea_field( $str ) {
        return htmlspecialchars( strip_tags( $str ), ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    /**
     * Mock esc_url_raw.
     *
     * @param string $url URL to escape.
     * @return string
     */
    function esc_url_raw( $url ) {
        return filter_var( $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'get_current_user_id' ) ) {
    /**
     * Mock get_current_user_id.
     *
     * @return int
     */
    function get_current_user_id() {
        return 1;
    }
}

if ( ! function_exists( 'current_user_can' ) ) {
    /**
     * Mock current_user_can.
     *
     * @param string $capability Capability to check.
     * @return bool
     */
    function current_user_can( $capability ) {
        return true;
    }
}

if ( ! function_exists( 'get_the_author_meta' ) ) {
    /**
     * Mock get_the_author_meta.
     *
     * @param string $field   Field to retrieve.
     * @param int    $user_id User ID.
     * @return string
     */
    function get_the_author_meta( $field, $user_id = 0 ) {
        if ( 'display_name' === $field ) {
            return 'Test User';
        }
        return '';
    }
}

if ( ! function_exists( 'wp_parse_args' ) ) {
    /**
     * Mock wp_parse_args.
     *
     * @param array $args     Arguments to parse.
     * @param array $defaults Default values.
     * @return array
     */
    function wp_parse_args( $args, $defaults = array() ) {
        if ( is_object( $args ) ) {
            $args = get_object_vars( $args );
        }
        return array_merge( $defaults, $args );
    }
}

if ( ! function_exists( '__' ) ) {
    /**
     * Mock translation function.
     *
     * @param string $text   Text to translate.
     * @param string $domain Text domain.
     * @return string
     */
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    /**
     * Mock WP_Error class.
     */
    class WP_Error {
        /**
         * Error code.
         *
         * @var string
         */
        private $code;

        /**
         * Error message.
         *
         * @var string
         */
        private $message;

        /**
         * Error data.
         *
         * @var mixed
         */
        private $data;

        /**
         * Constructor.
         *
         * @param string $code    Error code.
         * @param string $message Error message.
         * @param mixed  $data    Error data.
         */
        public function __construct( $code = '', $message = '', $data = '' ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }

        /**
         * Get error code.
         *
         * @return string
         */
        public function get_error_code() {
            return $this->code;
        }

        /**
         * Get error message.
         *
         * @return string
         */
        public function get_error_message() {
            return $this->message;
        }

        /**
         * Get error data.
         *
         * @return mixed
         */
        public function get_error_data() {
            return $this->data;
        }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    /**
     * Check if value is WP_Error.
     *
     * @param mixed $thing Value to check.
     * @return bool
     */
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

/**
 * Base test case class for running tests without WordPress.
 */
class WP_UnitTestCase extends PHPUnit\Framework\TestCase {

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();
    }

    /**
     * Tear down after each test.
     */
    protected function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Assert that value is WP_Error.
     *
     * @param mixed  $actual  Value to check.
     * @param string $message Failure message.
     */
    public function assertWPError( $actual, $message = '' ) {
        $this->assertInstanceOf( 'WP_Error', $actual, $message );
    }

    /**
     * Assert that value is not WP_Error.
     *
     * @param mixed  $actual  Value to check.
     * @param string $message Failure message.
     */
    public function assertNotWPError( $actual, $message = '' ) {
        $this->assertNotInstanceOf( 'WP_Error', $actual, $message );
    }
}

// Load test case base class.
require_once FLAVOR_PLATFORM_TEST_DIR . '/class-vbp-unit-test-case.php';
