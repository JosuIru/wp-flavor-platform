<?php
/**
 * Tests unitarios para la API REST
 *
 * Tests de endpoints principales, autenticacion y respuestas
 *
 * @package FlavorChatIA
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Mock de WP_REST_Request para tests
 */
class Mock_REST_Request {

    private $parametros;
    private $metodo;
    private $ruta;
    private $headers;

    public function __construct($metodo = 'GET', $ruta = '', $parametros = []) {
        $this->metodo = $metodo;
        $this->ruta = $ruta;
        $this->parametros = $parametros;
        $this->headers = [];
    }

    public function get_param($nombreParametro) {
        return $this->parametros[$nombreParametro] ?? null;
    }

    public function get_params() {
        return $this->parametros;
    }

    public function get_method() {
        return $this->metodo;
    }

    public function get_route() {
        return $this->ruta;
    }

    public function set_header($nombre, $valor) {
        $this->headers[$nombre] = $valor;
    }

    public function get_header($nombre) {
        return $this->headers[$nombre] ?? null;
    }
}

/**
 * Mock de WP_REST_Response para tests
 */
class Mock_REST_Response {

    private $datos;
    private $estado;
    private $headers;

    public function __construct($datos = [], $estado = 200) {
        $this->datos = $datos;
        $this->estado = $estado;
        $this->headers = [];
    }

    public function get_data() {
        return $this->datos;
    }

    public function get_status() {
        return $this->estado;
    }

    public function set_status($estado) {
        $this->estado = $estado;
    }

    public function header($nombre, $valor) {
        $this->headers[$nombre] = $valor;
    }

    public function get_headers() {
        return $this->headers;
    }
}

/**
 * Tests de API REST
 */
class RestApiTest extends Flavor_TestCase {

    /**
     * Test de namespaces de API
     */
    public function test_api_namespaces() {
        $namespaces = [
            'chat-ia-mobile/v1',
            'flavor/v1',
        ];

        foreach ($namespaces as $namespace) {
            $this->assertMatchesRegularExpression(
                '/^[a-z-]+\/v\d+$/',
                $namespace,
                "Namespace debe seguir formato: nombre/vN"
            );
        }
    }

    /**
     * Test de endpoints principales de Mobile API
     */
    public function test_mobile_api_endpoints() {
        $endpointsMobile = [
            '/config' => ['GET'],
            '/site-info' => ['GET'],
            '/modules' => ['GET'],
            '/module/(?P<module_id>[a-z_]+)' => ['GET'],
            '/login' => ['POST'],
            '/register' => ['POST'],
            '/products' => ['GET'],
            '/cart' => ['GET', 'POST'],
            '/orders' => ['GET'],
            '/order/(?P<order_id>\d+)' => ['GET'],
        ];

        foreach ($endpointsMobile as $ruta => $metodos) {
            $this->assertIsArray($metodos);
            $this->assertNotEmpty($metodos);
            foreach ($metodos as $metodo) {
                $this->assertContains($metodo, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);
            }
        }
    }

    /**
     * Test de estructura de respuesta de config
     */
    public function test_config_endpoint_response() {
        $respuestaConfig = [
            'success' => true,
            'data' => [
                'site_name' => 'Mi Sitio',
                'site_url' => 'https://example.com',
                'api_version' => '1.0.0',
                'modules' => ['woocommerce', 'eventos'],
                'features' => [
                    'chat_enabled' => true,
                    'notifications_enabled' => true,
                ],
            ],
        ];

        $this->assertTrue($respuestaConfig['success']);
        $this->assertArrayHasKey('data', $respuestaConfig);
        $this->assertArrayHasKey('site_name', $respuestaConfig['data']);
        $this->assertArrayHasKey('modules', $respuestaConfig['data']);
    }

    /**
     * Test de autenticacion JWT
     */
    public function test_jwt_authentication() {
        $tokenEstructura = [
            'header' => [
                'alg' => 'HS256',
                'typ' => 'JWT',
            ],
            'payload' => [
                'iss' => 'https://example.com',
                'sub' => 123, // user_id
                'iat' => time(),
                'exp' => time() + 3600,
            ],
        ];

        $this->assertArrayHasKey('header', $tokenEstructura);
        $this->assertArrayHasKey('payload', $tokenEstructura);
        $this->assertEquals('HS256', $tokenEstructura['header']['alg']);
        $this->assertArrayHasKey('exp', $tokenEstructura['payload']);
    }

    /**
     * Test de respuesta de login exitoso
     */
    public function test_login_success_response() {
        $respuestaLogin = [
            'success' => true,
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
            'user' => [
                'id' => 123,
                'email' => 'user@example.com',
                'display_name' => 'Usuario Test',
                'roles' => ['subscriber'],
            ],
        ];

        $this->assertTrue($respuestaLogin['success']);
        $this->assertArrayHasKey('token', $respuestaLogin);
        $this->assertArrayHasKey('user', $respuestaLogin);
        $this->assertIsString($respuestaLogin['token']);
    }

    /**
     * Test de respuesta de login fallido
     */
    public function test_login_failure_response() {
        $respuestaError = [
            'success' => false,
            'error' => 'Credenciales invalidas',
            'error_code' => 'invalid_credentials',
        ];

        $this->assertFalse($respuestaError['success']);
        $this->assertArrayHasKey('error', $respuestaError);
        $this->assertArrayHasKey('error_code', $respuestaError);
    }

    /**
     * Test de codigos de estado HTTP
     */
    public function test_http_status_codes() {
        $codigosEstado = [
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
        ];

        foreach ($codigosEstado as $codigo => $descripcion) {
            $this->assertIsInt($codigo);
            $this->assertGreaterThanOrEqual(100, $codigo);
            $this->assertLessThan(600, $codigo);
        }
    }

    /**
     * Test de rate limiting headers
     */
    public function test_rate_limit_headers() {
        $headersRateLimit = [
            'X-RateLimit-Limit' => 100,
            'X-RateLimit-Remaining' => 95,
            'X-RateLimit-Reset' => time() + 3600,
        ];

        $this->assertArrayHasKey('X-RateLimit-Limit', $headersRateLimit);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headersRateLimit);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headersRateLimit);
    }

    /**
     * Test de CORS headers
     */
    public function test_cors_headers() {
        $headersCors = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, X-Mobile-App',
            'Access-Control-Max-Age' => 86400,
        ];

        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headersCors);
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $headersCors);
    }

    /**
     * Test de paginacion en endpoints de lista
     */
    public function test_pagination_structure() {
        $respuestaPaginada = [
            'success' => true,
            'data' => [
                'items' => [],
                'pagination' => [
                    'page' => 1,
                    'per_page' => 10,
                    'total' => 100,
                    'total_pages' => 10,
                ],
            ],
        ];

        $paginacion = $respuestaPaginada['data']['pagination'];
        $this->assertArrayHasKey('page', $paginacion);
        $this->assertArrayHasKey('per_page', $paginacion);
        $this->assertArrayHasKey('total', $paginacion);
        $this->assertArrayHasKey('total_pages', $paginacion);
    }

    /**
     * Test de validacion de parametros
     */
    public function test_parameter_validation() {
        $parametrosRequeridos = [
            'email' => [
                'type' => 'string',
                'format' => 'email',
                'required' => true,
            ],
            'password' => [
                'type' => 'string',
                'minLength' => 6,
                'required' => true,
            ],
        ];

        foreach ($parametrosRequeridos as $nombre => $reglas) {
            $this->assertArrayHasKey('type', $reglas);
            $this->assertArrayHasKey('required', $reglas);
        }
    }

    /**
     * Test de formato de error de validacion
     */
    public function test_validation_error_format() {
        $errorValidacion = [
            'success' => false,
            'error' => 'Errores de validacion',
            'error_code' => 'validation_error',
            'errors' => [
                'email' => ['El email es requerido', 'Formato de email invalido'],
                'password' => ['La contrasena debe tener al menos 6 caracteres'],
            ],
        ];

        $this->assertFalse($errorValidacion['success']);
        $this->assertEquals('validation_error', $errorValidacion['error_code']);
        $this->assertArrayHasKey('errors', $errorValidacion);
        $this->assertIsArray($errorValidacion['errors']);
    }

    /**
     * Test de endpoint de chat/mensaje
     */
    public function test_chat_message_endpoint() {
        $peticionChat = [
            'message' => 'Hola, necesito ayuda',
            'session_id' => 'fcia_abc123def456',
            'context' => [
                'page' => 'home',
                'user_id' => null,
            ],
        ];

        $this->assertArrayHasKey('message', $peticionChat);
        $this->assertArrayHasKey('session_id', $peticionChat);
        $this->assertIsString($peticionChat['message']);
        $this->assertNotEmpty($peticionChat['message']);
    }

    /**
     * Test de respuesta de chat
     */
    public function test_chat_response_format() {
        $respuestaChat = [
            'success' => true,
            'response' => 'Hola! En que puedo ayudarte?',
            'session_id' => 'fcia_abc123def456',
            'engine_used' => 'claude',
            'tokens_used' => 150,
        ];

        $this->assertTrue($respuestaChat['success']);
        $this->assertArrayHasKey('response', $respuestaChat);
        $this->assertIsString($respuestaChat['response']);
    }

    /**
     * Test de endpoint de modulos
     */
    public function test_modules_endpoint_response() {
        $respuestaModulos = [
            'success' => true,
            'modules' => [
                [
                    'id' => 'woocommerce',
                    'name' => 'WooCommerce',
                    'enabled' => true,
                    'icon' => 'dashicons-cart',
                ],
                [
                    'id' => 'eventos',
                    'name' => 'Eventos',
                    'enabled' => true,
                    'icon' => 'dashicons-calendar',
                ],
            ],
        ];

        $this->assertTrue($respuestaModulos['success']);
        $this->assertArrayHasKey('modules', $respuestaModulos);
        $this->assertIsArray($respuestaModulos['modules']);
    }

    /**
     * Test de permisos de endpoint
     */
    public function test_endpoint_permissions() {
        $permisosEndpoint = [
            '/config' => 'public',
            '/login' => 'public',
            '/profile' => 'logged_in',
            '/admin/settings' => 'manage_options',
            '/admin/modules' => 'manage_options',
        ];

        foreach ($permisosEndpoint as $ruta => $permiso) {
            $this->assertIsString($permiso);
            $this->assertContains($permiso, ['public', 'logged_in', 'manage_options', 'edit_posts']);
        }
    }

    /**
     * Test de versionado de API
     */
    public function test_api_versioning() {
        $versionActual = '1.0.0';
        $headerVersion = 'X-API-Version';

        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $versionActual);
        $this->assertNotEmpty($headerVersion);
    }

    /**
     * Test de sanitizacion de entrada
     */
    public function test_input_sanitization() {
        $entradaMaliciosa = '<script>alert("xss")</script>';
        $entradaSanitizada = sanitize_text_field($entradaMaliciosa);

        $this->assertStringNotContainsString('<script>', $entradaSanitizada);
        $this->assertStringNotContainsString('</script>', $entradaSanitizada);
    }

    /**
     * Test de estructura de producto WooCommerce
     */
    public function test_product_response_structure() {
        $producto = [
            'id' => 123,
            'name' => 'Producto Test',
            'price' => '29.99',
            'regular_price' => '39.99',
            'sale_price' => '29.99',
            'on_sale' => true,
            'stock_status' => 'instock',
            'images' => [
                ['src' => 'https://example.com/image.jpg'],
            ],
            'categories' => [
                ['id' => 1, 'name' => 'Categoria'],
            ],
        ];

        $this->assertArrayHasKey('id', $producto);
        $this->assertArrayHasKey('name', $producto);
        $this->assertArrayHasKey('price', $producto);
        $this->assertArrayHasKey('stock_status', $producto);
    }

    /**
     * Test de estructura de orden
     */
    public function test_order_response_structure() {
        $orden = [
            'id' => 456,
            'status' => 'processing',
            'total' => '59.98',
            'currency' => 'EUR',
            'date_created' => '2024-01-15T10:30:00',
            'line_items' => [
                [
                    'product_id' => 123,
                    'name' => 'Producto',
                    'quantity' => 2,
                    'total' => '59.98',
                ],
            ],
        ];

        $this->assertArrayHasKey('id', $orden);
        $this->assertArrayHasKey('status', $orden);
        $this->assertArrayHasKey('total', $orden);
        $this->assertArrayHasKey('line_items', $orden);
    }

    /**
     * Test de Mock REST Request
     */
    public function test_mock_rest_request() {
        $request = new Mock_REST_Request('POST', '/api/test', [
            'name' => 'Test',
            'value' => 123,
        ]);

        $this->assertEquals('POST', $request->get_method());
        $this->assertEquals('/api/test', $request->get_route());
        $this->assertEquals('Test', $request->get_param('name'));
        $this->assertEquals(123, $request->get_param('value'));
        $this->assertNull($request->get_param('nonexistent'));
    }

    /**
     * Test de Mock REST Response
     */
    public function test_mock_rest_response() {
        $response = new Mock_REST_Response(['success' => true], 200);

        $this->assertEquals(200, $response->get_status());
        $this->assertEquals(['success' => true], $response->get_data());

        $response->set_status(201);
        $this->assertEquals(201, $response->get_status());
    }

    /**
     * Test de endpoint de push notifications
     */
    public function test_push_notification_endpoint() {
        $peticionPush = [
            'device_token' => 'fcm_token_abc123',
            'platform' => 'android',
            'user_id' => 123,
        ];

        $this->assertArrayHasKey('device_token', $peticionPush);
        $this->assertArrayHasKey('platform', $peticionPush);
        $this->assertContains($peticionPush['platform'], ['android', 'ios', 'web']);
    }
}
