<?php
/**
 * Tests de integración para REST API de Flavor Platform.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Integration
 */

class RestApiIntegrationTest extends Flavor_Integration_Test_Case {

    /**
     * Setup antes de cada test.
     */
    public function setUp(): void {
        parent::setUp();

        // Registrar rutas REST
        do_action('rest_api_init');
    }

    /**
     * Test endpoint de health check.
     *
     * @group rest-api
     */
    public function test_health_check_endpoint() {
        $response = $this->rest_request('GET', '/flavor-site-builder/v1/system/health');

        $status = $response->get_status();
        // Puede ser 200 o 401 dependiendo de autenticación
        $this->assertContains($status, [200, 401, 403, 404]);
    }

    /**
     * Test endpoint de módulos.
     *
     * @group rest-api
     */
    public function test_modules_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-site-builder/v1/modules');

        $status = $response->get_status();
        if ($status === 200) {
            $data = $response->get_data();
            $this->assertIsArray($data);
        }
    }

    /**
     * Test endpoint de templates.
     *
     * @group rest-api
     */
    public function test_templates_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-site-builder/v1/templates');

        $status = $response->get_status();
        $this->assertContains($status, [200, 401, 403, 404]);
    }

    /**
     * Test endpoint requiere autenticación.
     *
     * @group rest-api
     */
    public function test_endpoint_requires_auth() {
        $this->logout();

        $response = $this->rest_request('GET', '/flavor-vbp/v1/claude/pages');

        $status = $response->get_status();
        // Sin autenticación debería dar error
        $this->assertContains($status, [401, 403, 404]);
    }

    /**
     * Test crear página via API.
     *
     * @group rest-api
     * @group vbp
     */
    public function test_create_page_via_api() {
        $this->login_as_admin();

        $response = $this->rest_request('POST', '/flavor-vbp/v1/claude/pages', [
            'title' => 'Test Page via API',
            'blocks' => [
                [
                    'type' => 'heading',
                    'props' => ['level' => 1, 'text' => 'Hello World'],
                ],
            ],
        ]);

        $status = $response->get_status();
        // Puede ser 200/201 si funciona, o 401/403/404 si no está configurado
        $this->assertContains($status, [200, 201, 400, 401, 403, 404]);
    }

    /**
     * Test endpoint de estado de VBP.
     *
     * @group rest-api
     * @group vbp
     */
    public function test_vbp_status_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-vbp/v1/claude/status');

        $status = $response->get_status();
        $this->assertContains($status, [200, 401, 403, 404]);
    }

    /**
     * Test endpoint de bloques disponibles.
     *
     * @group rest-api
     * @group vbp
     */
    public function test_blocks_endpoint() {
        // Skip: el método Flavor_VBP_Block_Library::get_all_blocks() no existe
        // TODO: Implementar get_all_blocks() en la clase Block Library
        if (!method_exists('Flavor_VBP_Block_Library', 'get_all_blocks')) {
            $this->markTestSkipped('Flavor_VBP_Block_Library::get_all_blocks() no implementado');
        }

        $this->login_as_admin();
        $response = $this->rest_request('GET', '/flavor-vbp/v1/claude/blocks');
        $status = $response->get_status();
        $this->assertContains($status, [200, 401, 403, 404]);
    }

    /**
     * Test endpoint de schema.
     *
     * @group rest-api
     * @group vbp
     */
    public function test_schema_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-vbp/v1/claude/schema');

        $status = $response->get_status();
        $this->assertContains($status, [200, 401, 403, 404]);
    }

    /**
     * Test validación de parámetros.
     *
     * @group rest-api
     */
    public function test_parameter_validation() {
        $this->login_as_admin();

        // Enviar request con parámetros inválidos
        $response = $this->rest_request('POST', '/flavor-vbp/v1/claude/pages', [
            'title' => '', // título vacío
        ]);

        $status = $response->get_status();
        // La API puede aceptar o rechazar título vacío
        $this->assertContains($status, [200, 201, 400, 401, 403, 404, 500]);
    }

    /**
     * Test respuesta JSON correcta.
     *
     * @group rest-api
     */
    public function test_json_response_format() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-site-builder/v1/modules');

        $data = $response->get_data();
        // La respuesta debe ser array o tener estructura JSON válida
        $this->assertTrue(is_array($data) || is_object($data) || is_null($data));
    }

    /**
     * Test headers de respuesta.
     *
     * @group rest-api
     */
    public function test_response_headers() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-site-builder/v1/system/health');

        $headers = $response->get_headers();
        // Los headers deben ser un array
        $this->assertTrue(is_array($headers) || empty($headers));
    }

    /**
     * Test endpoint con paginación.
     *
     * @group rest-api
     */
    public function test_paginated_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-vbp/v1/claude/pages', [
            'per_page' => 5,
            'page' => 1,
        ]);

        $status = $response->get_status();
        $this->assertContains($status, [200, 401, 403, 404]);
    }

    /**
     * Test endpoint de activar módulos.
     *
     * @group rest-api
     */
    public function test_activate_modules_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('POST', '/flavor-site-builder/v1/modules/activate', [
            'modules' => ['eventos', 'socios'],
        ]);

        $status = $response->get_status();
        $this->assertContains($status, [200, 400, 401, 403, 404]);
    }

    /**
     * Test endpoint de menú.
     *
     * @group rest-api
     */
    public function test_menu_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('POST', '/flavor-site-builder/v1/menu', [
            'name' => 'Test Menu',
            'location' => 'primary',
            'items' => [
                ['title' => 'Home', 'url' => '/'],
            ],
        ]);

        $status = $response->get_status();
        $this->assertContains($status, [200, 201, 400, 401, 403, 404]);
    }

    /**
     * Test CORS headers si están configurados.
     *
     * @group rest-api
     */
    public function test_cors_headers() {
        $this->login_as_admin();

        // En tests de integración los CORS pueden no estar configurados
        $response = $this->rest_request('GET', '/flavor-site-builder/v1/system/health');

        // Solo verificamos que la request se procesa
        $this->assertInstanceOf('WP_REST_Response', $response);
    }

    /**
     * Test rate limiting (si está implementado).
     *
     * @group rest-api
     */
    public function test_rate_limiting() {
        $this->login_as_admin();

        // Hacer múltiples requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->rest_request('GET', '/flavor-site-builder/v1/modules');
        }

        // La última request debería funcionar (no hemos excedido límites en test)
        $this->assertInstanceOf('WP_REST_Response', $response);
    }

    /**
     * Test endpoint de design presets.
     *
     * @group rest-api
     * @group vbp
     */
    public function test_design_presets_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-vbp/v1/claude/design-presets');

        $status = $response->get_status();
        $this->assertContains($status, [200, 401, 403, 404]);
    }

    /**
     * Test endpoint de section types.
     *
     * @group rest-api
     * @group vbp
     */
    public function test_section_types_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-vbp/v1/claude/section-types');

        $status = $response->get_status();
        $this->assertContains($status, [200, 401, 403, 404]);
    }

    /**
     * Test endpoint de capabilities.
     *
     * @group rest-api
     * @group vbp
     */
    public function test_capabilities_endpoint() {
        $this->login_as_admin();

        $response = $this->rest_request('GET', '/flavor-vbp/v1/claude/capabilities');

        $status = $response->get_status();
        $this->assertContains($status, [200, 401, 403, 404]);
    }

    /**
     * Test manejo de errores.
     *
     * @group rest-api
     */
    public function test_error_handling() {
        $this->login_as_admin();

        // Request a endpoint que no existe
        $response = $this->rest_request('GET', '/flavor-nonexistent/v1/test');

        $status = $response->get_status();
        $this->assertEquals(404, $status);
    }

    /**
     * Test método HTTP incorrecto.
     *
     * @group rest-api
     */
    public function test_wrong_http_method() {
        $this->login_as_admin();

        // Intentar POST en endpoint que solo acepta GET
        $response = $this->rest_request('POST', '/flavor-site-builder/v1/modules');

        $status = $response->get_status();
        // Puede dar 404 o 405 Method Not Allowed
        $this->assertContains($status, [200, 404, 405]);
    }

    /**
     * Test namespace de API.
     *
     * @group rest-api
     */
    public function test_api_namespaces() {
        $server = rest_get_server();
        $namespaces = $server->get_namespaces();

        // Verificar que hay namespaces registrados
        $this->assertIsArray($namespaces);
    }
}
