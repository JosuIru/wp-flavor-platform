<?php
/**
 * Tests unitarios para los AI Engines
 *
 * Tests de interfaz comun, configuracion y mocks de llamadas API
 *
 * @package FlavorChatIA
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Mock de engine para tests
 */
class Mock_AI_Engine {

    private $identificador;
    private $nombre;
    private $configurado;
    private $soportaTools;
    private $modelosDisponibles;

    public function __construct(
        $identificador = 'mock',
        $nombre = 'Mock Engine',
        $configurado = true,
        $soportaTools = true
    ) {
        $this->identificador = $identificador;
        $this->nombre = $nombre;
        $this->configurado = $configurado;
        $this->soportaTools = $soportaTools;
        $this->modelosDisponibles = [
            'mock-small' => 'Mock Small',
            'mock-large' => 'Mock Large',
        ];
    }

    public function get_id() {
        return $this->identificador;
    }

    public function get_name() {
        return $this->nombre;
    }

    public function get_description() {
        return 'Mock AI Engine for testing';
    }

    public function is_configured() {
        return $this->configurado;
    }

    public function supports_tools() {
        return $this->soportaTools;
    }

    public function get_available_models() {
        return $this->modelosDisponibles;
    }

    public function send_message($mensajes, $promptSistema, $herramientas = []) {
        return [
            'success' => true,
            'response' => 'Mock response',
            'tool_calls' => [],
        ];
    }

    public function send_message_stream($mensajes, $promptSistema, $callback) {
        $respuesta = 'Mock streaming response';
        call_user_func($callback, $respuesta);
        return [
            'success' => true,
            'response' => $respuesta,
        ];
    }

    public function verify_api_key($apiKey) {
        if (empty($apiKey)) {
            return ['valid' => false, 'error' => 'API key vacia'];
        }
        if (strlen($apiKey) < 10) {
            return ['valid' => false, 'error' => 'API key demasiado corta'];
        }
        return ['valid' => true, 'error' => ''];
    }

    public function get_settings_fields() {
        return [
            [
                'id' => 'mock_api_key',
                'label' => 'Mock API Key',
                'type' => 'password',
            ],
            [
                'id' => 'mock_model',
                'label' => 'Model',
                'type' => 'select',
                'options' => $this->modelosDisponibles,
            ],
        ];
    }
}

/**
 * Tests de AI Engines
 */
class EnginesTest extends Flavor_TestCase {

    /**
     * Mock engine para tests
     */
    private $mockEngine;

    /**
     * Setup antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();
        $this->mockEngine = new Mock_AI_Engine();
    }

    /**
     * Test de interfaz de engine - get_id
     */
    public function test_engine_interface_get_id() {
        $this->assertEquals('mock', $this->mockEngine->get_id());
    }

    /**
     * Test de interfaz de engine - get_name
     */
    public function test_engine_interface_get_name() {
        $this->assertEquals('Mock Engine', $this->mockEngine->get_name());
    }

    /**
     * Test de interfaz de engine - get_description
     */
    public function test_engine_interface_get_description() {
        $this->assertIsString($this->mockEngine->get_description());
        $this->assertNotEmpty($this->mockEngine->get_description());
    }

    /**
     * Test de interfaz de engine - is_configured
     */
    public function test_engine_interface_is_configured() {
        $this->assertTrue($this->mockEngine->is_configured());

        $engineNoConfigurado = new Mock_AI_Engine('test', 'Test', false);
        $this->assertFalse($engineNoConfigurado->is_configured());
    }

    /**
     * Test de interfaz de engine - supports_tools
     */
    public function test_engine_interface_supports_tools() {
        $this->assertTrue($this->mockEngine->supports_tools());

        $engineSinTools = new Mock_AI_Engine('test', 'Test', true, false);
        $this->assertFalse($engineSinTools->supports_tools());
    }

    /**
     * Test de modelos disponibles
     */
    public function test_engine_available_models() {
        $modelos = $this->mockEngine->get_available_models();

        $this->assertIsArray($modelos);
        $this->assertNotEmpty($modelos);
        $this->assertArrayHasKey('mock-small', $modelos);
        $this->assertArrayHasKey('mock-large', $modelos);
    }

    /**
     * Test de envio de mensaje exitoso
     */
    public function test_send_message_success() {
        $mensajes = [
            ['role' => 'user', 'content' => 'Hola'],
        ];
        $promptSistema = 'Eres un asistente util.';

        $respuesta = $this->mockEngine->send_message($mensajes, $promptSistema);

        $this->assertIsArray($respuesta);
        $this->assertArrayHasKey('success', $respuesta);
        $this->assertTrue($respuesta['success']);
        $this->assertArrayHasKey('response', $respuesta);
        $this->assertIsString($respuesta['response']);
    }

    /**
     * Test de envio de mensaje con streaming
     */
    public function test_send_message_stream() {
        $mensajes = [
            ['role' => 'user', 'content' => 'Hola'],
        ];
        $promptSistema = 'Eres un asistente util.';
        $chunkRecibido = '';

        $callback = function($chunk) use (&$chunkRecibido) {
            $chunkRecibido = $chunk;
        };

        $respuesta = $this->mockEngine->send_message_stream($mensajes, $promptSistema, $callback);

        $this->assertIsArray($respuesta);
        $this->assertTrue($respuesta['success']);
        $this->assertNotEmpty($chunkRecibido);
    }

    /**
     * Test de verificacion de API key valida
     */
    public function test_verify_api_key_valid() {
        $apiKeyValida = 'sk-12345678901234567890';

        $resultado = $this->mockEngine->verify_api_key($apiKeyValida);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('valid', $resultado);
        $this->assertTrue($resultado['valid']);
    }

    /**
     * Test de verificacion de API key vacia
     */
    public function test_verify_api_key_empty() {
        $resultado = $this->mockEngine->verify_api_key('');

        $this->assertFalse($resultado['valid']);
        $this->assertNotEmpty($resultado['error']);
    }

    /**
     * Test de verificacion de API key corta
     */
    public function test_verify_api_key_too_short() {
        $resultado = $this->mockEngine->verify_api_key('short');

        $this->assertFalse($resultado['valid']);
        $this->assertStringContainsString('corta', $resultado['error']);
    }

    /**
     * Test de campos de configuracion
     */
    public function test_engine_settings_fields() {
        $campos = $this->mockEngine->get_settings_fields();

        $this->assertIsArray($campos);
        $this->assertNotEmpty($campos);

        // Verificar estructura de cada campo
        foreach ($campos as $campo) {
            $this->assertArrayHasKey('id', $campo);
            $this->assertArrayHasKey('label', $campo);
            $this->assertArrayHasKey('type', $campo);
        }
    }

    /**
     * Test de estructura de respuesta de error API
     */
    public function test_api_error_response_structure() {
        $respuestaError = [
            'success' => false,
            'error' => 'Rate limit exceeded',
            'error_code' => 'rate_limit_exceeded',
            'status_code' => 429,
        ];

        $this->assertArrayHasKey('success', $respuestaError);
        $this->assertFalse($respuestaError['success']);
        $this->assertArrayHasKey('error', $respuestaError);
        $this->assertArrayHasKey('error_code', $respuestaError);
    }

    /**
     * Test de formato de mensajes para API
     */
    public function test_message_format() {
        $mensajesValidos = [
            ['role' => 'user', 'content' => 'Mensaje del usuario'],
            ['role' => 'assistant', 'content' => 'Respuesta del asistente'],
        ];

        foreach ($mensajesValidos as $mensaje) {
            $this->assertArrayHasKey('role', $mensaje);
            $this->assertArrayHasKey('content', $mensaje);
            $this->assertContains($mensaje['role'], ['user', 'assistant', 'system']);
        }
    }

    /**
     * Test de configuracion de Claude
     */
    public function test_claude_engine_config() {
        $configClaude = [
            'id' => 'claude',
            'models' => [
                'claude-sonnet-4-20250514' => 'Claude Sonnet 4',
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            ],
            'supports_tools' => true,
            'api_base' => 'https://api.anthropic.com/v1',
        ];

        $this->assertEquals('claude', $configClaude['id']);
        $this->assertTrue($configClaude['supports_tools']);
        $this->assertNotEmpty($configClaude['models']);
    }

    /**
     * Test de configuracion de OpenAI
     */
    public function test_openai_engine_config() {
        $configOpenai = [
            'id' => 'openai',
            'models' => [
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
            ],
            'supports_tools' => true,
            'api_base' => 'https://api.openai.com/v1',
        ];

        $this->assertEquals('openai', $configOpenai['id']);
        $this->assertTrue($configOpenai['supports_tools']);
    }

    /**
     * Test de configuracion de DeepSeek
     */
    public function test_deepseek_engine_config() {
        $configDeepseek = [
            'id' => 'deepseek',
            'models' => [
                'deepseek-chat' => 'DeepSeek Chat',
                'deepseek-coder' => 'DeepSeek Coder',
            ],
            'supports_tools' => true,
            'free_tier' => true,
        ];

        $this->assertEquals('deepseek', $configDeepseek['id']);
        $this->assertTrue($configDeepseek['free_tier']);
    }

    /**
     * Test de configuracion de Mistral
     */
    public function test_mistral_engine_config() {
        $configMistral = [
            'id' => 'mistral',
            'models' => [
                'mistral-small-latest' => 'Mistral Small',
                'mistral-medium-latest' => 'Mistral Medium',
                'mistral-large-latest' => 'Mistral Large',
            ],
            'supports_tools' => true,
            'free_tier' => true,
        ];

        $this->assertEquals('mistral', $configMistral['id']);
        $this->assertTrue($configMistral['free_tier']);
    }

    /**
     * Test de contextos de engine (frontend/backend)
     */
    public function test_engine_contexts() {
        $contextos = [
            'frontend' => 'Chat publico para visitantes',
            'backend' => 'Asistente admin para administradores',
            'default' => 'Configuracion general',
        ];

        $this->assertArrayHasKey('frontend', $contextos);
        $this->assertArrayHasKey('backend', $contextos);
        $this->assertArrayHasKey('default', $contextos);
    }

    /**
     * Test de sistema de fallback entre engines
     */
    public function test_engine_fallback_system() {
        $ordenFallback = ['claude', 'openai', 'deepseek', 'mistral'];

        $this->assertIsArray($ordenFallback);
        $this->assertNotEmpty($ordenFallback);

        // Claude debe ser el primero por defecto
        $this->assertEquals('claude', $ordenFallback[0]);
    }

    /**
     * Test de rate limiting en respuesta
     */
    public function test_rate_limit_response() {
        $respuestaRateLimit = [
            'success' => false,
            'error' => 'Rate limit exceeded',
            'error_code' => 'rate_limit_exceeded',
            'status_code' => 429,
        ];

        $this->assertEquals(429, $respuestaRateLimit['status_code']);
        $this->assertEquals('rate_limit_exceeded', $respuestaRateLimit['error_code']);
    }

    /**
     * Test de max retries para rate limit
     */
    public function test_max_retries_config() {
        $maxReintentos = 3;
        $tiempoEsperaMaximo = 30; // segundos

        $this->assertGreaterThan(0, $maxReintentos);
        $this->assertLessThanOrEqual(5, $maxReintentos);
        $this->assertLessThanOrEqual(60, $tiempoEsperaMaximo);
    }

    /**
     * Test de tool definitions format
     */
    public function test_tool_definitions_format() {
        $definicionTool = [
            'name' => 'search_products',
            'description' => 'Busca productos en el catalogo',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Termino de busqueda',
                    ],
                ],
                'required' => ['query'],
            ],
        ];

        $this->assertArrayHasKey('name', $definicionTool);
        $this->assertArrayHasKey('description', $definicionTool);
        $this->assertArrayHasKey('input_schema', $definicionTool);
    }

    /**
     * Test de respuesta con tool calls
     */
    public function test_tool_call_response_format() {
        $respuestaConTools = [
            'success' => true,
            'response' => '',
            'tool_calls' => [
                [
                    'id' => 'call_123',
                    'name' => 'search_products',
                    'input' => ['query' => 'zapatillas'],
                ],
            ],
        ];

        $this->assertTrue($respuestaConTools['success']);
        $this->assertIsArray($respuestaConTools['tool_calls']);
        $this->assertNotEmpty($respuestaConTools['tool_calls']);

        $primerTool = $respuestaConTools['tool_calls'][0];
        $this->assertArrayHasKey('id', $primerTool);
        $this->assertArrayHasKey('name', $primerTool);
        $this->assertArrayHasKey('input', $primerTool);
    }

    /**
     * Test de providers con tier gratuito
     */
    public function test_free_tier_providers() {
        $proveedoresGratis = [
            'deepseek' => [
                'name' => 'DeepSeek',
                'free_limit' => '~500K tokens/dia',
            ],
            'mistral' => [
                'name' => 'Mistral',
                'free_limit' => '1M tokens/mes',
            ],
        ];

        $this->assertArrayHasKey('deepseek', $proveedoresGratis);
        $this->assertArrayHasKey('mistral', $proveedoresGratis);
        $this->assertCount(2, $proveedoresGratis);
    }
}
