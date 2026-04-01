<?php
/**
 * Tests para Flavor_Translation_Storage
 *
 * @package FlavorMultilingual
 * @subpackage Tests
 */

class TranslationStorageTest extends WP_Mock_TestCase {

    private $storage;

    public function setUp(): void {
        parent::setUp();
        WP_Mock::setUp();

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';

        $this->storage = Flavor_Translation_Storage::get_instance();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test: Singleton
     */
    public function test_singleton_instance() {
        $instance1 = Flavor_Translation_Storage::get_instance();
        $instance2 = Flavor_Translation_Storage::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: Validación de tipo de objeto
     */
    public function test_validate_object_type() {
        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('validate_object_type');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->storage, 'post'));
        $this->assertTrue($method->invoke($this->storage, 'term'));
        $this->assertTrue($method->invoke($this->storage, 'menu_item'));
        $this->assertFalse($method->invoke($this->storage, 'invalid_type'));
    }

    /**
     * Test: Validación de código de idioma
     */
    public function test_validate_language_code() {
        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('validate_language_code');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->storage, 'es'));
        $this->assertTrue($method->invoke($this->storage, 'en'));
        $this->assertTrue($method->invoke($this->storage, 'eu'));
        $this->assertFalse($method->invoke($this->storage, 'invalid'));
        $this->assertFalse($method->invoke($this->storage, ''));
    }

    /**
     * Test: Sanitización de nombre de campo
     */
    public function test_sanitize_field_name() {
        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('sanitize_field_name');
        $method->setAccessible(true);

        $this->assertEquals('title', $method->invoke($this->storage, 'title'));
        $this->assertEquals('custom_field', $method->invoke($this->storage, 'custom_field'));
        $this->assertEquals('field_name', $method->invoke($this->storage, 'field-name'));
    }

    /**
     * Test: Preparación de datos para guardar
     */
    public function test_prepare_save_data() {
        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('prepare_save_data');
        $method->setAccessible(true);

        WP_Mock::userFunction('current_time', ['return' => '2024-01-01 00:00:00']);
        WP_Mock::userFunction('wp_kses_post', ['return_arg' => 0]);

        $data = $method->invoke($this->storage, [
            'object_type' => 'post',
            'object_id' => 123,
            'language_code' => 'eu',
            'field_name' => 'title',
            'translation' => 'Kaixo mundua'
        ]);

        $this->assertArrayHasKey('object_type', $data);
        $this->assertArrayHasKey('object_id', $data);
        $this->assertArrayHasKey('language_code', $data);
        $this->assertArrayHasKey('field_name', $data);
        $this->assertArrayHasKey('translation', $data);
    }

    /**
     * Test: Generación de clave de caché
     */
    public function test_cache_key() {
        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('get_cache_key');
        $method->setAccessible(true);

        $key = $method->invoke($this->storage, 'post', 123, 'eu', 'title');

        $this->assertIsString($key);
        $this->assertStringContainsString('post', $key);
        $this->assertStringContainsString('123', $key);
        $this->assertStringContainsString('eu', $key);
    }

    /**
     * Test: Verificar si traducción existe
     */
    public function test_has_translation() {
        global $wpdb;

        $wpdb->shouldReceive('prepare')
            ->andReturn("SELECT COUNT(*) FROM wp_flavor_translations WHERE ...");

        $wpdb->shouldReceive('get_var')
            ->andReturn(1);

        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('translation_exists');
        $method->setAccessible(true);

        $exists = $method->invoke($this->storage, 'post', 123, 'eu', 'title');

        $this->assertTrue($exists);
    }

    /**
     * Test: Estados de traducción válidos
     */
    public function test_valid_statuses() {
        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('get_valid_statuses');
        $method->setAccessible(true);

        $statuses = $method->invoke($this->storage);

        $this->assertContains('draft', $statuses);
        $this->assertContains('pending', $statuses);
        $this->assertContains('in_progress', $statuses);
        $this->assertContains('needs_review', $statuses);
        $this->assertContains('approved', $statuses);
        $this->assertContains('published', $statuses);
    }

    /**
     * Test: Conteo de traducciones por estado
     */
    public function test_count_by_status() {
        global $wpdb;

        $wpdb->shouldReceive('prepare')
            ->andReturn("SELECT COUNT(*) FROM wp_flavor_translations WHERE status = 'published'");

        $wpdb->shouldReceive('get_var')
            ->andReturn(50);

        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('count_by_status');
        $method->setAccessible(true);

        $count = $method->invoke($this->storage, 'published');

        $this->assertEquals(50, $count);
    }

    /**
     * Test: Obtener campos traducibles de un post
     */
    public function test_get_translatable_fields() {
        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('get_translatable_fields');
        $method->setAccessible(true);

        WP_Mock::userFunction('apply_filters', ['return_arg' => 1]);

        $fields = $method->invoke($this->storage, 'post');

        $this->assertIsArray($fields);
        $this->assertContains('title', $fields);
        $this->assertContains('content', $fields);
        $this->assertContains('excerpt', $fields);
    }

    /**
     * Test: Cálculo de progreso de traducción
     */
    public function test_calculate_progress() {
        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('calculate_progress');
        $method->setAccessible(true);

        // 5 de 10 campos traducidos = 50%
        $progress = $method->invoke($this->storage, 5, 10);
        $this->assertEquals(50, $progress);

        // 0 de 10 = 0%
        $progress = $method->invoke($this->storage, 0, 10);
        $this->assertEquals(0, $progress);

        // 10 de 10 = 100%
        $progress = $method->invoke($this->storage, 10, 10);
        $this->assertEquals(100, $progress);

        // División por cero
        $progress = $method->invoke($this->storage, 5, 0);
        $this->assertEquals(0, $progress);
    }

    /**
     * Test: Limpieza de caché al guardar
     */
    public function test_cache_invalidation_on_save() {
        WP_Mock::userFunction('wp_cache_delete', [
            'times' => 1,
            'return' => true
        ]);

        WP_Mock::userFunction('delete_transient', [
            'return' => true
        ]);

        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('invalidate_cache');
        $method->setAccessible(true);

        $result = $method->invoke($this->storage, 'post', 123, 'eu');

        $this->assertTrue($result);
    }

    /**
     * Test: Exportar traducciones
     */
    public function test_export_format() {
        $reflection = new ReflectionClass($this->storage);
        $method = $reflection->getMethod('format_for_export');
        $method->setAccessible(true);

        $translation = (object)[
            'object_type' => 'post',
            'object_id' => 123,
            'language_code' => 'eu',
            'field_name' => 'title',
            'translation' => 'Kaixo mundua',
            'status' => 'published'
        ];

        $formatted = $method->invoke($this->storage, $translation);

        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('source', $formatted);
        $this->assertArrayHasKey('target', $formatted);
    }
}
