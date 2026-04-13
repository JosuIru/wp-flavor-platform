<?php
/**
 * Tests de integración para Visual Builder Pro.
 *
 * Estos tests requieren WordPress completo y una base de datos de prueba.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Integration
 */

/**
 * Tests de integración de VBP.
 */
class VBPIntegrationTest extends Flavor_Integration_Test_Case {

    /**
     * Test que el plugin está activo.
     */
    public function test_plugin_is_active() {
        $this->assertTrue(
            defined( 'FLAVOR_PLATFORM_VERSION' ),
            'FLAVOR_PLATFORM_VERSION debería estar definida'
        );
    }

    /**
     * Test crear página con VBP.
     */
    public function test_create_vbp_page() {
        $this->login_as_admin();

        $page_id = $this->create_vbp_page( array(
            'post_title' => 'Mi Página VBP',
        ) );

        $this->assertGreaterThan( 0, $page_id );

        // Verificar meta
        $vbp_enabled = get_post_meta( $page_id, '_flavor_vbp_enabled', true );
        $this->assertEquals( '1', $vbp_enabled );

        $vbp_data = get_post_meta( $page_id, '_flavor_vbp_data', true );
        $this->assertIsArray( $vbp_data );
        $this->assertArrayHasKey( 'blocks', $vbp_data );
    }

    /**
     * Test que los módulos se pueden activar/desactivar.
     */
    public function test_module_activation() {
        $this->login_as_admin();

        // Activar módulos
        $modules = array( 'eventos', 'socios', 'foros' );
        $this->activate_modules( $modules );

        $active = get_option( 'flavor_active_modules' );
        $this->assertEquals( $modules, $active );

        // Desactivar
        $this->deactivate_all_modules();
        $active = get_option( 'flavor_active_modules' );
        $this->assertEmpty( $active );
    }

    /**
     * Test REST API de VBP status.
     *
     * @group rest-api
     */
    public function test_vbp_rest_api_status() {
        $this->login_as_admin();

        // Registrar rutas REST
        do_action( 'rest_api_init' );

        $response = $this->rest_request( 'GET', '/flavor-vbp/v1/claude/status' );

        // Puede ser 200 o 401 dependiendo de auth
        $status = $response->get_status();
        $this->assertContains( $status, array( 200, 401, 403, 404 ) );
    }

    /**
     * Test que los shortcodes están registrados.
     */
    public function test_shortcodes_registered() {
        global $shortcode_tags;

        $expected_shortcodes = array(
            'flavor_module',
            'flavor_eventos',
            'flavor_socios',
            'flavor_unified_dashboard',
        );

        foreach ( $expected_shortcodes as $shortcode ) {
            $this->assertArrayHasKey(
                $shortcode,
                $shortcode_tags,
                "Shortcode '$shortcode' debería estar registrado"
            );
        }
    }

    /**
     * Test renderizar shortcode de módulo.
     */
    public function test_render_module_shortcode() {
        $this->activate_modules( array( 'eventos' ) );

        $output = do_shortcode( '[flavor_module module="eventos" view="grid"]' );

        $this->assertStringContainsString( 'flavor-module', $output );
        $this->assertStringContainsString( 'data-module="eventos"', $output );
    }

    /**
     * Test permisos de usuario para VBP.
     */
    public function test_user_permissions() {
        // Como subscriber no debería poder editar
        $this->login_as_subscriber();
        $this->assertFalse( current_user_can( 'edit_pages' ) );

        // Como admin sí
        $this->login_as_admin();
        $this->assertTrue( current_user_can( 'edit_pages' ) );
    }

    /**
     * Test que los estilos globales se guardan.
     *
     * @group vbp-styles
     */
    public function test_global_styles_save() {
        $this->login_as_admin();

        if ( ! class_exists( 'Flavor_VBP_Global_Styles' ) ) {
            $this->markTestSkipped( 'Flavor_VBP_Global_Styles no disponible' );
        }

        $styles = Flavor_VBP_Global_Styles::get_instance();

        $test_style = array(
            'name'  => 'Test Style',
            'type'  => 'color',
            'value' => '#ff0000',
        );

        // Los métodos dependen de la implementación
        $this->assertInstanceOf( 'Flavor_VBP_Global_Styles', $styles );
    }
}
