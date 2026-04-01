<?php
/**
 * Tests para Flavor_Module_Compatibility_API
 *
 * @package FlavorPlatform
 * @subpackage Tests
 */

class ModuleCompatibilityAPITest extends WP_Mock_TestCase {

    private $api;

    public function setUp(): void {
        parent::setUp();
        WP_Mock::setUp();

        // Mock de rest_get_server
        WP_Mock::userFunction('rest_get_server', [
            'return' => Mockery::mock('WP_REST_Server')
        ]);

        WP_Mock::userFunction('register_rest_route', ['return' => true]);
        WP_Mock::userFunction('rest_ensure_response', ['return_arg' => 0]);
        WP_Mock::userFunction('current_time', ['return' => '2026-04-01 12:00:00']);
        WP_Mock::userFunction('get_option', ['return' => []]);
        WP_Mock::userFunction('get_site_url', ['return' => 'https://example.com']);
        WP_Mock::userFunction('get_bloginfo', ['return' => '6.4']);
        WP_Mock::userFunction('is_multisite', ['return' => false]);

        $this->api = Flavor_Module_Compatibility_API::get_instance();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test: Singleton
     */
    public function test_singleton_instance() {
        $instance1 = Flavor_Module_Compatibility_API::get_instance();
        $instance2 = Flavor_Module_Compatibility_API::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: API key válida
     */
    public function test_check_api_key_valid() {
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('X-VBP-Key')
            ->andReturn('flavor-vbp-2024');
        $request->shouldReceive('get_param')
            ->with('api_key')
            ->andReturn(null);
        $request->shouldReceive('get_route')
            ->andReturn('/flavor-platform/v1/modules/compatibility');

        $result = $this->api->check_api_key($request);

        $this->assertTrue($result);
    }

    /**
     * Test: API key inválida
     */
    public function test_check_api_key_invalid() {
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('X-VBP-Key')
            ->andReturn('wrong-key');
        $request->shouldReceive('get_param')
            ->with('api_key')
            ->andReturn(null);
        $request->shouldReceive('get_route')
            ->andReturn('/flavor-platform/v1/modules/compatibility');

        $result = $this->api->check_api_key($request);

        $this->assertFalse($result);
    }

    /**
     * Test: Rutas públicas sin API key
     */
    public function test_public_routes_no_api_key() {
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('X-VBP-Key')
            ->andReturn(null);
        $request->shouldReceive('get_param')
            ->with('api_key')
            ->andReturn(null);
        $request->shouldReceive('get_route')
            ->andReturn('/flavor-platform/v1/modules/supported');

        $result = $this->api->check_api_key($request);

        $this->assertTrue($result);
    }

    /**
     * Test: Matriz de compatibilidad estructura
     */
    public function test_compatibility_matrix_structure() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->api->get_compatibility_matrix($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('matrix', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('generated_at', $result);
    }

    /**
     * Test: Resumen tiene contadores correctos
     */
    public function test_summary_has_counters() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->api->get_compatibility_matrix($request);

        $this->assertArrayHasKey('full_support', $result['summary']);
        $this->assertArrayHasKey('partial_support', $result['summary']);
        $this->assertArrayHasKey('no_support', $result['summary']);
    }

    /**
     * Test: Módulo conocido tiene estructura correcta
     */
    public function test_module_entry_structure() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->api->get_compatibility_matrix($request);

        $this->assertArrayHasKey('eventos', $result['matrix']);

        $module = $result['matrix']['eventos'];
        $this->assertArrayHasKey('id', $module);
        $this->assertArrayHasKey('category', $module);
        $this->assertArrayHasKey('wordpress', $module);
        $this->assertArrayHasKey('flutter', $module);
        $this->assertArrayHasKey('api', $module);
        $this->assertArrayHasKey('support_level', $module);
        $this->assertArrayHasKey('recommendation', $module);
    }

    /**
     * Test: Verificar módulo específico - existe
     */
    public function test_check_module_exists() {
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn('eventos');

        $result = $this->api->check_module($request);

        $this->assertIsArray($result);
        $this->assertEquals('eventos', $result['id']);
        $this->assertArrayHasKey('levels', $result);
        $this->assertArrayHasKey('can_enable', $result);
    }

    /**
     * Test: Verificar módulo específico - no existe
     */
    public function test_check_module_not_found() {
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('id')
            ->andReturn('modulo-inexistente');

        WP_Mock::userFunction('WP_Error', [
            'return' => new stdClass()
        ]);

        $result = $this->api->check_module($request);

        $this->assertInstanceOf('WP_Error', $result);
    }

    /**
     * Test: Obtener módulos soportados
     */
    public function test_get_supported_modules() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->api->get_supported_modules($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('full_support', $result);
        $this->assertArrayHasKey('partial_support', $result);
        $this->assertArrayHasKey('enable_automatically', $result);
        $this->assertArrayHasKey('ask_permission', $result);
    }

    /**
     * Test: Diagnóstico tiene estructura completa
     */
    public function test_diagnostics_structure() {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('get_results')->andReturn([]);

        $request = Mockery::mock('WP_REST_Request');

        $result = $this->api->get_diagnostics($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('platform', $result);
        $this->assertArrayHasKey('modules', $result);
        $this->assertArrayHasKey('apis', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    /**
     * Test: Recomendación para soporte completo
     */
    public function test_recommendation_full_support() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->api->get_compatibility_matrix($request);

        // Buscar un módulo con algún nivel de soporte
        foreach ($result['matrix'] as $module) {
            if ($module['support_level'] === 3) {
                $this->assertEquals('enable', $module['recommendation']['action']);
                $this->assertFalse($module['recommendation']['ask_permission']);
                break;
            }
        }
    }

    /**
     * Test: Categorías de módulos
     */
    public function test_module_categories() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->api->get_compatibility_matrix($request);

        $categories = [];
        foreach ($result['matrix'] as $module) {
            $categories[$module['category']] = true;
        }

        $this->assertArrayHasKey('community', $categories);
        $this->assertArrayHasKey('commerce', $categories);
        $this->assertArrayHasKey('education', $categories);
    }

    /**
     * Test: Flutter folder mapping
     */
    public function test_flutter_folder_mapping() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->api->get_compatibility_matrix($request);

        // grupos-consumo debe mapear a grupos_consumo
        $this->assertEquals('grupos_consumo', $result['matrix']['grupos-consumo']['flutter_folder']);

        // eventos debe mapear a eventos
        $this->assertEquals('eventos', $result['matrix']['eventos']['flutter_folder']);
    }

    /**
     * Test: Support text format
     */
    public function test_support_text_format() {
        $request = Mockery::mock('WP_REST_Request');

        $result = $this->api->get_compatibility_matrix($request);

        foreach ($result['matrix'] as $module) {
            $this->assertMatchesRegularExpression('/^\d\/3$/', $module['support_text']);
        }
    }
}
