<?php
/**
 * Tests para Flavor_Translation_Cache
 *
 * @package FlavorMultilingual
 */

class TranslationCacheTest extends PHPUnit\Framework\TestCase {

    /**
     * @var Flavor_Translation_Cache
     */
    private $cache;

    /**
     * Setup antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();

        // Resetear mocks
        if (function_exists('wp_mock_reset')) {
            wp_mock_reset();
        }

        // Obtener instancia fresca del cache
        $reflection = new ReflectionClass('Flavor_Translation_Cache');
        $instance_property = $reflection->getProperty('instance');
        $instance_property->setAccessible(true);
        $instance_property->setValue(null, null);

        $this->cache = Flavor_Translation_Cache::get_instance();
    }

    /**
     * Test: Singleton devuelve la misma instancia
     */
    public function test_singleton_returns_same_instance() {
        $instance1 = Flavor_Translation_Cache::get_instance();
        $instance2 = Flavor_Translation_Cache::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: Set y get funcionan correctamente
     */
    public function test_set_and_get() {
        $key = 'test_key';
        $value = 'test_value';

        $result = $this->cache->set($key, $value);
        $this->assertTrue($result);

        $retrieved = $this->cache->get($key);
        $this->assertEquals($value, $retrieved);
    }

    /**
     * Test: Get devuelve false para clave inexistente
     */
    public function test_get_nonexistent_returns_false() {
        $result = $this->cache->get('nonexistent_key_12345');
        $this->assertFalse($result);
    }

    /**
     * Test: Delete elimina la clave
     */
    public function test_delete_removes_key() {
        $key = 'delete_test_key';
        $this->cache->set($key, 'some_value');

        $result = $this->cache->delete($key);
        $this->assertTrue($result);

        $retrieved = $this->cache->get($key);
        $this->assertFalse($retrieved);
    }

    /**
     * Test: Exists verifica correctamente
     */
    public function test_exists() {
        $key = 'exists_test_key';

        $this->assertFalse($this->cache->exists($key));

        $this->cache->set($key, 'value');
        $this->assertTrue($this->cache->exists($key));
    }

    /**
     * Test: set_translation y get_translation
     */
    public function test_translation_methods() {
        $type = 'post';
        $object_id = 123;
        $lang = 'es';
        $field = 'title';
        $value = 'Título traducido';

        $this->cache->set_translation($type, $object_id, $lang, $field, $value);
        $retrieved = $this->cache->get_translation($type, $object_id, $lang, $field);

        $this->assertEquals($value, $retrieved);
    }

    /**
     * Test: get_all_translations y set_all_translations
     */
    public function test_all_translations_methods() {
        $type = 'post';
        $object_id = 456;
        $translations = array(
            'es' => array('title' => 'Título', 'content' => 'Contenido'),
            'en' => array('title' => 'Title', 'content' => 'Content'),
        );

        $this->cache->set_all_translations($type, $object_id, $translations);
        $retrieved = $this->cache->get_all_translations($type, $object_id);

        $this->assertEquals($translations, $retrieved);
    }

    /**
     * Test: get_string y set_string
     */
    public function test_string_methods() {
        $original = 'Hello World';
        $lang = 'es';
        $translation = 'Hola Mundo';
        $domain = 'my-plugin';

        $this->cache->set_string($original, $lang, $translation, $domain);
        $retrieved = $this->cache->get_string($original, $lang, $domain);

        $this->assertEquals($translation, $retrieved);
    }

    /**
     * Test: Estadísticas de cache
     */
    public function test_stats() {
        // Generar algunos hits y misses
        $this->cache->set('stats_test', 'value');
        $this->cache->get('stats_test'); // Hit
        $this->cache->get('stats_test'); // Hit
        $this->cache->get('nonexistent'); // Miss

        $stats = $this->cache->get_stats();

        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('writes', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertArrayHasKey('memory_items', $stats);

        $this->assertGreaterThanOrEqual(2, $stats['hits']);
        $this->assertGreaterThanOrEqual(1, $stats['misses']);
    }

    /**
     * Test: Caché con arrays complejos
     */
    public function test_complex_array_caching() {
        $complex_data = array(
            'nested' => array(
                'level1' => array(
                    'level2' => 'deep_value',
                ),
            ),
            'list' => array('a', 'b', 'c'),
            'mixed' => array(
                'string' => 'text',
                'number' => 42,
                'boolean' => true,
                'null' => null,
            ),
        );

        $this->cache->set('complex_key', $complex_data);
        $retrieved = $this->cache->get('complex_key');

        $this->assertEquals($complex_data, $retrieved);
    }

    /**
     * Test: Caché con HTML
     */
    public function test_html_content_caching() {
        $html = '<div class="test"><p>Content with <strong>HTML</strong></p></div>';

        $this->cache->set_translation('post', 1, 'es', 'content', $html);
        $retrieved = $this->cache->get_translation('post', 1, 'es', 'content');

        $this->assertEquals($html, $retrieved);
    }

    /**
     * Test: get_memory_size devuelve valor válido
     */
    public function test_memory_size() {
        $this->cache->set('size_test_1', 'value1');
        $this->cache->set('size_test_2', str_repeat('x', 1000));

        $size = $this->cache->get_memory_size();

        $this->assertIsInt($size);
        $this->assertGreaterThan(0, $size);
    }

    /**
     * Test: Invalidar traducción específica
     */
    public function test_invalidate_translation() {
        $type = 'post';
        $object_id = 789;
        $lang = 'es';
        $field = 'title';

        // Guardar traducción
        $this->cache->set_translation($type, $object_id, $lang, $field, 'Título');
        $this->cache->set_all_translations($type, $object_id, array('es' => array('title' => 'Título')));

        // Invalidar
        $this->cache->invalidate_translation($type, $object_id, $lang, $field);

        // Verificar que se eliminó
        $this->assertFalse($this->cache->get_translation($type, $object_id, $lang, $field));
        $this->assertFalse($this->cache->get_all_translations($type, $object_id));
    }
}
