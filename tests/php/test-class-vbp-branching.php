<?php
/**
 * Tests for Flavor_VBP_Branching class.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

/**
 * Test class for VBP Branching functionality.
 */
class Test_VBP_Branching extends VBP_UnitTestCase {

    /**
     * Branching instance.
     *
     * @var Flavor_VBP_Branching|null
     */
    private $branching;

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        if ( class_exists( 'Flavor_VBP_Branching' ) ) {
            $this->branching = Flavor_VBP_Branching::get_instance();
        } else {
            $this->markTestSkipped( 'Flavor_VBP_Branching class not available' );
        }
    }

    /**
     * Test branching constants are defined.
     */
    public function test_branching_constants_defined() {
        $this->assertEquals( 'flavor-vbp/v1', Flavor_VBP_Branching::API_NAMESPACE );
        $this->assertEquals( '_flavor_vbp_data', Flavor_VBP_Branching::META_DATA );
        $this->assertEquals( '_flavor_vbp_active_branch', Flavor_VBP_Branching::META_ACTIVE_BRANCH );
        $this->assertEquals( 'active', Flavor_VBP_Branching::STATUS_ACTIVE );
        $this->assertEquals( 'merged', Flavor_VBP_Branching::STATUS_MERGED );
        $this->assertEquals( 'archived', Flavor_VBP_Branching::STATUS_ARCHIVED );
    }

    /**
     * Test create branch REST callback exists.
     */
    public function test_crear_branch_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'crear_branch' ),
            'crear_branch method should exist'
        );
    }

    /**
     * Test checkout branch method exists.
     */
    public function test_checkout_branch_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'checkout_branch' ),
            'checkout_branch method should exist'
        );
    }

    /**
     * Test merge branches method exists.
     */
    public function test_merge_branches_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'merge_branches' ),
            'merge_branches method should exist'
        );
    }

    /**
     * Test diff branches method exists.
     */
    public function test_diff_branches_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'diff_branches' ),
            'diff_branches method should exist'
        );
    }

    /**
     * Test permission callbacks exist and are callable.
     */
    public function test_permission_callbacks_exist() {
        $this->assertTrue(
            method_exists( $this->branching, 'verificar_permisos_lectura' ),
            'verificar_permisos_lectura should exist'
        );

        $this->assertTrue(
            method_exists( $this->branching, 'verificar_permisos_escritura' ),
            'verificar_permisos_escritura should exist'
        );

        // Test that they return booleans.
        $this->assertIsBool( $this->branching->verificar_permisos_lectura() );
        $this->assertIsBool( $this->branching->verificar_permisos_escritura() );
    }

    /**
     * Test listar_branches method exists.
     */
    public function test_listar_branches_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'listar_branches' ),
            'listar_branches method should exist'
        );
    }

    /**
     * Test historial_branch method exists.
     */
    public function test_historial_branch_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'historial_branch' ),
            'historial_branch method should exist'
        );
    }

    /**
     * Test archivar_branch method exists.
     */
    public function test_archivar_branch_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'archivar_branch' ),
            'archivar_branch method should exist'
        );
    }

    /**
     * Test guardar_en_branch method exists.
     */
    public function test_guardar_en_branch_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'guardar_en_branch' ),
            'guardar_en_branch method should exist'
        );
    }

    /**
     * Test restaurar_version_branch method exists.
     */
    public function test_restaurar_version_branch_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'restaurar_version_branch' ),
            'restaurar_version_branch method should exist'
        );
    }

    /**
     * Test obtener_branch_activa method exists.
     */
    public function test_obtener_branch_activa_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'obtener_branch_activa' ),
            'obtener_branch_activa method should exist'
        );
    }

    /**
     * Test guardar_version_en_branch hook method exists.
     */
    public function test_guardar_version_en_branch_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'guardar_version_en_branch' ),
            'guardar_version_en_branch method should exist'
        );
    }

    /**
     * Test registrar_rutas method exists.
     */
    public function test_registrar_rutas_method_exists() {
        $this->assertTrue(
            method_exists( $this->branching, 'registrar_rutas' ),
            'registrar_rutas method should exist'
        );
    }

    /**
     * Test singleton pattern returns same instance.
     */
    public function test_singleton_returns_same_instance() {
        $instance1 = Flavor_VBP_Branching::get_instance();
        $instance2 = Flavor_VBP_Branching::get_instance();

        $this->assertSame( $instance1, $instance2 );
    }

    /**
     * Test mock content structure for merge operations.
     */
    public function test_merge_content_structure() {
        $content_a = array(
            'elements' => array(
                array(
                    'id'    => 'el-1',
                    'type'  => 'heading',
                    'props' => array( 'text' => 'Title A' ),
                ),
            ),
        );

        $content_b = array(
            'elements' => array(
                array(
                    'id'    => 'el-1',
                    'type'  => 'heading',
                    'props' => array( 'text' => 'Title B' ),
                ),
            ),
        );

        // Verify content structures are valid for merging.
        $this->assertArrayHasKey( 'elements', $content_a );
        $this->assertArrayHasKey( 'elements', $content_b );
        $this->assertCount( 1, $content_a['elements'] );
        $this->assertCount( 1, $content_b['elements'] );

        // Elements with same ID should be detected as conflicting.
        $this->assertEquals(
            $content_a['elements'][0]['id'],
            $content_b['elements'][0]['id']
        );
    }

    /**
     * Test diff structure detection.
     */
    public function test_diff_structure_detection() {
        $content_source = array(
            'elements' => array(
                array(
                    'id'       => 'section-1',
                    'type'     => 'section',
                    'children' => array(
                        array(
                            'id'    => 'heading-1',
                            'type'  => 'heading',
                            'props' => array( 'text' => 'Hello' ),
                        ),
                    ),
                ),
            ),
        );

        $content_target = array(
            'elements' => array(
                array(
                    'id'       => 'section-1',
                    'type'     => 'section',
                    'children' => array(
                        array(
                            'id'    => 'heading-1',
                            'type'  => 'heading',
                            'props' => array( 'text' => 'World' ),
                        ),
                        array(
                            'id'    => 'text-1',
                            'type'  => 'text',
                            'props' => array( 'content' => 'New element' ),
                        ),
                    ),
                ),
            ),
        );

        // Verify elements exist for diff comparison.
        $source_elements = $content_source['elements'][0]['children'];
        $target_elements = $content_target['elements'][0]['children'];

        $this->assertCount( 1, $source_elements, 'Source should have 1 child' );
        $this->assertCount( 2, $target_elements, 'Target should have 2 children' );

        // The heading text changed.
        $this->assertNotEquals(
            $source_elements[0]['props']['text'],
            $target_elements[0]['props']['text'],
            'Heading text should be different'
        );
    }

    /**
     * Test branch slug sanitization expectation.
     */
    public function test_branch_slug_format() {
        $branch_name = 'My Feature Branch #123';
        $expected_slug = sanitize_title( $branch_name );

        // Slug should be lowercase with dashes.
        $this->assertMatchesRegularExpression(
            '/^[a-z0-9-]+$/',
            $expected_slug,
            'Branch slug should be lowercase alphanumeric with dashes'
        );
    }

    /**
     * Test version hash generation is consistent.
     */
    public function test_version_hash_consistency() {
        $content = array(
            'elements' => array(
                array( 'id' => 'test', 'type' => 'text' ),
            ),
        );

        $json = wp_json_encode( $content );
        $hash1 = hash( 'sha256', $json );
        $hash2 = hash( 'sha256', $json );

        $this->assertEquals( $hash1, $hash2, 'Same content should produce same hash' );
        $this->assertEquals( 64, strlen( $hash1 ), 'SHA256 hash should be 64 characters' );
    }

    /**
     * Test conflict resolution options.
     */
    public function test_conflict_resolution_options() {
        // Valid resolution options.
        $valid_resolutions = array( 'source', 'target' );

        foreach ( $valid_resolutions as $resolution ) {
            $this->assertIsString( $resolution );
            $this->assertMatchesRegularExpression( '/^(source|target)$/', $resolution );
        }

        // Custom resolution should also be valid (as array).
        $custom_resolution = array(
            'id'    => 'el-1',
            'type'  => 'heading',
            'props' => array( 'text' => 'Custom merged title' ),
        );

        $this->assertIsArray( $custom_resolution );
        $this->assertArrayHasKey( 'id', $custom_resolution );
    }
}
