<?php
/**
 * Tests para Flavor_Translation_Memory
 *
 * @package FlavorMultilingual
 * @subpackage Tests
 */

class TranslationMemoryTest extends WP_Mock_TestCase {

    private $memory;

    public function setUp(): void {
        parent::setUp();
        WP_Mock::setUp();

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';

        $this->memory = Flavor_Translation_Memory::get_instance();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test: Singleton
     */
    public function test_singleton_instance() {
        $instance1 = Flavor_Translation_Memory::get_instance();
        $instance2 = Flavor_Translation_Memory::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: Normalización de texto para búsqueda
     */
    public function test_normalize_text() {
        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('normalize_text');
        $method->setAccessible(true);

        // Espacios múltiples
        $text = "Hola    mundo";
        $normalized = $method->invoke($this->memory, $text);
        $this->assertEquals("hola mundo", $normalized);

        // Mayúsculas y minúsculas
        $text = "HOLA Mundo";
        $normalized = $method->invoke($this->memory, $text);
        $this->assertEquals("hola mundo", $normalized);

        // Puntuación extra
        $text = "Hola, mundo!!!";
        $normalized = $method->invoke($this->memory, $text);
        $this->assertStringNotContainsString("!!!", $normalized);
    }

    /**
     * Test: Cálculo de similitud
     */
    public function test_calculate_similarity() {
        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('calculate_similarity');
        $method->setAccessible(true);

        // Textos idénticos
        $similarity = $method->invoke($this->memory, "Hola mundo", "Hola mundo");
        $this->assertEquals(1.0, $similarity);

        // Textos similares
        $similarity = $method->invoke($this->memory, "Hola mundo", "Hola mundo!");
        $this->assertGreaterThan(0.8, $similarity);

        // Textos diferentes
        $similarity = $method->invoke($this->memory, "Hola mundo", "Adiós universo");
        $this->assertLessThan(0.5, $similarity);
    }

    /**
     * Test: Generación de hash para segmento
     */
    public function test_segment_hash() {
        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('get_segment_hash');
        $method->setAccessible(true);

        $hash1 = $method->invoke($this->memory, "Hola mundo", "es", "en");
        $hash2 = $method->invoke($this->memory, "Hola mundo", "es", "en");
        $hash3 = $method->invoke($this->memory, "Hola mundo", "es", "eu");

        $this->assertEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
        $this->assertEquals(32, strlen($hash1)); // MD5 length
    }

    /**
     * Test: Tokenización de texto
     */
    public function test_tokenize() {
        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('tokenize');
        $method->setAccessible(true);

        $tokens = $method->invoke($this->memory, "Hola, cómo estás?");

        $this->assertIsArray($tokens);
        $this->assertContains("hola", $tokens);
        $this->assertContains("cómo", $tokens);
        $this->assertContains("estás", $tokens);
    }

    /**
     * Test: Filtro de stopwords
     */
    public function test_filter_stopwords() {
        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('filter_stopwords');
        $method->setAccessible(true);

        $tokens = ["el", "perro", "de", "la", "casa"];
        $filtered = $method->invoke($this->memory, $tokens, 'es');

        $this->assertContains("perro", $filtered);
        $this->assertContains("casa", $filtered);
        // Stopwords deberían estar filtradas
        $this->assertNotContains("el", $filtered);
        $this->assertNotContains("de", $filtered);
        $this->assertNotContains("la", $filtered);
    }

    /**
     * Test: Preparación de datos para inserción
     */
    public function test_prepare_entry_data() {
        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('prepare_entry');
        $method->setAccessible(true);

        WP_Mock::userFunction('get_current_user_id', ['return' => 1]);
        WP_Mock::userFunction('current_time', ['return' => '2024-01-01 00:00:00']);

        $data = [
            'source_text' => 'Hola mundo',
            'target_text' => 'Hello world',
            'source_lang' => 'es',
            'target_lang' => 'en'
        ];

        $prepared = $method->invoke($this->memory, $data);

        $this->assertArrayHasKey('source_text', $prepared);
        $this->assertArrayHasKey('target_text', $prepared);
        $this->assertArrayHasKey('source_lang', $prepared);
        $this->assertArrayHasKey('target_lang', $prepared);
        $this->assertArrayHasKey('segment_hash', $prepared);
    }

    /**
     * Test: Umbral de similitud
     */
    public function test_similarity_threshold() {
        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('meets_threshold');
        $method->setAccessible(true);

        // Por encima del umbral (default 0.7)
        $this->assertTrue($method->invoke($this->memory, 0.8));
        $this->assertTrue($method->invoke($this->memory, 1.0));

        // Por debajo del umbral
        $this->assertFalse($method->invoke($this->memory, 0.5));
        $this->assertFalse($method->invoke($this->memory, 0.3));
    }

    /**
     * Test: Ordenación de resultados por similitud
     */
    public function test_sort_by_similarity() {
        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('sort_results');
        $method->setAccessible(true);

        $results = [
            (object)['source_text' => 'A', 'score' => 0.5],
            (object)['source_text' => 'B', 'score' => 0.9],
            (object)['source_text' => 'C', 'score' => 0.7],
        ];

        $sorted = $method->invoke($this->memory, $results);

        $this->assertEquals('B', $sorted[0]->source_text);
        $this->assertEquals('C', $sorted[1]->source_text);
        $this->assertEquals('A', $sorted[2]->source_text);
    }

    /**
     * Test: Límite de resultados
     */
    public function test_result_limit() {
        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('limit_results');
        $method->setAccessible(true);

        $results = array_fill(0, 20, (object)['source_text' => 'Test']);
        $limited = $method->invoke($this->memory, $results, 5);

        $this->assertCount(5, $limited);
    }

    /**
     * Test: Estadísticas de memoria
     */
    public function test_get_statistics() {
        global $wpdb;

        $wpdb->shouldReceive('get_var')
            ->andReturn(100);

        $wpdb->shouldReceive('get_results')
            ->andReturn([
                (object)['source_lang' => 'es', 'target_lang' => 'en', 'count' => 50],
                (object)['source_lang' => 'es', 'target_lang' => 'eu', 'count' => 30],
            ]);

        $reflection = new ReflectionClass($this->memory);
        $method = $reflection->getMethod('get_stats');
        $method->setAccessible(true);

        $stats = $method->invoke($this->memory);

        $this->assertIsArray($stats);
    }
}
