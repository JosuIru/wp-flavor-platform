<?php
/**
 * Tests de Flavor_VBP_Paginated_Collection_Trait.
 *
 * Cubre la lógica de paginación compartida por todas las Collections:
 * extract_pagination (clamps) y get_total_count (COUNT + manejo de
 * tabla ausente). Se usa un wpdb falso que registra las queries para
 * poder aserciones sobre qué SQL generó el trait.
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once FLAVOR_PLUGIN_DIR . '/addons/flavor-visual-builder-pro/collections/interface-collection-source.php';
require_once FLAVOR_PLUGIN_DIR . '/addons/flavor-visual-builder-pro/collections/trait-paginated-collection.php';

/**
 * Mock de wpdb suficiente para el trait: prefix, prepare (no-op),
 * get_var con respuestas programables y registro de queries.
 */
class Flavor_Fake_Wpdb_For_Paginated_Trait {
    public $prefix = 'wp_';
    public $queries_preparadas = array();
    public $get_var_responses = array();
    private $response_index = 0;

    public function prepare( $sql, ...$args ) {
        // Sustitución naïve de placeholders. Suficiente para tests
        // porque sólo verificamos estructura, no SQL exacto.
        foreach ( $args as $arg ) {
            if ( is_array( $arg ) ) {
                foreach ( $arg as $inner ) {
                    $sql = preg_replace( '/%s|%d|%f/', is_string( $inner ) ? "'" . $inner . "'" : $inner, $sql, 1 );
                }
            } else {
                $sql = preg_replace( '/%s|%d|%f/', is_string( $arg ) ? "'" . $arg . "'" : $arg, $sql, 1 );
            }
        }
        $this->queries_preparadas[] = $sql;
        return $sql;
    }

    public function get_var( $sql ) {
        $this->queries_preparadas[] = $sql;
        if ( isset( $this->get_var_responses[ $this->response_index ] ) ) {
            return $this->get_var_responses[ $this->response_index++ ];
        }
        return null;
    }
}

/**
 * Source concreto mínimo que usa el trait para poder exponer sus
 * métodos protegidos a los tests.
 */
class Flavor_Test_Source_For_Paginated_Trait implements Flavor_VBP_Collection_Source {
    use Flavor_VBP_Paginated_Collection_Trait;

    public $test_table_name = 'wp_test_items';
    public $test_where_clause = 'estado = %s';
    public $test_where_args   = array( 'activo' );
    public $override_default_limit = null;

    public function get_identifier() { return 'test_items'; }
    public function get_label()      { return 'Items de prueba'; }
    public function get_description(){ return ''; }

    public function get_query_fields() {
        return $this->get_pagination_fields();
    }

    public function query( array $query_args ) {
        return array();
    }

    // Expose trait internals for assertion.
    public function test_extract_pagination( array $args ) {
        return $this->extract_pagination( $args );
    }

    public function test_get_pagination_fields() {
        return $this->get_pagination_fields();
    }

    // Trait abstract methods
    protected function get_table_name() {
        return $this->test_table_name;
    }

    protected function build_where_and_args( array $query_args ) {
        return array( $this->test_where_clause, $this->test_where_args );
    }

    protected function get_default_limit() {
        if ( $this->override_default_limit !== null ) {
            return $this->override_default_limit;
        }
        return 10;
    }
}

class PaginatedCollectionTraitTest extends Flavor_TestCase {

    private function con_mock_wpdb( callable $callback, array $responses = array() ) {
        global $wpdb;
        $wpdb_original = $wpdb;

        $mock = new Flavor_Fake_Wpdb_For_Paginated_Trait();
        $mock->get_var_responses = $responses;
        $wpdb = $mock;

        try {
            $resultado = $callback( $mock );
        } finally {
            $wpdb = $wpdb_original;
        }
        return $resultado;
    }

    // ===== extract_pagination =====

    public function test_extract_pagination_uses_defaults_when_args_empty() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();
        $paginacion = $source->test_extract_pagination( array() );

        $this->assertSame( 1, $paginacion['page'] );
        $this->assertSame( 10, $paginacion['limit'] );
        $this->assertSame( 0, $paginacion['offset'] );
    }

    public function test_extract_pagination_computes_offset_from_page() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();
        $paginacion = $source->test_extract_pagination( array( 'page' => 3, 'limit' => 10 ) );

        $this->assertSame( 3, $paginacion['page'] );
        $this->assertSame( 10, $paginacion['limit'] );
        $this->assertSame( 20, $paginacion['offset'] );
    }

    public function test_extract_pagination_clamps_page_to_minimum_one() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();

        $this->assertSame( 1, $source->test_extract_pagination( array( 'page' => 0 ) )['page'] );
        $this->assertSame( 1, $source->test_extract_pagination( array( 'page' => -5 ) )['page'] );
    }

    public function test_extract_pagination_clamps_page_to_maximum() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();
        $paginacion = $source->test_extract_pagination( array( 'page' => 5000 ) );

        $this->assertSame( 1000, $paginacion['page'] );
    }

    public function test_extract_pagination_clamps_limit_to_range_1_50() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();

        $this->assertSame( 50, $source->test_extract_pagination( array( 'limit' => 200 ) )['limit'] );
        $this->assertSame( 1, $source->test_extract_pagination( array( 'limit' => 0 ) )['limit'] );
        $this->assertSame( 1, $source->test_extract_pagination( array( 'limit' => -10 ) )['limit'] );
    }

    public function test_extract_pagination_respects_source_default_limit_override() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();
        $source->override_default_limit = 25;

        $paginacion = $source->test_extract_pagination( array() );

        $this->assertSame( 25, $paginacion['limit'] );
    }

    // ===== get_pagination_fields =====

    public function test_get_pagination_fields_exposes_page_and_limit() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();
        $campos = $source->test_get_pagination_fields();

        $this->assertArrayHasKey( 'page', $campos );
        $this->assertArrayHasKey( 'limit', $campos );
        $this->assertSame( 'int', $campos['page']['type'] );
        $this->assertSame( 'int', $campos['limit']['type'] );
        $this->assertSame( 1, $campos['page']['min'] );
        $this->assertSame( 1000, $campos['page']['max'] );
        $this->assertSame( 1, $campos['limit']['min'] );
        $this->assertSame( 50, $campos['limit']['max'] );
    }

    public function test_get_pagination_fields_uses_overridden_default_limit() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();
        $source->override_default_limit = 12;

        $campos = $source->test_get_pagination_fields();

        $this->assertSame( 12, $campos['limit']['default'] );
    }

    // ===== get_total_count =====

    public function test_get_total_count_returns_zero_when_table_missing() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();

        $total = $this->con_mock_wpdb( function ( $mock ) use ( $source ) {
            // Primera respuesta: SHOW TABLES LIKE devuelve null → no existe.
            return $source->get_total_count( array( 'estado' => 'activo' ) );
        }, array( null ) );

        $this->assertSame( 0, $total );
    }

    public function test_get_total_count_returns_count_when_table_exists() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();

        $total = $this->con_mock_wpdb( function ( $mock ) use ( $source ) {
            return $source->get_total_count( array( 'estado' => 'activo' ) );
        }, array(
            'wp_test_items', // SHOW TABLES LIKE devuelve el nombre
            42               // SELECT COUNT(*) devuelve 42
        ) );

        $this->assertSame( 42, $total );
    }

    public function test_get_total_count_casts_non_int_response_to_int() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();

        // wpdb puede devolver string cuando get_var no detecta int.
        $total = $this->con_mock_wpdb( function ( $mock ) use ( $source ) {
            return $source->get_total_count( array() );
        }, array( 'wp_test_items', '17' ) );

        $this->assertSame( 17, $total );
        $this->assertIsInt( $total );
    }

    public function test_get_total_count_builds_sql_with_source_where() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();
        $source->test_where_clause = 'estado = %s AND tipo = %s';
        $source->test_where_args   = array( 'activo', 'evento' );

        $this->con_mock_wpdb( function ( $mock ) use ( $source ) {
            $source->get_total_count( array() );

            // queries_preparadas[0]: SHOW TABLES LIKE.
            // queries_preparadas[1]: prepare del COUNT con placeholders.
            // queries_preparadas[2]: get_var ejecutando el SELECT COUNT.
            $sql_count = $mock->queries_preparadas[2];

            $this->assertStringContainsString( 'SELECT COUNT(*)', $sql_count );
            $this->assertStringContainsString( 'wp_test_items', $sql_count );
            // Los placeholders ya fueron sustituidos por prepare() mock.
            $this->assertStringContainsString( "'activo'", $sql_count );
            $this->assertStringContainsString( "'evento'", $sql_count );
        }, array( 'wp_test_items', 5 ) );
    }

    public function test_get_total_count_ignores_limit_and_page_in_count() {
        $source = new Flavor_Test_Source_For_Paginated_Trait();

        $this->con_mock_wpdb( function ( $mock ) use ( $source ) {
            $source->get_total_count( array( 'page' => 99, 'limit' => 1 ) );

            $sql_count = $mock->queries_preparadas[2];

            $this->assertStringNotContainsString( 'LIMIT', strtoupper( $sql_count ) );
            $this->assertStringNotContainsString( 'OFFSET', strtoupper( $sql_count ) );
        }, array( 'wp_test_items', 100 ) );
    }
}
