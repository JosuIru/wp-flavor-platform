<?php
/**
 * PHPUnit bootstrap para tests de INTEGRACIÓN con WordPress real.
 *
 * Este bootstrap carga WordPress completo y permite tests que interactúan
 * con la base de datos real (en una DB de tests separada).
 *
 * REQUISITOS:
 * 1. Ejecutar: ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass>
 * 2. Configurar WP_TESTS_DIR en el entorno o phpunit.xml
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

// Directorio de WordPress Test Framework
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Verificar que existe
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "ERROR: No se encontró WordPress Test Framework en: $_tests_dir\n\n";
    echo "Ejecuta primero:\n";
    echo "  ./bin/install-wp-tests.sh wordpress_test root root\n\n";
    exit( 1 );
}

// Definir constantes de test
define( 'FLAVOR_TESTING', true );
define( 'FLAVOR_INTEGRATION_TESTING', true );

// PHPUnit Polyfills - requerido por WP Test Framework
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' );

// Cargar funciones de WordPress Test Framework
require_once $_tests_dir . '/includes/functions.php';

/**
 * Cargar el plugin manualmente antes de que WordPress arranque.
 */
function _manually_load_plugin() {
    // Cargar el plugin principal
    require dirname( dirname( __FILE__ ) ) . '/flavor-platform.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Arrancar WordPress Test Framework
require $_tests_dir . '/includes/bootstrap.php';

// Cargar clase base para tests de integración
require_once dirname( __FILE__ ) . '/class-flavor-integration-test-case.php';

echo "\n=== Tests de Integración con WordPress " . get_bloginfo( 'version' ) . " ===\n\n";
