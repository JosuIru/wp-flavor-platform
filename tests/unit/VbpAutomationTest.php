<?php
/**
 * Tests para las funciones de automatización VBP
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class VbpAutomationTest extends Flavor_TestCase {

    protected function setUp(): void {
        parent::setUp();

        // Cargar funciones del plugin
        require_once FLAVOR_PLUGIN_DIR . '/flavor-chat-ia.php';
    }

    public function test_flavor_check_vbp_automation_access_with_valid_key_and_scope() {
        $validKey = flavor_get_vbp_api_key();
        $scopes = flavor_get_vbp_automation_scopes();

        if (!empty($scopes)) {
            $result = flavor_check_vbp_automation_access($validKey, $scopes[0]);

            // Depende de si la automatización está habilitada
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('No scopes configured');
        }
    }

    public function test_flavor_check_vbp_automation_access_rejects_invalid_key() {
        $result = flavor_check_vbp_automation_access('invalid-key', 'site_builder');

        $this->assertFalse($result);
    }

    public function test_flavor_check_vbp_automation_access_rejects_empty_key() {
        $result = flavor_check_vbp_automation_access('', 'site_builder');

        $this->assertFalse($result);
    }

    public function test_flavor_vbp_automation_enabled_with_scope() {
        $result = flavor_vbp_automation_enabled('site_builder');

        $this->assertIsBool($result);
    }

    public function test_flavor_vbp_automation_enabled_with_invalid_scope() {
        $result = flavor_vbp_automation_enabled('nonexistent_scope');

        $this->assertIsBool($result);
    }

    public function test_flavor_regenerate_vbp_api_key_returns_string() {
        // Solo ejecutar si estamos en contexto de admin
        if (!function_exists('flavor_regenerate_vbp_api_key')) {
            $this->markTestSkipped('Function not available');
            return;
        }

        $newKey = flavor_regenerate_vbp_api_key();

        $this->assertIsString($newKey);
        $this->assertNotEmpty($newKey);
        $this->assertGreaterThan(20, strlen($newKey));
    }

    public function test_flavor_regenerate_vbp_api_key_creates_unique_keys() {
        if (!function_exists('flavor_regenerate_vbp_api_key')) {
            $this->markTestSkipped('Function not available');
            return;
        }

        $key1 = flavor_regenerate_vbp_api_key();
        $key2 = flavor_regenerate_vbp_api_key();

        $this->assertNotEquals($key1, $key2);
    }
}

class LoggingFunctionsTest extends Flavor_TestCase {

    protected function setUp(): void {
        parent::setUp();

        require_once FLAVOR_PLUGIN_DIR . '/flavor-chat-ia.php';
    }

    public function test_flavor_chat_ia_log_does_not_throw() {
        // La función de log no debería lanzar excepciones
        $exception = null;

        try {
            flavor_chat_ia_log('Test message', 'info', 'test');
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }

    public function test_flavor_log_debug_does_not_throw() {
        $exception = null;

        try {
            flavor_log_debug('Debug message', 'test');
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }

    public function test_flavor_log_error_does_not_throw() {
        $exception = null;

        try {
            flavor_log_error('Error message', 'test');
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }

    public function test_flavor_chat_ia_log_accepts_different_levels() {
        $levels = ['info', 'debug', 'warning', 'error'];

        foreach ($levels as $level) {
            $exception = null;

            try {
                flavor_chat_ia_log("Test $level", $level, 'test');
            } catch (Exception $e) {
                $exception = $e;
            }

            $this->assertNull($exception, "Log level '$level' should not throw");
        }
    }

    public function test_flavor_chat_ia_log_handles_empty_message() {
        $exception = null;

        try {
            flavor_chat_ia_log('', 'info', 'test');
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }

    public function test_flavor_chat_ia_log_handles_special_characters() {
        $specialChars = "Test with special chars: <script>alert('xss')</script> & \"quotes\"";

        $exception = null;

        try {
            flavor_chat_ia_log($specialChars, 'info', 'test');
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }
}
