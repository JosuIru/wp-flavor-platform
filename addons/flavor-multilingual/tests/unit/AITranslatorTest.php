<?php
/**
 * Tests para Flavor_AI_Translator
 *
 * @package FlavorMultilingual
 * @subpackage Tests
 */

class AITranslatorTest extends WP_Mock_TestCase {

    private $translator;

    public function setUp(): void {
        parent::setUp();
        WP_Mock::setUp();

        // Mock de las opciones
        WP_Mock::userFunction('get_option', [
            'args' => ['flavor_multilingual_settings', []],
            'return' => [
                'ai_engine' => 'claude',
                'claude_api_key' => 'test-key',
                'openai_api_key' => 'test-openai-key',
            ]
        ]);

        WP_Mock::userFunction('get_transient', [
            'return' => false
        ]);

        WP_Mock::userFunction('set_transient', [
            'return' => true
        ]);

        $this->translator = Flavor_AI_Translator::get_instance();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test: Verificar instancia singleton
     */
    public function test_singleton_instance() {
        $instance1 = Flavor_AI_Translator::get_instance();
        $instance2 = Flavor_AI_Translator::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: Validación de idiomas soportados
     */
    public function test_supported_languages() {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('is_language_supported');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->translator, 'es'));
        $this->assertTrue($method->invoke($this->translator, 'en'));
        $this->assertTrue($method->invoke($this->translator, 'eu'));
        $this->assertTrue($method->invoke($this->translator, 'ca'));
        $this->assertTrue($method->invoke($this->translator, 'gl'));
    }

    /**
     * Test: Preparación de texto para traducción
     */
    public function test_prepare_text_for_translation() {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('prepare_text');
        $method->setAccessible(true);

        // Texto normal
        $text = "  Hola mundo  ";
        $prepared = $method->invoke($this->translator, $text);
        $this->assertEquals("Hola mundo", $prepared);

        // HTML entities
        $text = "Caf&eacute; &amp; t&eacute;";
        $prepared = $method->invoke($this->translator, $text);
        $this->assertStringContainsString("Café", $prepared);
    }

    /**
     * Test: División de textos largos en párrafos
     */
    public function test_split_into_paragraphs() {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('split_into_chunks');
        $method->setAccessible(true);

        $longText = str_repeat("Este es un párrafo. ", 100);
        $chunks = $method->invoke($this->translator, $longText, 500);

        $this->assertIsArray($chunks);
        $this->assertGreaterThan(1, count($chunks));

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(600, strlen($chunk)); // Margen
        }
    }

    /**
     * Test: Generación de clave de caché
     */
    public function test_cache_key_generation() {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('get_cache_key');
        $method->setAccessible(true);

        $key1 = $method->invoke($this->translator, 'Hola', 'es', 'en');
        $key2 = $method->invoke($this->translator, 'Hola', 'es', 'en');
        $key3 = $method->invoke($this->translator, 'Hola', 'es', 'eu');

        $this->assertEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
    }

    /**
     * Test: Contexto por tipo de contenido
     */
    public function test_context_by_content_type() {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('get_context_for_type');
        $method->setAccessible(true);

        $titleContext = $method->invoke($this->translator, 'title');
        $contentContext = $method->invoke($this->translator, 'content');
        $excerptContext = $method->invoke($this->translator, 'excerpt');

        $this->assertIsString($titleContext);
        $this->assertIsString($contentContext);
        $this->assertIsString($excerptContext);
    }

    /**
     * Test: Escape de caracteres especiales para prompt
     */
    public function test_escape_for_prompt() {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('escape_for_prompt');
        $method->setAccessible(true);

        $text = 'Texto con "comillas" y \\barras\\';
        $escaped = $method->invoke($this->translator, $text);

        $this->assertStringNotContainsString('\\', $escaped);
    }

    /**
     * Test: Rate limiting
     */
    public function test_rate_limiting() {
        WP_Mock::userFunction('get_transient', [
            'args' => ['flavor_ml_rate_limit_*'],
            'return' => 100 // Ya se han hecho 100 peticiones
        ]);

        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('check_rate_limit');
        $method->setAccessible(true);

        // Debería indicar que estamos cerca del límite
        $canProceed = $method->invoke($this->translator);
        $this->assertIsBool($canProceed);
    }

    /**
     * Test: Fallback de motores de traducción
     */
    public function test_engine_fallback_order() {
        $reflection = new ReflectionClass($this->translator);
        $property = $reflection->getProperty('fallback_engines');
        $property->setAccessible(true);

        $engines = $property->getValue($this->translator);

        $this->assertIsArray($engines);
        $this->assertContains('openai', $engines);
        $this->assertContains('google', $engines);
    }

    /**
     * Test: Validación de respuesta de API
     */
    public function test_validate_api_response() {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('validate_translation_response');
        $method->setAccessible(true);

        // Respuesta válida
        $valid = $method->invoke($this->translator, 'Traducción válida', 'Texto original');
        $this->assertTrue($valid);

        // Respuesta vacía
        $empty = $method->invoke($this->translator, '', 'Texto original');
        $this->assertFalse($empty);

        // Respuesta igual al original (posible error)
        $same = $method->invoke($this->translator, 'Texto original', 'Texto original');
        $this->assertFalse($same);
    }

    /**
     * Test: Preservación de HTML en traducción
     */
    public function test_html_preservation() {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('preserve_html_structure');
        $method->setAccessible(true);

        $html = '<p>Texto <strong>importante</strong></p>';
        $result = $method->invoke($this->translator, $html);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
    }

    /**
     * Test: Manejo de errores de API
     */
    public function test_api_error_handling() {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('handle_api_error');
        $method->setAccessible(true);

        $error = new WP_Error('api_error', 'Error de conexión');
        $result = $method->invoke($this->translator, $error);

        $this->assertInstanceOf(WP_Error::class, $result);
    }
}
