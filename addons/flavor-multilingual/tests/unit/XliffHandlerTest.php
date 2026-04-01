<?php
/**
 * Tests para Flavor_XLIFF_Handler
 *
 * @package FlavorMultilingual
 */

class XliffHandlerTest extends PHPUnit\Framework\TestCase {

    /**
     * @var Flavor_XLIFF_Handler
     */
    private $xliff;

    /**
     * XLIFF de ejemplo para importación
     *
     * @var string
     */
    private $sample_xliff;

    /**
     * Setup antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();

        // Resetear mocks
        if (function_exists('wp_mock_reset')) {
            wp_mock_reset();
        }

        // Obtener instancia fresca
        $reflection = new ReflectionClass('Flavor_XLIFF_Handler');
        $instance_property = $reflection->getProperty('instance');
        $instance_property->setAccessible(true);
        $instance_property->setValue(null, null);

        $this->xliff = Flavor_XLIFF_Handler::get_instance();

        // XLIFF de ejemplo
        $this->sample_xliff = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:flavor="https://flavor-platform.com/xliff">
  <file original="https://example.com/post" source-language="es-ES" target-language="en-US" datatype="html">
    <header>
      <tool tool-id="flavor-multilingual" tool-name="Flavor Multilingual" tool-version="1.2.0"/>
    </header>
    <body>
      <group id="post-123" restype="x-post" flavor:post-id="123" flavor:post-type="post">
        <trans-unit id="123-title" resname="title">
          <source xml:lang="es-ES">Título de prueba</source>
          <target xml:lang="en-US" state="needs-translation">Test title</target>
        </trans-unit>
        <trans-unit id="123-excerpt" resname="excerpt">
          <source xml:lang="es-ES">Este es el extracto</source>
          <target xml:lang="en-US" state="translated">This is the excerpt</target>
        </trans-unit>
        <trans-unit id="123-content" resname="content">
          <source xml:lang="es-ES"><![CDATA[<p>Contenido con HTML</p>]]></source>
          <target xml:lang="en-US" state="translated"><![CDATA[<p>Content with HTML</p>]]></target>
        </trans-unit>
      </group>
    </body>
  </file>
</xliff>';
    }

    /**
     * Test: Singleton devuelve la misma instancia
     */
    public function test_singleton_returns_same_instance() {
        $instance1 = Flavor_XLIFF_Handler::get_instance();
        $instance2 = Flavor_XLIFF_Handler::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: Parsear XLIFF válido
     */
    public function test_parse_valid_xliff() {
        $reflection = new ReflectionClass($this->xliff);
        $method = $reflection->getMethod('parse_xliff');
        $method->setAccessible(true);

        $result = $method->invoke($this->xliff, $this->sample_xliff);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('source_lang', $result);
        $this->assertArrayHasKey('target_lang', $result);
        $this->assertArrayHasKey('units', $result);

        $this->assertEquals('es', $result['source_lang']);
        $this->assertEquals('en', $result['target_lang']);
        $this->assertCount(3, $result['units']);
    }

    /**
     * Test: Parsear XLIFF inválido devuelve error
     */
    public function test_parse_invalid_xliff_returns_error() {
        $reflection = new ReflectionClass($this->xliff);
        $method = $reflection->getMethod('parse_xliff');
        $method->setAccessible(true);

        $invalid_xml = '<not valid xml>';
        $result = $method->invoke($this->xliff, $invalid_xml);

        $this->assertInstanceOf('WP_Error', $result);
    }

    /**
     * Test: Extraer unidades de traducción correctamente
     */
    public function test_extract_translation_units() {
        $reflection = new ReflectionClass($this->xliff);
        $method = $reflection->getMethod('parse_xliff');
        $method->setAccessible(true);

        $result = $method->invoke($this->xliff, $this->sample_xliff);

        // Verificar primera unidad (título)
        $title_unit = $result['units'][0];
        $this->assertEquals('123-title', $title_unit['id']);
        $this->assertEquals(123, $title_unit['post_id']);
        $this->assertEquals('title', $title_unit['field']);
        $this->assertEquals('Título de prueba', $title_unit['source']);
        $this->assertEquals('Test title', $title_unit['target']);
        $this->assertEquals('en', $title_unit['target_lang']);
    }

    /**
     * Test: Manejar CDATA correctamente
     */
    public function test_handle_cdata_content() {
        $reflection = new ReflectionClass($this->xliff);
        $method = $reflection->getMethod('parse_xliff');
        $method->setAccessible(true);

        $result = $method->invoke($this->xliff, $this->sample_xliff);

        // Buscar unidad de contenido
        $content_unit = null;
        foreach ($result['units'] as $unit) {
            if ($unit['field'] === 'content') {
                $content_unit = $unit;
                break;
            }
        }

        $this->assertNotNull($content_unit);
        $this->assertEquals('<p>Contenido con HTML</p>', $content_unit['source']);
        $this->assertEquals('<p>Content with HTML</p>', $content_unit['target']);
    }

    /**
     * Test: Conversión de locales XLIFF a internos
     */
    public function test_locale_conversion() {
        $reflection = new ReflectionClass($this->xliff);
        $from_method = $reflection->getMethod('from_xliff_locale');
        $from_method->setAccessible(true);

        $to_method = $reflection->getMethod('to_xliff_locale');
        $to_method->setAccessible(true);

        // De XLIFF a interno
        $this->assertEquals('es', $from_method->invoke($this->xliff, 'es-ES'));
        $this->assertEquals('en', $from_method->invoke($this->xliff, 'en-US'));
        $this->assertEquals('eu', $from_method->invoke($this->xliff, 'eu'));

        // De interno a XLIFF
        $this->assertEquals('es-ES', $to_method->invoke($this->xliff, 'es'));
        $this->assertEquals('en-US', $to_method->invoke($this->xliff, 'en'));
    }

    /**
     * Test: XLIFF vacío
     */
    public function test_empty_xliff() {
        $empty_xliff = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file original="test" source-language="es" target-language="en" datatype="html">
    <body></body>
  </file>
</xliff>';

        $reflection = new ReflectionClass($this->xliff);
        $method = $reflection->getMethod('parse_xliff');
        $method->setAccessible(true);

        $result = $method->invoke($this->xliff, $empty_xliff);

        $this->assertIsArray($result);
        $this->assertCount(0, $result['units']);
    }

    /**
     * Test: Múltiples archivos en XLIFF
     */
    public function test_multiple_files_xliff() {
        $multi_file_xliff = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:flavor="https://flavor-platform.com/xliff">
  <file original="https://example.com/post" source-language="es-ES" target-language="en-US" datatype="html">
    <body>
      <group id="post-1" flavor:post-id="1">
        <trans-unit id="1-title" resname="title">
          <source>Título 1</source>
          <target>Title 1</target>
        </trans-unit>
      </group>
    </body>
  </file>
  <file original="https://example.com/page" source-language="es-ES" target-language="en-US" datatype="html">
    <body>
      <group id="post-2" flavor:post-id="2">
        <trans-unit id="2-title" resname="title">
          <source>Título 2</source>
          <target>Title 2</target>
        </trans-unit>
      </group>
    </body>
  </file>
</xliff>';

        $reflection = new ReflectionClass($this->xliff);
        $method = $reflection->getMethod('parse_xliff');
        $method->setAccessible(true);

        $result = $method->invoke($this->xliff, $multi_file_xliff);

        $this->assertIsArray($result);
        $this->assertCount(2, $result['units']);
    }

    /**
     * Test: Verificar permisos de exportación
     */
    public function test_export_permission() {
        // El mock siempre devuelve true para current_user_can
        $this->assertTrue($this->xliff->check_export_permission());
    }

    /**
     * Test: Verificar permisos de importación
     */
    public function test_import_permission() {
        // El mock siempre devuelve true para current_user_can
        $this->assertTrue($this->xliff->check_import_permission());
    }

    /**
     * Test: Caracteres especiales en XLIFF
     */
    public function test_special_characters() {
        $special_xliff = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:flavor="https://flavor-platform.com/xliff">
  <file original="test" source-language="es" target-language="en" datatype="html">
    <body>
      <group id="post-1" flavor:post-id="1">
        <trans-unit id="1-title" resname="title">
          <source>Título con ñ, ü y €</source>
          <target>Title with ñ, ü and €</target>
        </trans-unit>
      </group>
    </body>
  </file>
</xliff>';

        $reflection = new ReflectionClass($this->xliff);
        $method = $reflection->getMethod('parse_xliff');
        $method->setAccessible(true);

        $result = $method->invoke($this->xliff, $special_xliff);

        $this->assertEquals('Título con ñ, ü y €', $result['units'][0]['source']);
        $this->assertEquals('Title with ñ, ü and €', $result['units'][0]['target']);
    }
}
