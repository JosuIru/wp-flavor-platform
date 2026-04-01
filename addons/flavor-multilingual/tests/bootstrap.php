<?php
/**
 * PHPUnit bootstrap file for Flavor Multilingual tests
 *
 * @package FlavorMultilingual
 */

// Definir constantes de test
define('FLAVOR_ML_TESTING', true);

// Buscar wordpress-tests-lib
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Si no existe, usar versión simplificada sin WordPress
if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "WordPress test library not found. Running standalone tests.\n";

    // Definir constantes mínimas
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__DIR__) . '/../../../../');
    }

    // Mock de funciones básicas de WordPress para tests standalone
    require_once __DIR__ . '/mocks/wordpress-mocks.php';

    // Cargar autoloader de Composer si existe
    $composer_autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($composer_autoload)) {
        require_once $composer_autoload;
    }

    // Cargar clases del plugin manualmente
    require_once dirname(__DIR__) . '/includes/class-translation-cache.php';
    require_once dirname(__DIR__) . '/includes/class-translation-memory.php';

    return;
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    // Cargar plugin principal de Flavor si existe
    $flavor_main = dirname(__DIR__, 3) . '/flavor-chat-ia.php';
    if (file_exists($flavor_main)) {
        require $flavor_main;
    }

    // Cargar el addon
    require dirname(__DIR__) . '/flavor-multilingual.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';
