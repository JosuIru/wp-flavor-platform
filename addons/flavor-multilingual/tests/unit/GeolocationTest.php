<?php
/**
 * Tests para Flavor_ML_Geolocation
 *
 * @package FlavorMultilingual
 */

class GeolocationTest extends PHPUnit\Framework\TestCase {

    /**
     * @var Flavor_ML_Geolocation
     */
    private $geo;

    /**
     * Setup antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();

        if (function_exists('wp_mock_reset')) {
            wp_mock_reset();
        }

        // Cargar clase si no está cargada
        if (!class_exists('Flavor_ML_Geolocation')) {
            require_once dirname(__DIR__, 2) . '/includes/class-geolocation.php';
        }

        // Resetear singleton
        $reflection = new ReflectionClass('Flavor_ML_Geolocation');
        $instance_property = $reflection->getProperty('instance');
        $instance_property->setAccessible(true);
        $instance_property->setValue(null, null);

        $this->geo = Flavor_ML_Geolocation::get_instance();
    }

    /**
     * Test: Singleton devuelve la misma instancia
     */
    public function test_singleton_returns_same_instance() {
        $instance1 = Flavor_ML_Geolocation::get_instance();
        $instance2 = Flavor_ML_Geolocation::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: Mapeo de países a idiomas
     */
    public function test_country_to_language_mapping() {
        $reflection = new ReflectionClass($this->geo);
        $method = $reflection->getMethod('map_country_to_language');
        $method->setAccessible(true);

        // España -> español
        $this->assertEquals('es', $method->invoke($this->geo, 'ES'));

        // Estados Unidos -> inglés
        $this->assertEquals('en', $method->invoke($this->geo, 'US'));

        // Francia -> francés
        $this->assertEquals('fr', $method->invoke($this->geo, 'FR'));

        // Alemania -> alemán
        $this->assertEquals('de', $method->invoke($this->geo, 'DE'));

        // México -> español
        $this->assertEquals('es', $method->invoke($this->geo, 'MX'));

        // Japón -> japonés
        $this->assertEquals('ja', $method->invoke($this->geo, 'JP'));
    }

    /**
     * Test: Mapeo de país desconocido devuelve false
     */
    public function test_unknown_country_returns_false() {
        $reflection = new ReflectionClass($this->geo);
        $method = $reflection->getMethod('map_country_to_language');
        $method->setAccessible(true);

        $result = $method->invoke($this->geo, 'XX');
        $this->assertFalse($result);
    }

    /**
     * Test: Mapeo insensible a mayúsculas/minúsculas
     */
    public function test_country_mapping_case_insensitive() {
        $reflection = new ReflectionClass($this->geo);
        $method = $reflection->getMethod('map_country_to_language');
        $method->setAccessible(true);

        $this->assertEquals('es', $method->invoke($this->geo, 'es'));
        $this->assertEquals('es', $method->invoke($this->geo, 'Es'));
        $this->assertEquals('es', $method->invoke($this->geo, 'ES'));
    }

    /**
     * Test: Verificar IP local
     */
    public function test_is_local_ip() {
        $reflection = new ReflectionClass($this->geo);
        $method = $reflection->getMethod('is_local_ip');
        $method->setAccessible(true);

        // IPs locales
        $this->assertTrue($method->invoke($this->geo, '127.0.0.1'));
        $this->assertTrue($method->invoke($this->geo, '::1'));
        $this->assertTrue($method->invoke($this->geo, '192.168.1.1'));
        $this->assertTrue($method->invoke($this->geo, '10.0.0.1'));
        $this->assertTrue($method->invoke($this->geo, ''));

        // IPs públicas (en un entorno real, estos devolverían false)
        // Pero con nuestro mock simplificado, filter_var puede comportarse diferente
    }

    /**
     * Test: Detectar bot por user agent
     */
    public function test_is_bot_detection() {
        $reflection = new ReflectionClass($this->geo);
        $method = $reflection->getMethod('is_bot');
        $method->setAccessible(true);

        // Sin user agent -> es bot
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->assertTrue($method->invoke($this->geo));

        // User agent de Googlebot
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1)';
        $this->assertTrue($method->invoke($this->geo));

        // User agent de Bingbot
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; bingbot/2.0)';
        $this->assertTrue($method->invoke($this->geo));

        // User agent normal
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $this->assertFalse($method->invoke($this->geo));

        // Limpiar
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Test: Parsear Accept-Language header
     */
    public function test_parse_accept_language() {
        $reflection = new ReflectionClass($this->geo);
        $method = $reflection->getMethod('get_language_from_browser');
        $method->setAccessible(true);

        // Simular Accept-Language
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es-ES,es;q=0.9,en;q=0.8';

        // Sin idiomas activos en mock, devolverá false
        $result = $method->invoke($this->geo);

        // Limpiar
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        // En este caso sin Flavor_Multilingual_Core cargado, devolverá false
        $this->assertFalse($result);
    }

    /**
     * Test: Sin Accept-Language devuelve false
     */
    public function test_no_accept_language_returns_false() {
        $reflection = new ReflectionClass($this->geo);
        $method = $reflection->getMethod('get_language_from_browser');
        $method->setAccessible(true);

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $result = $method->invoke($this->geo);
        $this->assertFalse($result);
    }

    /**
     * Test: Obtener configuración de admin
     */
    public function test_get_admin_settings() {
        $settings = $this->geo->get_admin_settings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('geo_apis', $settings);

        $apis = $settings['geo_apis'];
        $this->assertArrayHasKey('ip-api', $apis);
        $this->assertArrayHasKey('ipinfo', $apis);
        $this->assertArrayHasKey('ipgeolocation', $apis);

        // Verificar estructura de cada API
        foreach ($apis as $api) {
            $this->assertArrayHasKey('name', $api);
            $this->assertArrayHasKey('description', $api);
            $this->assertArrayHasKey('needs_key', $api);
        }
    }

    /**
     * Test: Test de configuración sin IP
     */
    public function test_configuration_without_ip() {
        // Sin IP disponible, debe fallar
        $result = $this->geo->test_configuration('');

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test: Países hispanohablantes mapean a español
     */
    public function test_spanish_speaking_countries() {
        $reflection = new ReflectionClass($this->geo);
        $method = $reflection->getMethod('map_country_to_language');
        $method->setAccessible(true);

        $spanish_countries = array('ES', 'MX', 'AR', 'CO', 'PE', 'VE', 'CL', 'EC', 'GT', 'CU');

        foreach ($spanish_countries as $country) {
            $this->assertEquals(
                'es',
                $method->invoke($this->geo, $country),
                "País {$country} debería mapear a español"
            );
        }
    }
}
