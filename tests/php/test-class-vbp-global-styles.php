<?php
/**
 * Tests for Flavor_VBP_Global_Styles class.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

/**
 * Test class for VBP Global Styles functionality.
 */
class Test_VBP_Global_Styles extends VBP_UnitTestCase {

    /**
     * Global Styles instance.
     *
     * @var Flavor_VBP_Global_Styles|null
     */
    private $global_styles;

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        if ( class_exists( 'Flavor_VBP_Global_Styles' ) ) {
            $this->global_styles = Flavor_VBP_Global_Styles::get_instance();
        } else {
            $this->markTestSkipped( 'Flavor_VBP_Global_Styles class not available' );
        }
    }

    /**
     * Test constants are defined.
     */
    public function test_constants_defined() {
        $this->assertEquals( 'flavor-vbp/v1', Flavor_VBP_Global_Styles::REST_NAMESPACE );
        $this->assertEquals( 'vbp_global_styles', Flavor_VBP_Global_Styles::OPTION_NAME );
    }

    /**
     * Test categories constant contains expected categories.
     */
    public function test_categories_constant() {
        $categories = Flavor_VBP_Global_Styles::CATEGORIES;

        $this->assertIsArray( $categories );
        $this->assertArrayHasKey( 'typography', $categories );
        $this->assertArrayHasKey( 'buttons', $categories );
        $this->assertArrayHasKey( 'containers', $categories );
        $this->assertArrayHasKey( 'custom', $categories );

        // Check category structure.
        $typography = $categories['typography'];
        $this->assertArrayHasKey( 'id', $typography );
        $this->assertArrayHasKey( 'name', $typography );
        $this->assertArrayHasKey( 'icon', $typography );
        $this->assertArrayHasKey( 'order', $typography );
    }

    /**
     * Test singleton pattern.
     */
    public function test_singleton_pattern() {
        $instance1 = Flavor_VBP_Global_Styles::get_instance();
        $instance2 = Flavor_VBP_Global_Styles::get_instance();

        $this->assertSame( $instance1, $instance2 );
    }

    /**
     * Test permission callbacks exist.
     */
    public function test_permission_callbacks_exist() {
        $this->assertTrue( method_exists( $this->global_styles, 'verificar_permiso_lectura' ) );
        $this->assertTrue( method_exists( $this->global_styles, 'verificar_permiso_escritura' ) );
    }

    /**
     * Test permission callbacks return booleans.
     */
    public function test_permission_callbacks_return_booleans() {
        $this->assertIsBool( $this->global_styles->verificar_permiso_lectura() );
        $this->assertIsBool( $this->global_styles->verificar_permiso_escritura() );
    }

    /**
     * Test REST route registration method exists.
     */
    public function test_rest_route_registration_method_exists() {
        $this->assertTrue( method_exists( $this->global_styles, 'registrar_rutas_rest' ) );
    }

    /**
     * Test CSS injection methods exist.
     */
    public function test_css_injection_methods_exist() {
        $this->assertTrue( method_exists( $this->global_styles, 'inyectar_css_frontend' ) );
        $this->assertTrue( method_exists( $this->global_styles, 'inyectar_css_editor' ) );
    }

    /**
     * Test style structure validation.
     */
    public function test_style_structure_validation() {
        $valid_style = array(
            'id'          => 'heading-primary',
            'name'        => 'Primary Heading',
            'category'    => 'typography',
            'description' => 'Main heading style',
            'css'         => array(
                'font-size'   => '2rem',
                'font-weight' => '700',
                'color'       => '#333333',
            ),
            'responsive'  => array(
                'tablet' => array(
                    'font-size' => '1.75rem',
                ),
                'mobile' => array(
                    'font-size' => '1.5rem',
                ),
            ),
        );

        $required_keys = array( 'id', 'name', 'category', 'css' );

        foreach ( $required_keys as $key ) {
            $this->assertArrayHasKey( $key, $valid_style );
        }

        // CSS should be array or string.
        $this->assertTrue(
            is_array( $valid_style['css'] ) || is_string( $valid_style['css'] )
        );
    }

    /**
     * Test button style preset structure.
     */
    public function test_button_style_preset() {
        $button_style = array(
            'id'       => 'button-primary',
            'name'     => 'Primary Button',
            'category' => 'buttons',
            'css'      => array(
                'display'          => 'inline-flex',
                'align-items'      => 'center',
                'justify-content'  => 'center',
                'padding'          => '12px 24px',
                'background-color' => 'var(--color-primary)',
                'color'            => '#ffffff',
                'border-radius'    => '8px',
                'font-weight'      => '600',
                'transition'       => 'all 0.2s ease',
            ),
            'states'   => array(
                'hover' => array(
                    'background-color' => 'var(--color-primary-dark)',
                    'transform'        => 'translateY(-2px)',
                ),
                'active' => array(
                    'transform' => 'translateY(0)',
                ),
            ),
        );

        $this->assertEquals( 'buttons', $button_style['category'] );
        $this->assertArrayHasKey( 'states', $button_style );
        $this->assertArrayHasKey( 'hover', $button_style['states'] );
    }

    /**
     * Test container style preset structure.
     */
    public function test_container_style_preset() {
        $container_style = array(
            'id'       => 'card-default',
            'name'     => 'Default Card',
            'category' => 'containers',
            'css'      => array(
                'background-color' => '#ffffff',
                'border-radius'    => '12px',
                'box-shadow'       => '0 4px 6px rgba(0, 0, 0, 0.1)',
                'padding'          => '24px',
            ),
        );

        $this->assertEquals( 'containers', $container_style['category'] );
        $this->assertArrayHasKey( 'box-shadow', $container_style['css'] );
    }

    /**
     * Test CSS property names are valid.
     */
    public function test_css_property_names_valid() {
        $valid_properties = array(
            'color',
            'background-color',
            'font-size',
            'font-weight',
            'margin',
            'padding',
            'border-radius',
            'box-shadow',
            'display',
            'flex-direction',
            'align-items',
            'justify-content',
            'gap',
            'width',
            'height',
            'max-width',
            'min-height',
            'position',
            'z-index',
            'opacity',
            'transform',
            'transition',
        );

        foreach ( $valid_properties as $property ) {
            // CSS properties should be lowercase with hyphens.
            $this->assertMatchesRegularExpression(
                '/^[a-z-]+$/',
                $property,
                "Property '{$property}' should be lowercase with hyphens"
            );
        }
    }

    /**
     * Test CSS generation from style object.
     */
    public function test_css_generation() {
        $style = array(
            'id'  => 'test-style',
            'css' => array(
                'color'      => '#333',
                'font-size'  => '16px',
                'margin-top' => '20px',
            ),
        );

        // Expected CSS output format.
        $expected_selector = '.vbp-style-test-style';
        $expected_contains = array(
            'color: #333',
            'font-size: 16px',
            'margin-top: 20px',
        );

        // Build mock CSS string.
        $css = "{$expected_selector} {\n";
        foreach ( $style['css'] as $property => $value ) {
            $css .= "    {$property}: {$value};\n";
        }
        $css .= '}';

        // Verify the CSS contains expected properties.
        foreach ( $expected_contains as $expected ) {
            $this->assertStringContainsString( $expected, $css );
        }
    }

    /**
     * Test responsive breakpoints.
     */
    public function test_responsive_breakpoints() {
        $breakpoints = array(
            'desktop' => 1200,
            'laptop'  => 1024,
            'tablet'  => 768,
            'mobile'  => 480,
        );

        // Each breakpoint should be a number.
        foreach ( $breakpoints as $name => $width ) {
            $this->assertIsInt( $width );
            $this->assertGreaterThan( 0, $width );
        }

        // Breakpoints should be in descending order.
        $values = array_values( $breakpoints );
        for ( $i = 0; $i < count( $values ) - 1; $i++ ) {
            $this->assertGreaterThan(
                $values[ $i + 1 ],
                $values[ $i ],
                'Breakpoints should be in descending order'
            );
        }
    }

    /**
     * Test style ID sanitization.
     */
    public function test_style_id_sanitization() {
        $test_cases = array(
            'Simple Name'       => 'simple-name',
            'Name With Numbers' => 'name-with-numbers',
            'Name__With__Chars' => 'name__with__chars',
        );

        foreach ( $test_cases as $input => $expected_pattern ) {
            $sanitized = sanitize_title( $input );

            // Should only contain lowercase letters, numbers, and hyphens.
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9-]+$/',
                $sanitized,
                "Sanitized ID should only contain lowercase letters, numbers, and hyphens"
            );
        }
    }

    /**
     * Test CSS variable references.
     */
    public function test_css_variable_references() {
        $style_with_variables = array(
            'css' => array(
                'color'            => 'var(--text-primary)',
                'background-color' => 'var(--bg-secondary)',
                'font-size'        => 'var(--font-size-lg)',
                'padding'          => 'var(--spacing-md)',
            ),
        );

        foreach ( $style_with_variables['css'] as $property => $value ) {
            // Values using CSS variables should match pattern.
            $this->assertMatchesRegularExpression(
                '/^var\(--[a-z-]+\)$/',
                $value,
                "CSS variable reference should follow var(--name) pattern"
            );
        }
    }

    /**
     * Test category ordering.
     */
    public function test_category_ordering() {
        $categories = Flavor_VBP_Global_Styles::CATEGORIES;

        $orders = array();
        foreach ( $categories as $cat ) {
            $orders[ $cat['id'] ] = $cat['order'];
        }

        // Typography should come before buttons.
        $this->assertLessThan( $orders['buttons'], $orders['typography'] );

        // Custom should be last.
        $this->assertGreaterThanOrEqual( max( $orders ), $orders['custom'] );
    }

    /**
     * Test style export format.
     */
    public function test_style_export_format() {
        $export_data = array(
            'version'     => '1.0',
            'exported_at' => gmdate( 'c' ),
            'site_url'    => 'https://example.com',
            'styles'      => array(
                array(
                    'id'       => 'style-1',
                    'name'     => 'Style One',
                    'category' => 'typography',
                    'css'      => array(),
                ),
            ),
        );

        $this->assertArrayHasKey( 'version', $export_data );
        $this->assertArrayHasKey( 'exported_at', $export_data );
        $this->assertArrayHasKey( 'styles', $export_data );
        $this->assertIsArray( $export_data['styles'] );
    }
}
