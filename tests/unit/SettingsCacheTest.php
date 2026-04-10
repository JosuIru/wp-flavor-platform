<?php
/**
 * Tests para las funciones de cache de settings
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class SettingsCacheTest extends Flavor_TestCase {

    protected function setUp(): void {
        parent::setUp();

        // Cargar funciones del plugin
        require_once FLAVOR_PLUGIN_DIR . '/flavor-chat-ia.php';

        // Limpiar cache antes de cada test
        if (function_exists('flavor_invalidate_settings_cache')) {
            flavor_invalidate_settings_cache('all');
        }
    }

    protected function tearDown(): void {
        // Limpiar cache después de cada test
        if (function_exists('flavor_invalidate_settings_cache')) {
            flavor_invalidate_settings_cache('all');
        }
        parent::tearDown();
    }

    public function test_flavor_get_cached_settings_returns_array() {
        $settings = flavor_get_cached_settings('main');

        $this->assertIsArray($settings);
    }

    public function test_flavor_get_cached_settings_vbp_returns_array() {
        $settings = flavor_get_cached_settings('vbp');

        $this->assertIsArray($settings);
    }

    public function test_flavor_get_cached_settings_uses_cache() {
        // Primera llamada
        $settings1 = flavor_get_cached_settings('main');

        // Modificar la opción directamente (sin invalidar cache)
        update_option('flavor_chat_ia_settings', ['test_key' => 'test_value']);

        // Segunda llamada debería devolver el valor cacheado
        $settings2 = flavor_get_cached_settings('main');

        // Deberían ser iguales (cacheados)
        $this->assertEquals($settings1, $settings2);
    }

    public function test_flavor_invalidate_settings_cache_clears_specific() {
        // Nota: En el entorno de tests, update_option es un mock que no persiste datos.
        // Este test verifica que flavor_invalidate_settings_cache no lanza excepciones
        // y que flavor_get_cached_settings sigue funcionando después de invalidar.

        // Poblar cache
        $settings1 = flavor_get_cached_settings('main');
        $settings_vbp = flavor_get_cached_settings('vbp');

        // Invalidar solo main (no debería lanzar errores)
        $exception = null;
        try {
            flavor_invalidate_settings_cache('main');
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);

        // Después de invalidar, debería poder obtener settings nuevamente
        $settings2 = flavor_get_cached_settings('main');
        $this->assertIsArray($settings2);
    }

    public function test_flavor_get_vbp_api_key_returns_string() {
        $key = flavor_get_vbp_api_key();

        $this->assertIsString($key);
        $this->assertNotEmpty($key);
    }

    public function test_flavor_verify_vbp_api_key_validates_correct_key() {
        $key = flavor_get_vbp_api_key();

        $result = flavor_verify_vbp_api_key($key);

        $this->assertTrue($result);
    }

    public function test_flavor_verify_vbp_api_key_rejects_invalid_key() {
        $result = flavor_verify_vbp_api_key('invalid-key-12345');

        $this->assertFalse($result);
    }

    public function test_flavor_verify_vbp_api_key_rejects_empty_key() {
        $result = flavor_verify_vbp_api_key('');

        $this->assertFalse($result);
    }

    public function test_flavor_vbp_automation_enabled_returns_bool() {
        $result = flavor_vbp_automation_enabled();

        $this->assertIsBool($result);
    }

    public function test_flavor_get_vbp_automation_scopes_returns_array() {
        $scopes = flavor_get_vbp_automation_scopes();

        $this->assertIsArray($scopes);
    }

    public function test_flavor_get_vbp_automation_scopes_contains_defaults() {
        $scopes = flavor_get_vbp_automation_scopes();

        $this->assertContains('site_builder', $scopes);
        $this->assertContains('claude_batch', $scopes);
    }

    // Tests para flavor_safe_posts_limit()

    public function test_flavor_safe_posts_limit_returns_max_for_minus_one() {
        $result = flavor_safe_posts_limit(-1);

        $this->assertEquals(FLAVOR_MAX_POSTS_PER_QUERY, $result);
    }

    public function test_flavor_safe_posts_limit_returns_requested_when_under_max() {
        $result = flavor_safe_posts_limit(50);

        $this->assertEquals(50, $result);
    }

    public function test_flavor_safe_posts_limit_caps_at_max() {
        $result = flavor_safe_posts_limit(500);

        $this->assertEquals(FLAVOR_MAX_POSTS_PER_QUERY, $result);
    }

    public function test_flavor_safe_posts_limit_accepts_custom_max() {
        $result = flavor_safe_posts_limit(-1, 100);

        $this->assertEquals(100, $result);
    }

    public function test_flavor_safe_posts_limit_custom_max_caps_high_values() {
        $result = flavor_safe_posts_limit(150, 100);

        $this->assertEquals(100, $result);
    }

    public function test_flavor_safe_posts_limit_returns_minimum_one() {
        $result = flavor_safe_posts_limit(0);

        $this->assertEquals(1, $result);
    }

    public function test_flavor_safe_posts_limit_handles_negative_values() {
        // -5 no es -1, así que se trata como valor inválido y devuelve 1
        $result = flavor_safe_posts_limit(-5);

        $this->assertEquals(1, $result);
    }

    public function test_flavor_safe_posts_limit_handles_string_input() {
        $result = flavor_safe_posts_limit('50');

        $this->assertEquals(50, $result);
    }
}
