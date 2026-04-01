<?php
/**
 * Tests para Flavor_Multilingual_Core
 *
 * @package FlavorMultilingual
 * @subpackage Tests
 */

class MultilingualCoreTest extends WP_Mock_TestCase {

    private $core;

    public function setUp(): void {
        parent::setUp();
        WP_Mock::setUp();

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';

        WP_Mock::userFunction('get_option', [
            'return' => [
                'default_language' => 'es',
                'url_mode' => 'parameter'
            ]
        ]);

        $this->core = Flavor_Multilingual_Core::get_instance();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test: Singleton
     */
    public function test_singleton_instance() {
        $instance1 = Flavor_Multilingual_Core::get_instance();
        $instance2 = Flavor_Multilingual_Core::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: Idioma por defecto
     */
    public function test_default_language() {
        $default = $this->core->get_default_language();

        $this->assertIsString($default);
        $this->assertEquals('es', $default);
    }

    /**
     * Test: Detección de idioma desde parámetro GET
     */
    public function test_detect_language_from_get() {
        $_GET['lang'] = 'eu';

        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('detect_language_from_request');
        $method->setAccessible(true);

        $lang = $method->invoke($this->core);

        $this->assertEquals('eu', $lang);

        unset($_GET['lang']);
    }

    /**
     * Test: Validación de código de idioma
     */
    public function test_is_valid_language() {
        global $wpdb;

        $wpdb->shouldReceive('prepare')
            ->andReturn("SELECT COUNT(*) FROM wp_flavor_languages WHERE code = 'es' AND is_active = 1");

        $wpdb->shouldReceive('get_var')
            ->andReturn(1);

        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('is_valid_language');
        $method->setAccessible(true);

        $valid = $method->invoke($this->core, 'es');

        $this->assertTrue($valid);
    }

    /**
     * Test: Obtener URL traducida
     */
    public function test_get_translated_url() {
        WP_Mock::userFunction('home_url', ['return' => 'https://example.com']);
        WP_Mock::userFunction('add_query_arg', ['return' => 'https://example.com?lang=eu']);

        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('get_translated_url');
        $method->setAccessible(true);

        $url = $method->invoke($this->core, 'https://example.com/page', 'eu');

        $this->assertStringContainsString('lang=eu', $url);
    }

    /**
     * Test: Es idioma RTL
     */
    public function test_is_rtl_language() {
        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('is_rtl');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->core, 'ar'));
        $this->assertTrue($method->invoke($this->core, 'he'));
        $this->assertFalse($method->invoke($this->core, 'es'));
        $this->assertFalse($method->invoke($this->core, 'en'));
    }

    /**
     * Test: Obtener idiomas activos
     */
    public function test_get_active_languages() {
        global $wpdb;

        $wpdb->shouldReceive('get_results')
            ->andReturn([
                (object)['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'is_default' => 1],
                (object)['code' => 'eu', 'name' => 'Basque', 'native_name' => 'Euskara', 'is_default' => 0],
                (object)['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => 0],
            ]);

        WP_Mock::userFunction('wp_cache_get', ['return' => false]);
        WP_Mock::userFunction('wp_cache_set', ['return' => true]);

        $languages = $this->core->get_active_languages();

        $this->assertIsArray($languages);
        $this->assertArrayHasKey('es', $languages);
        $this->assertArrayHasKey('eu', $languages);
    }

    /**
     * Test: Cambio de idioma
     */
    public function test_switch_language() {
        WP_Mock::userFunction('setcookie', ['return' => true]);

        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('set_current_language');
        $method->setAccessible(true);

        $result = $method->invoke($this->core, 'eu');

        $this->assertTrue($result);
    }

    /**
     * Test: Filtro de locale
     */
    public function test_filter_locale() {
        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('filter_locale');
        $method->setAccessible(true);

        // Simular que el idioma actual es euskera
        $property = $reflection->getProperty('current_language');
        $property->setAccessible(true);
        $property->setValue($this->core, 'eu');

        $locale = $method->invoke($this->core, 'es_ES');

        $this->assertEquals('eu', $locale);
    }

    /**
     * Test: Hreflang tags
     */
    public function test_generate_hreflang_tags() {
        global $wpdb;

        $wpdb->shouldReceive('get_results')
            ->andReturn([
                (object)['code' => 'es', 'locale' => 'es_ES'],
                (object)['code' => 'eu', 'locale' => 'eu'],
            ]);

        WP_Mock::userFunction('home_url', ['return' => 'https://example.com']);

        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('generate_hreflang_tags');
        $method->setAccessible(true);

        $tags = $method->invoke($this->core);

        $this->assertIsArray($tags);
    }

    /**
     * Test: Modo de URL
     */
    public function test_url_modes() {
        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('get_url_mode');
        $method->setAccessible(true);

        $mode = $method->invoke($this->core);

        $this->assertContains($mode, ['parameter', 'directory', 'subdomain']);
    }

    /**
     * Test: Persistencia de preferencia
     */
    public function test_remember_language_preference() {
        WP_Mock::userFunction('setcookie', ['return' => true]);

        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('remember_preference');
        $method->setAccessible(true);

        $result = $method->invoke($this->core, 'eu');

        $this->assertTrue($result);
    }

    /**
     * Test: Detección de navegador
     */
    public function test_detect_browser_language() {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'eu,es;q=0.9,en;q=0.8';

        $reflection = new ReflectionClass($this->core);
        $method = $reflection->getMethod('detect_browser_language');
        $method->setAccessible(true);

        $lang = $method->invoke($this->core);

        $this->assertEquals('eu', $lang);

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }
}
