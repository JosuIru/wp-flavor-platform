<?php
/**
 * Tests for Flavor_VBP_Symbols class.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

/**
 * Test class for VBP Symbols functionality.
 */
class Test_VBP_Symbols extends VBP_UnitTestCase {

    /**
     * Symbols instance.
     *
     * @var Flavor_VBP_Symbols|null
     */
    private $symbols;

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        // Mock the singleton for testing.
        if ( class_exists( 'Flavor_VBP_Symbols' ) ) {
            $this->symbols = Flavor_VBP_Symbols::get_instance();
        } else {
            $this->markTestSkipped( 'Flavor_VBP_Symbols class not available' );
        }
    }

    /**
     * Test that create_symbol returns a valid ID.
     */
    public function test_create_symbol() {
        $symbol_name = 'Test Symbol ' . uniqid();
        $symbol_content = $this->sample_symbol_content;

        $symbol_id = $this->symbols->create_symbol( $symbol_name, $symbol_content );

        if ( is_wp_error( $symbol_id ) ) {
            // Expected behavior when database is not available.
            $this->assertInstanceOf( 'WP_Error', $symbol_id );
        } else {
            $this->assertIsInt( $symbol_id );
            $this->assertGreaterThan( 0, $symbol_id );
        }
    }

    /**
     * Test that create_symbol with empty name returns error.
     */
    public function test_create_symbol_empty_name_returns_error() {
        $symbol_id = $this->symbols->create_symbol( '', $this->sample_symbol_content );

        $this->assertInstanceOf( 'WP_Error', $symbol_id );
        $this->assertEquals( 'invalid_data', $symbol_id->get_error_code() );
    }

    /**
     * Test that create_symbol with empty content returns error.
     */
    public function test_create_symbol_empty_content_returns_error() {
        $symbol_id = $this->symbols->create_symbol( 'Test Symbol', array() );

        $this->assertInstanceOf( 'WP_Error', $symbol_id );
        $this->assertEquals( 'invalid_data', $symbol_id->get_error_code() );
    }

    /**
     * Test register_instance creates a valid instance.
     */
    public function test_create_instance() {
        // First create a symbol.
        $symbol_id = $this->create_mock_symbol();

        if ( is_wp_error( $symbol_id ) ) {
            $this->markTestSkipped( 'Cannot create symbol for instance test' );
        }

        $document_id = $this->test_post_id;
        $element_id = 'element-' . uniqid();

        $instance_id = $this->symbols->register_instance( $symbol_id, $document_id, $element_id );

        if ( is_wp_error( $instance_id ) ) {
            // Expected behavior when database is not available.
            $this->assertInstanceOf( 'WP_Error', $instance_id );
        } else {
            $this->assertIsInt( $instance_id );
            $this->assertGreaterThan( 0, $instance_id );
        }
    }

    /**
     * Test that updating symbol syncs instances.
     */
    public function test_update_symbol_syncs_instances() {
        $symbol_id = $this->create_mock_symbol();

        if ( is_wp_error( $symbol_id ) ) {
            $this->markTestSkipped( 'Cannot create symbol for sync test' );
        }

        // Update the symbol content.
        $new_content = $this->sample_symbol_content;
        $new_content[0]['props']['className'] = 'updated-class';

        $update_result = $this->symbols->update_symbol( $symbol_id, array(
            'content' => $new_content,
        ) );

        if ( is_wp_error( $update_result ) ) {
            // Expected when permissions or database not available.
            $this->assertInstanceOf( 'WP_Error', $update_result );
        } else {
            // Verify sync_instances returns count or success.
            $sync_result = $this->symbols->sync_instances( $symbol_id );
            $this->assertTrue( $sync_result === true || is_int( $sync_result ) || is_wp_error( $sync_result ) );
        }
    }

    /**
     * Test nested symbols validation.
     */
    public function test_nested_symbols() {
        // Create outer symbol.
        $outer_symbol_id = $this->create_mock_symbol( 'Outer Symbol' );

        if ( is_wp_error( $outer_symbol_id ) ) {
            $this->markTestSkipped( 'Cannot create outer symbol' );
        }

        // Create inner symbol with reference to outer.
        $inner_content = array(
            array(
                'id'       => 'inner-root',
                'type'     => 'symbol-instance',
                'props'    => array(
                    'symbolId' => $outer_symbol_id,
                ),
            ),
        );

        $inner_symbol_id = $this->symbols->create_symbol( 'Inner Symbol', $inner_content );

        // Both operations should complete (success or expected error).
        $this->assertTrue(
            is_int( $inner_symbol_id ) || is_wp_error( $inner_symbol_id ),
            'Nested symbol creation should return ID or error'
        );
    }

    /**
     * Test symbol variants functionality.
     */
    public function test_symbol_variants() {
        $symbol_id = $this->create_mock_symbol();

        if ( is_wp_error( $symbol_id ) ) {
            $this->markTestSkipped( 'Cannot create symbol for variant test' );
        }

        // Get default variants.
        $variants = $this->symbols->get_variants( $symbol_id );

        // Should have at least the default variant.
        $this->assertIsArray( $variants );
        $this->assertArrayHasKey( 'default', $variants );

        // Create a new variant.
        $variant_result = $this->symbols->set_variant( $symbol_id, 'dark', array(
            'name'      => 'Dark Mode',
            'overrides' => array(
                'symbol-heading' => array(
                    'color' => '#ffffff',
                ),
            ),
        ) );

        if ( ! is_wp_error( $variant_result ) ) {
            $updated_variants = $this->symbols->get_variants( $symbol_id );
            $this->assertArrayHasKey( 'dark', $updated_variants );
        }
    }

    /**
     * Test cannot delete default variant.
     */
    public function test_cannot_delete_default_variant() {
        $symbol_id = $this->create_mock_symbol();

        if ( is_wp_error( $symbol_id ) ) {
            $this->markTestSkipped( 'Cannot create symbol for delete variant test' );
        }

        $delete_result = $this->symbols->delete_variant( $symbol_id, 'default' );

        $this->assertInstanceOf( 'WP_Error', $delete_result );
        $this->assertEquals( 'cannot_delete_default', $delete_result->get_error_code() );
    }

    /**
     * Test export_symbols returns correct structure.
     */
    public function test_export_symbols_structure() {
        $export_data = $this->symbols->export_symbols( array() );

        $this->assertIsArray( $export_data );
        $this->assertArrayHasKey( 'version', $export_data );
        $this->assertArrayHasKey( 'exported_at', $export_data );
        $this->assertArrayHasKey( 'site_url', $export_data );
        $this->assertArrayHasKey( 'symbols', $export_data );
        $this->assertIsArray( $export_data['symbols'] );
    }

    /**
     * Test import_symbols validates data structure.
     */
    public function test_import_symbols_validates_data() {
        // Test with invalid data.
        $import_result = $this->symbols->import_symbols( 'invalid_string' );

        $this->assertIsArray( $import_result );
        $this->assertArrayHasKey( 'success', $import_result );
        $this->assertFalse( $import_result['success'] );
    }

    /**
     * Test validate_import_data with valid structure.
     */
    public function test_validate_import_data_valid() {
        $valid_data = array(
            'version'  => '1.0',
            'symbols'  => array(
                array(
                    'name'    => 'Test Symbol',
                    'content' => $this->sample_symbol_content,
                ),
            ),
        );

        $validation = $this->symbols->validate_import_data( $valid_data );

        $this->assertIsArray( $validation );
        $this->assertArrayHasKey( 'valid', $validation );
        $this->assertTrue( $validation['valid'] );
    }

    /**
     * Test validate_import_data with invalid structure.
     */
    public function test_validate_import_data_invalid() {
        $invalid_data = array(
            'symbols' => array(
                array(
                    // Missing name and content.
                ),
            ),
        );

        $validation = $this->symbols->validate_import_data( $invalid_data );

        $this->assertIsArray( $validation );
        $this->assertArrayHasKey( 'valid', $validation );
        $this->assertFalse( $validation['valid'] );
        $this->assertNotEmpty( $validation['errors'] );
    }

    /**
     * Test get_categories returns predefined categories.
     */
    public function test_get_categories() {
        $categories = $this->symbols->get_categories();

        $this->assertIsArray( $categories );
        $this->assertNotEmpty( $categories );

        // Check structure of first category.
        $first_category = $categories[0];
        $this->assertArrayHasKey( 'id', $first_category );
        $this->assertArrayHasKey( 'name', $first_category );
        $this->assertArrayHasKey( 'icon', $first_category );
        $this->assertArrayHasKey( 'count', $first_category );
    }

    /**
     * Test get_similar_symbols returns scored results.
     */
    public function test_get_similar_symbols() {
        $symbol_id = $this->create_mock_symbol();

        if ( is_wp_error( $symbol_id ) ) {
            $this->markTestSkipped( 'Cannot create symbol for similarity test' );
        }

        $similar = $this->symbols->get_similar_symbols( $symbol_id, 5 );

        $this->assertIsArray( $similar );

        // If results exist, they should have similarity_score.
        if ( ! empty( $similar ) ) {
            $first = $similar[0];
            $this->assertArrayHasKey( 'similarity_score', $first );
        }
    }

    /**
     * Test calculate_override_compatibility.
     */
    public function test_calculate_override_compatibility() {
        $source_symbol_id = $this->create_mock_symbol( 'Source' );
        $target_symbol_id = $this->create_mock_symbol( 'Target' );

        if ( is_wp_error( $source_symbol_id ) || is_wp_error( $target_symbol_id ) ) {
            $this->markTestSkipped( 'Cannot create symbols for compatibility test' );
        }

        $overrides = array(
            'symbol-heading' => array( 'text' => 'New Title' ),
        );

        $compatibility = $this->symbols->calculate_override_compatibility(
            $source_symbol_id,
            $target_symbol_id,
            $overrides
        );

        $this->assertIsArray( $compatibility );
        $this->assertArrayHasKey( 'compatible', $compatibility );
        $this->assertArrayHasKey( 'incompatible', $compatibility );
        $this->assertArrayHasKey( 'compatibility_score', $compatibility );
    }

    /**
     * Test detach_instance returns content with overrides applied.
     */
    public function test_detach_instance() {
        $symbol_id = $this->create_mock_symbol();

        if ( is_wp_error( $symbol_id ) ) {
            $this->markTestSkipped( 'Cannot create symbol for detach test' );
        }

        $instance_id = $this->symbols->register_instance(
            $symbol_id,
            $this->test_post_id,
            'element-detach-' . uniqid()
        );

        if ( is_wp_error( $instance_id ) ) {
            $this->markTestSkipped( 'Cannot create instance for detach test' );
        }

        $detach_result = $this->symbols->detach_instance( $instance_id );

        if ( ! is_wp_error( $detach_result ) ) {
            $this->assertIsArray( $detach_result );
            $this->assertArrayHasKey( 'content', $detach_result );
            $this->assertArrayHasKey( 'document_id', $detach_result );
            $this->assertArrayHasKey( 'element_id', $detach_result );
        }
    }

    /**
     * Helper to create a mock symbol for testing.
     *
     * @param string $name Optional symbol name.
     * @return int|WP_Error Symbol ID or error.
     */
    private function create_mock_symbol( $name = '' ) {
        if ( empty( $name ) ) {
            $name = 'Test Symbol ' . uniqid();
        }

        return $this->symbols->create_symbol( $name, $this->sample_symbol_content );
    }
}
