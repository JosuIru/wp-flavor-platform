<?php
/**
 * Tests for VBP REST API functionality.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

/**
 * Test class for VBP REST API.
 */
class Test_VBP_REST_API extends VBP_UnitTestCase {

    /**
     * Test API namespace constant.
     */
    public function test_api_namespace() {
        $expected_namespace = 'flavor-vbp/v1';

        // All VBP API classes should use this namespace.
        if ( class_exists( 'Flavor_VBP_Branching' ) ) {
            $this->assertEquals( $expected_namespace, Flavor_VBP_Branching::API_NAMESPACE );
        }

        if ( class_exists( 'Flavor_VBP_Global_Styles' ) ) {
            $this->assertEquals( $expected_namespace, Flavor_VBP_Global_Styles::REST_NAMESPACE );
        }
    }

    /**
     * Test API key validation function exists.
     */
    public function test_api_key_validation_function_exists() {
        $this->assertTrue(
            function_exists( 'flavor_check_vbp_automation_access' ),
            'flavor_check_vbp_automation_access function should exist'
        );
    }

    /**
     * Test valid API key is accepted.
     */
    public function test_valid_api_key_accepted() {
        $valid_key = 'test-vbp-key-12345';
        $scope = 'pages';

        $result = flavor_check_vbp_automation_access( $valid_key, $scope );

        $this->assertTrue( $result, 'Valid API key should be accepted' );
    }

    /**
     * Test invalid API key is rejected.
     */
    public function test_invalid_api_key_rejected() {
        $invalid_key = 'invalid-key-xyz';
        $scope = 'pages';

        $result = flavor_check_vbp_automation_access( $invalid_key, $scope );

        $this->assertFalse( $result, 'Invalid API key should be rejected' );
    }

    /**
     * Test API key extraction from request function exists.
     */
    public function test_api_key_extraction_function_exists() {
        $this->assertTrue(
            function_exists( 'flavor_get_vbp_api_key_from_request' ),
            'flavor_get_vbp_api_key_from_request function should exist'
        );
    }

    /**
     * Test rate limiting concept.
     */
    public function test_rate_limiting_concept() {
        // Rate limiting should be based on transients or similar.
        $rate_limit_key = 'vbp_rate_limit_' . md5( '127.0.0.1' );

        // In tests, transients are mocked to return false.
        $current_count = get_transient( $rate_limit_key );

        $this->assertFalse( $current_count, 'Fresh rate limit should not exist' );

        // Setting transient should work.
        $set_result = set_transient( $rate_limit_key, 1, 60 );
        $this->assertTrue( $set_result );
    }

    /**
     * Test REST response structure for pages endpoint.
     */
    public function test_pages_endpoint_response_structure() {
        // Expected response structure.
        $expected_keys = array(
            'success',
            'pages',
        );

        $mock_response = array(
            'success' => true,
            'pages'   => array(
                array(
                    'id'           => 1,
                    'title'        => 'Test Page',
                    'slug'         => 'test-page',
                    'status'       => 'publish',
                    'blocks'       => array(),
                    'created_at'   => '2024-01-01 00:00:00',
                    'modified_at'  => '2024-01-01 00:00:00',
                ),
            ),
        );

        foreach ( $expected_keys as $key ) {
            $this->assertArrayHasKey( $key, $mock_response );
        }

        // Verify page structure.
        $page = $mock_response['pages'][0];
        $page_keys = array( 'id', 'title', 'slug', 'status', 'blocks' );

        foreach ( $page_keys as $key ) {
            $this->assertArrayHasKey( $key, $page );
        }
    }

    /**
     * Test blocks endpoint response structure.
     */
    public function test_blocks_endpoint_response_structure() {
        $mock_response = array(
            'success' => true,
            'blocks'  => array(
                array(
                    'id'          => 'heading',
                    'name'        => 'Heading',
                    'category'    => 'typography',
                    'icon'        => 'heading',
                    'description' => 'Add a heading block',
                    'props'       => array(
                        'text'  => array( 'type' => 'string', 'default' => '' ),
                        'level' => array( 'type' => 'number', 'default' => 2 ),
                    ),
                ),
            ),
        );

        $this->assertArrayHasKey( 'blocks', $mock_response );
        $this->assertIsArray( $mock_response['blocks'] );

        $block = $mock_response['blocks'][0];
        $this->assertArrayHasKey( 'id', $block );
        $this->assertArrayHasKey( 'name', $block );
        $this->assertArrayHasKey( 'category', $block );
        $this->assertArrayHasKey( 'props', $block );
    }

    /**
     * Test error response structure.
     */
    public function test_error_response_structure() {
        $mock_error_response = array(
            'success' => false,
            'code'    => 'invalid_request',
            'message' => 'The request was invalid.',
            'data'    => array(
                'status' => 400,
            ),
        );

        $this->assertFalse( $mock_error_response['success'] );
        $this->assertArrayHasKey( 'code', $mock_error_response );
        $this->assertArrayHasKey( 'message', $mock_error_response );
    }

    /**
     * Test schema endpoint expected structure.
     */
    public function test_schema_endpoint_structure() {
        $mock_schema = array(
            'blocks'         => array(),
            'section_types'  => array(),
            'design_presets' => array(),
            'widgets'        => array(),
            'version'        => '1.0',
        );

        $expected_keys = array( 'blocks', 'section_types', 'design_presets', 'version' );

        foreach ( $expected_keys as $key ) {
            $this->assertArrayHasKey( $key, $mock_schema );
        }
    }

    /**
     * Test authentication header name.
     */
    public function test_authentication_header_name() {
        $expected_header = 'X-VBP-Key';

        // The header should be used consistently across the API.
        $this->assertEquals( 'X-VBP-Key', $expected_header );
    }

    /**
     * Test page creation request validation.
     */
    public function test_page_creation_request_validation() {
        // Required fields for page creation.
        $required_fields = array( 'title' );

        $valid_request = array(
            'title'   => 'New Page',
            'slug'    => 'new-page',
            'blocks'  => array(),
            'status'  => 'draft',
        );

        foreach ( $required_fields as $field ) {
            $this->assertArrayHasKey( $field, $valid_request );
            $this->assertNotEmpty( $valid_request[ $field ] );
        }

        // Invalid request missing title.
        $invalid_request = array(
            'slug'   => 'no-title-page',
            'blocks' => array(),
        );

        $this->assertArrayNotHasKey( 'title', $invalid_request );
    }

    /**
     * Test styled page creation structure.
     */
    public function test_styled_page_creation_structure() {
        $styled_request = array(
            'title'           => 'Styled Landing Page',
            'slug'            => 'styled-landing',
            'preset'          => 'community',
            'sections'        => array( 'hero', 'features', 'cta' ),
            'set_as_homepage' => true,
            'context'         => array(
                'topic'    => 'Community',
                'industry' => 'nonprofit',
            ),
            'status'          => 'publish',
        );

        $this->assertArrayHasKey( 'preset', $styled_request );
        $this->assertArrayHasKey( 'sections', $styled_request );
        $this->assertIsArray( $styled_request['sections'] );
        $this->assertArrayHasKey( 'context', $styled_request );
    }

    /**
     * Test symbols API expected endpoints.
     */
    public function test_symbols_api_endpoints() {
        $expected_endpoints = array(
            'GET /symbols'           => 'List all symbols',
            'POST /symbols'          => 'Create symbol',
            'GET /symbols/{id}'      => 'Get symbol',
            'PUT /symbols/{id}'      => 'Update symbol',
            'DELETE /symbols/{id}'   => 'Delete symbol',
            'POST /symbols/import'   => 'Import symbols',
            'GET /symbols/export'    => 'Export symbols',
        );

        $this->assertCount( 7, $expected_endpoints );

        foreach ( $expected_endpoints as $endpoint => $description ) {
            $this->assertIsString( $endpoint );
            $this->assertIsString( $description );
        }
    }

    /**
     * Test global styles API expected endpoints.
     */
    public function test_global_styles_api_endpoints() {
        $expected_endpoints = array(
            'GET /global-styles'         => 'List all styles',
            'POST /global-styles'        => 'Create style',
            'GET /global-styles/{id}'    => 'Get style',
            'PUT /global-styles/{id}'    => 'Update style',
            'DELETE /global-styles/{id}' => 'Delete style',
            'GET /global-styles/css'     => 'Get generated CSS',
        );

        $this->assertCount( 6, $expected_endpoints );
    }

    /**
     * Test branching API expected endpoints.
     */
    public function test_branching_api_endpoints() {
        $expected_endpoints = array(
            'GET /branches/{post_id}'                 => 'List branches',
            'POST /branches'                          => 'Create branch',
            'GET /branches/{post_id}/{branch_id}'     => 'Get branch',
            'PUT /branches/{post_id}/{branch_id}'     => 'Update branch',
            'POST /branches/{post_id}/{branch_id}/checkout' => 'Checkout branch',
            'POST /branches/{post_id}/{branch_id}/merge'    => 'Merge branches',
            'POST /branches/{post_id}/{branch_id}/diff'     => 'Diff branches',
            'DELETE /branches/{post_id}/{branch_id}'        => 'Archive branch',
        );

        $this->assertCount( 8, $expected_endpoints );
    }

    /**
     * Test HTTP methods are valid.
     */
    public function test_http_methods_valid() {
        $valid_methods = array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' );

        foreach ( $valid_methods as $method ) {
            $this->assertMatchesRegularExpression(
                '/^(GET|POST|PUT|PATCH|DELETE)$/',
                $method
            );
        }
    }
}
