<?php
/**
 * Tests de traits comunes.
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';

require_once FLAVOR_PLUGIN_DIR . '/includes/traits/trait-rest-response.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/traits/trait-request-validation.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/traits/trait-safe-sql.php';

/**
 * Clase de prueba que usa los traits
 */
class Flavor_Trait_Test_Class {
    use Flavor_REST_Response_Trait;
    use Flavor_Request_Validation_Trait;
    use Flavor_Safe_SQL_Trait;

    // Exponer métodos protegidos para testing
    public function test_sanitize_id($id, $allow_zero = false) {
        return $this->sanitize_id($id, $allow_zero);
    }

    public function test_sanitize_id_array($ids) {
        return $this->sanitize_id_array($ids);
    }

    public function test_normalize_pagination($page, $per_page, $limits = []) {
        return $this->normalize_pagination($page, $per_page, $limits);
    }

    public function test_validate_orderby($orderby, $whitelist, $default = 'id') {
        return $this->validate_orderby($orderby, $whitelist, $default);
    }

    public function test_validate_order($order) {
        return $this->validate_order($order);
    }

    public function test_sanitize_search($search, $min = 2, $max = 100) {
        return $this->sanitize_search($search, $min, $max);
    }

    public function test_sanitize_enum($value, $allowed, $default = '') {
        return $this->sanitize_enum($value, $allowed, $default);
    }

    public function test_validate_required($data, $fields) {
        return $this->validate_required($data, $fields);
    }

    public function test_build_where_in($ids, $column = 'id') {
        return $this->build_where_in($ids, $column);
    }

    public function test_build_order_by($orderby, $order, $whitelist, $default = 'id') {
        return $this->build_order_by($orderby, $order, $whitelist, $default);
    }

    public function test_build_limit_offset($limit, $offset = 0) {
        return $this->build_limit_offset($limit, $offset);
    }

    public function test_sanitize_date($date, $format = 'Y-m-d') {
        return $this->sanitize_date($date, $format);
    }

    public function test_run_transaction(callable $callback) {
        return $this->run_transaction($callback);
    }
}

/**
 * Mock mínimo de wpdb que registra las queries ejecutadas.
 */
class Flavor_Fake_Wpdb_For_Transactions {
    public $queries_ejecutadas = [];
    public $prefix = 'wp_';

    public function query($sql) {
        $this->queries_ejecutadas[] = $sql;
        return 1;
    }
}

class TraitsTest extends Flavor_TestCase {

    private $test_class;

    protected function setUp(): void {
        parent::setUp();
        $this->test_class = new Flavor_Trait_Test_Class();
    }

    // ===== Request Validation Trait Tests =====

    public function test_sanitize_id_returns_positive_int() {
        $this->assertSame(123, $this->test_class->test_sanitize_id('123'));
        $this->assertSame(123, $this->test_class->test_sanitize_id(123));
        $this->assertSame(0, $this->test_class->test_sanitize_id('0', true));
    }

    public function test_sanitize_id_rejects_invalid_values() {
        $this->assertNull($this->test_class->test_sanitize_id(0));
        $this->assertNull($this->test_class->test_sanitize_id(-5));
        $this->assertNull($this->test_class->test_sanitize_id('abc'));
        $this->assertNull($this->test_class->test_sanitize_id(''));
    }

    public function test_sanitize_id_array_filters_invalid() {
        $this->assertSame([1, 2, 3], $this->test_class->test_sanitize_id_array([1, 2, 3]));
        $this->assertSame([1, 2, 3], $this->test_class->test_sanitize_id_array('1,2,3'));
        $this->assertSame([5, 10], $this->test_class->test_sanitize_id_array([5, 0, -1, 10, 'abc']));
        $this->assertSame([], $this->test_class->test_sanitize_id_array([]));
        $this->assertSame([], $this->test_class->test_sanitize_id_array('invalid'));
    }

    public function test_normalize_pagination_applies_limits() {
        $result = $this->test_class->test_normalize_pagination(1, 20);
        $this->assertSame(1, $result['page']);
        $this->assertSame(20, $result['per_page']);
        $this->assertSame(0, $result['offset']);

        $result = $this->test_class->test_normalize_pagination(3, 50);
        $this->assertSame(3, $result['page']);
        $this->assertSame(50, $result['per_page']);
        $this->assertSame(100, $result['offset']);
    }

    public function test_normalize_pagination_enforces_max() {
        $result = $this->test_class->test_normalize_pagination(1, 500);
        $this->assertSame(100, $result['per_page']);

        $result = $this->test_class->test_normalize_pagination(1, 200, ['max_per_page' => 50]);
        $this->assertSame(50, $result['per_page']);
    }

    public function test_normalize_pagination_uses_defaults() {
        $result = $this->test_class->test_normalize_pagination(null, null);
        $this->assertSame(1, $result['page']);
        $this->assertSame(20, $result['per_page']);

        $result = $this->test_class->test_normalize_pagination(0, 0, ['default_per_page' => 15]);
        $this->assertSame(1, $result['page']);
        $this->assertSame(15, $result['per_page']);
    }

    public function test_validate_orderby_uses_whitelist() {
        $whitelist = ['id', 'name', 'created_at'];

        $this->assertSame('name', $this->test_class->test_validate_orderby('name', $whitelist));
        $this->assertSame('id', $this->test_class->test_validate_orderby('INVALID', $whitelist));
        $this->assertSame('id', $this->test_class->test_validate_orderby('name; DROP TABLE', $whitelist));
    }

    public function test_validate_order_normalizes_direction() {
        $this->assertSame('ASC', $this->test_class->test_validate_order('asc'));
        $this->assertSame('ASC', $this->test_class->test_validate_order('ASC'));
        $this->assertSame('DESC', $this->test_class->test_validate_order('desc'));
        $this->assertSame('DESC', $this->test_class->test_validate_order('invalid'));
        $this->assertSame('DESC', $this->test_class->test_validate_order(''));
    }

    public function test_sanitize_search_enforces_length() {
        $this->assertSame('hello', $this->test_class->test_sanitize_search('hello'));
        $this->assertNull($this->test_class->test_sanitize_search('a'));
        $this->assertNull($this->test_class->test_sanitize_search(''));
        $this->assertNull($this->test_class->test_sanitize_search(str_repeat('a', 101)));
    }

    public function test_sanitize_enum_validates_against_allowed() {
        $allowed = ['draft', 'published', 'archived'];

        $this->assertSame('published', $this->test_class->test_sanitize_enum('published', $allowed));
        $this->assertSame('', $this->test_class->test_sanitize_enum('invalid', $allowed));
        $this->assertSame('draft', $this->test_class->test_sanitize_enum('invalid', $allowed, 'draft'));
    }

    public function test_validate_required_detects_missing_fields() {
        $data = ['name' => 'Test', 'email' => ''];
        $fields = ['name' => 'Nombre', 'email' => 'Email', 'phone' => 'Teléfono'];

        $errors = $this->test_class->test_validate_required($data, $fields);

        $this->assertArrayNotHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('phone', $errors);
    }

    public function test_sanitize_date_validates_format() {
        $this->assertSame('2024-04-17', $this->test_class->test_sanitize_date('2024-04-17'));
        $this->assertNull($this->test_class->test_sanitize_date('17-04-2024'));
        $this->assertNull($this->test_class->test_sanitize_date('invalid'));
        $this->assertNull($this->test_class->test_sanitize_date('2024-13-45'));
    }

    // ===== Safe SQL Trait Tests =====

    public function test_build_where_in_creates_safe_clause() {
        global $wpdb;
        // Asegurar que wpdb esté disponible
        if (!$wpdb) {
            $this->markTestSkipped('wpdb not available');
        }
        $result = $this->test_class->test_build_where_in([1, 2, 3]);
        $this->assertStringContainsString('id IN', $result);
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('2', $result);
        $this->assertStringContainsString('3', $result);
    }

    public function test_build_where_in_returns_false_for_empty() {
        // Este test no requiere wpdb ya que devuelve '1=0' directamente
        $this->assertSame('1=0', $this->test_class->test_build_where_in([]));
        $this->assertSame('1=0', $this->test_class->test_build_where_in([0, -1, 'abc']));
    }

    public function test_build_order_by_uses_whitelist() {
        $whitelist = ['id', 'name', 'created_at'];

        $this->assertSame('ORDER BY name DESC', $this->test_class->test_build_order_by('name', 'desc', $whitelist));
        $this->assertSame('ORDER BY id ASC', $this->test_class->test_build_order_by('invalid', 'asc', $whitelist));
    }

    public function test_build_limit_offset_creates_safe_clause() {
        $this->assertSame('LIMIT 10 OFFSET 0', $this->test_class->test_build_limit_offset(10));
        $this->assertSame('LIMIT 20 OFFSET 40', $this->test_class->test_build_limit_offset(20, 40));
        $this->assertSame('LIMIT 1 OFFSET 0', $this->test_class->test_build_limit_offset(-5, -10));
    }

    // ===== run_transaction =====

    private function con_mock_wpdb(callable $ejecutar) {
        global $wpdb;
        $wpdb_original = $wpdb;
        $wpdb_mock = new Flavor_Fake_Wpdb_For_Transactions();
        $wpdb = $wpdb_mock;

        try {
            $resultado_callback = $ejecutar($wpdb_mock);
        } finally {
            $wpdb = $wpdb_original;
        }

        return $resultado_callback;
    }

    public function test_run_transaction_commits_on_success() {
        $resultado = $this->con_mock_wpdb(function ($wpdb_mock) {
            $valor_retornado = $this->test_class->test_run_transaction(function () {
                return ['id' => 42];
            });

            $this->assertSame(['id' => 42], $valor_retornado);
            $this->assertSame(
                ['START TRANSACTION', 'COMMIT'],
                $wpdb_mock->queries_ejecutadas
            );
            return true;
        });

        $this->assertTrue($resultado);
    }

    public function test_run_transaction_rolls_back_when_callback_returns_wp_error() {
        $this->con_mock_wpdb(function ($wpdb_mock) {
            $error_simulado = new WP_Error('fallo', 'Algo falló');

            $valor_retornado = $this->test_class->test_run_transaction(function () use ($error_simulado) {
                return $error_simulado;
            });

            $this->assertSame($error_simulado, $valor_retornado);
            $this->assertSame(
                ['START TRANSACTION', 'ROLLBACK'],
                $wpdb_mock->queries_ejecutadas
            );
            return true;
        });
    }

    public function test_run_transaction_rolls_back_on_exception() {
        $this->con_mock_wpdb(function ($wpdb_mock) {
            $valor_retornado = $this->test_class->test_run_transaction(function () {
                throw new Exception('Explosión dentro de la transacción');
            });

            $this->assertInstanceOf(WP_Error::class, $valor_retornado);
            $this->assertSame('transaction_failed', $valor_retornado->get_error_code());
            $this->assertSame(
                ['START TRANSACTION', 'ROLLBACK'],
                $wpdb_mock->queries_ejecutadas
            );
            return true;
        });
    }
}
