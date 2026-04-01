<?php
/**
 * Mocks de funciones de WordPress para tests standalone
 *
 * Permite ejecutar tests unitarios sin cargar WordPress completo
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

// Constantes de tiempo
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

// Constantes del addon
if (!defined('FLAVOR_MULTILINGUAL_VERSION')) {
    define('FLAVOR_MULTILINGUAL_VERSION', '1.2.0');
}
if (!defined('FLAVOR_MULTILINGUAL_PATH')) {
    define('FLAVOR_MULTILINGUAL_PATH', dirname(__DIR__, 2) . '/');
}

/**
 * Mock de almacenamiento para options/transients
 */
class WP_Mock_Storage {
    private static $options = array();
    private static $transients = array();
    private static $transient_timeouts = array();

    public static function get_option($key, $default = false) {
        return isset(self::$options[$key]) ? self::$options[$key] : $default;
    }

    public static function update_option($key, $value) {
        self::$options[$key] = $value;
        return true;
    }

    public static function delete_option($key) {
        unset(self::$options[$key]);
        return true;
    }

    public static function get_transient($key) {
        if (!isset(self::$transients[$key])) {
            return false;
        }

        // Verificar expiración
        if (isset(self::$transient_timeouts[$key]) && self::$transient_timeouts[$key] < time()) {
            unset(self::$transients[$key]);
            unset(self::$transient_timeouts[$key]);
            return false;
        }

        return self::$transients[$key];
    }

    public static function set_transient($key, $value, $expiration = 0) {
        self::$transients[$key] = $value;
        if ($expiration > 0) {
            self::$transient_timeouts[$key] = time() + $expiration;
        }
        return true;
    }

    public static function delete_transient($key) {
        unset(self::$transients[$key]);
        unset(self::$transient_timeouts[$key]);
        return true;
    }

    public static function reset() {
        self::$options = array();
        self::$transients = array();
        self::$transient_timeouts = array();
    }
}

// Funciones de options
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return WP_Mock_Storage::get_option($option, $default);
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return WP_Mock_Storage::update_option($option, $value);
    }
}

if (!function_exists('add_option')) {
    function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {
        if (WP_Mock_Storage::get_option($option) === false) {
            return WP_Mock_Storage::update_option($option, $value);
        }
        return false;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        return WP_Mock_Storage::delete_option($option);
    }
}

// Funciones de transients
if (!function_exists('get_transient')) {
    function get_transient($transient) {
        return WP_Mock_Storage::get_transient($transient);
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        return WP_Mock_Storage::set_transient($transient, $value, $expiration);
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        return WP_Mock_Storage::delete_transient($transient);
    }
}

// Funciones de cache
if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '', $force = false, &$found = null) {
        $full_key = $group . '_' . $key;
        $value = WP_Mock_Storage::get_transient('cache_' . $full_key);
        $found = $value !== false;
        return $value;
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $data, $group = '', $expire = 0) {
        $full_key = $group . '_' . $key;
        return WP_Mock_Storage::set_transient('cache_' . $full_key, $data, $expire);
    }
}

if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($key, $group = '') {
        $full_key = $group . '_' . $key;
        return WP_Mock_Storage::delete_transient('cache_' . $full_key);
    }
}

if (!function_exists('wp_cache_flush_group')) {
    function wp_cache_flush_group($group) {
        return true;
    }
}

if (!function_exists('wp_using_ext_object_cache')) {
    function wp_using_ext_object_cache() {
        return false;
    }
}

// Funciones de hooks
if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($tag, ...$args) {
        return null;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        return $value;
    }
}

if (!function_exists('did_action')) {
    function did_action($tag) {
        return 0;
    }
}

// Funciones de scheduling
if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = array()) {
        return false;
    }
}

if (!function_exists('wp_schedule_single_event')) {
    function wp_schedule_single_event($timestamp, $hook, $args = array()) {
        return true;
    }
}

// Funciones de sanitización
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data) {
        return $data;
    }
}

if (!function_exists('esc_sql')) {
    function esc_sql($data) {
        global $wpdb;
        if (isset($wpdb)) {
            return $wpdb->_real_escape($data);
        }
        return addslashes($data);
    }
}

// Funciones de usuario
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

// Funciones de error_log
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

// Clase WP_Error básica
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $errors = array();
        private $error_data = array();

        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->add($code, $message, $data);
            }
        }

        public function add($code, $message, $data = '') {
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        public function get_error_code() {
            $codes = array_keys($this->errors);
            return reset($codes);
        }

        public function get_error_message($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            if (isset($this->errors[$code])) {
                return $this->errors[$code][0];
            }
            return '';
        }

        public function get_error_codes() {
            return array_keys($this->errors);
        }

        public function has_errors() {
            return !empty($this->errors);
        }
    }
}

// Mock básico de $wpdb
if (!isset($GLOBALS['wpdb'])) {
    class WP_Mock_WPDB {
        public $prefix = 'wp_';
        public $posts = 'wp_posts';
        public $postmeta = 'wp_postmeta';
        public $options = 'wp_options';
        public $terms = 'wp_terms';
        public $term_taxonomy = 'wp_term_taxonomy';
        public $term_relationships = 'wp_term_relationships';
        public $termmeta = 'wp_termmeta';

        private $results = array();

        public function prepare($query, ...$args) {
            return vsprintf(str_replace('%s', "'%s'", $query), $args);
        }

        public function get_var($query) {
            return null;
        }

        public function get_row($query, $output = OBJECT, $y = 0) {
            return null;
        }

        public function get_results($query, $output = OBJECT) {
            return array();
        }

        public function get_col($query, $x = 0) {
            return array();
        }

        public function query($query) {
            return true;
        }

        public function insert($table, $data, $format = null) {
            return true;
        }

        public function update($table, $data, $where, $format = null, $where_format = null) {
            return true;
        }

        public function delete($table, $where, $where_format = null) {
            return true;
        }

        public function _real_escape($string) {
            return addslashes($string);
        }

        public function get_charset_collate() {
            return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }
    }

    $GLOBALS['wpdb'] = new WP_Mock_WPDB();
}

// Función para resetear el estado entre tests
function wp_mock_reset() {
    WP_Mock_Storage::reset();
}
