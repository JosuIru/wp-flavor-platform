<?php
/**
 * Clase base para tests de integración de Flavor Platform.
 *
 * Extiende WP_UnitTestCase para proveer helpers específicos del plugin.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

/**
 * Clase base para tests de integración.
 */
class Flavor_Integration_Test_Case extends WP_UnitTestCase {

    /**
     * Usuario admin para tests.
     *
     * @var int
     */
    protected static $admin_id;

    /**
     * Usuario subscriber para tests.
     *
     * @var int
     */
    protected static $subscriber_id;

    /**
     * Setup antes de todos los tests de la clase.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        // Crear usuarios de prueba
        self::$admin_id = self::factory()->user->create( array(
            'role' => 'administrator',
        ) );

        self::$subscriber_id = self::factory()->user->create( array(
            'role' => 'subscriber',
        ) );
    }

    /**
     * Setup antes de cada test.
     */
    public function setUp(): void {
        parent::setUp();

        // Limpiar transients
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );

        // Resetear usuario actual
        wp_set_current_user( 0 );
    }

    /**
     * Teardown después de cada test.
     */
    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Helper: Login como admin.
     */
    protected function login_as_admin() {
        wp_set_current_user( self::$admin_id );
    }

    /**
     * Helper: Login como subscriber.
     */
    protected function login_as_subscriber() {
        wp_set_current_user( self::$subscriber_id );
    }

    /**
     * Helper: Logout.
     */
    protected function logout() {
        wp_set_current_user( 0 );
    }

    /**
     * Helper: Crear una página VBP de prueba.
     *
     * @param array $args Argumentos opcionales.
     * @return int Post ID.
     */
    protected function create_vbp_page( $args = array() ) {
        $defaults = array(
            'post_title'   => 'Test VBP Page ' . uniqid(),
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        $args = wp_parse_args( $args, $defaults );
        $post_id = self::factory()->post->create( $args );

        // Añadir meta de VBP
        update_post_meta( $post_id, '_flavor_vbp_enabled', '1' );
        update_post_meta( $post_id, '_flavor_vbp_data', array(
            'blocks' => array(),
            'styles' => array(),
        ) );

        return $post_id;
    }

    /**
     * Helper: Crear un símbolo VBP de prueba.
     *
     * @param string $name    Nombre del símbolo.
     * @param array  $content Contenido del símbolo.
     * @return int|WP_Error Symbol ID o error.
     */
    protected function create_vbp_symbol( $name, $content = array() ) {
        if ( ! class_exists( 'Flavor_VBP_Symbols' ) ) {
            $this->markTestSkipped( 'Flavor_VBP_Symbols no disponible' );
        }

        if ( empty( $content ) ) {
            $content = array(
                'type'  => 'container',
                'props' => array( 'className' => 'test-symbol' ),
                'children' => array(
                    array(
                        'type'  => 'text',
                        'props' => array( 'content' => 'Test content' ),
                    ),
                ),
            );
        }

        $symbols = Flavor_VBP_Symbols::get_instance();
        return $symbols->create_symbol( $name, $content );
    }

    /**
     * Helper: Hacer una request REST simulada.
     *
     * @param string $method   HTTP method.
     * @param string $route    Route (ej: '/flavor-vbp/v1/pages').
     * @param array  $params   Parámetros.
     * @return WP_REST_Response
     */
    protected function rest_request( $method, $route, $params = array() ) {
        $request = new WP_REST_Request( $method, $route );

        foreach ( $params as $key => $value ) {
            $request->set_param( $key, $value );
        }

        return rest_do_request( $request );
    }

    /**
     * Helper: Activar módulos de Flavor Platform.
     *
     * @param array $modules Lista de IDs de módulos.
     */
    protected function activate_modules( $modules ) {
        update_option( 'flavor_active_modules', $modules );
    }

    /**
     * Helper: Desactivar todos los módulos.
     */
    protected function deactivate_all_modules() {
        update_option( 'flavor_active_modules', array() );
    }

    /**
     * Assert que una respuesta REST es exitosa.
     *
     * @param WP_REST_Response $response Respuesta.
     * @param int              $status   Código esperado (default 200).
     */
    protected function assertRestSuccess( $response, $status = 200 ) {
        $this->assertEquals( $status, $response->get_status(),
            'REST response status: ' . print_r( $response->get_data(), true )
        );
    }

    /**
     * Assert que una respuesta REST es un error.
     *
     * @param WP_REST_Response $response Respuesta.
     * @param int              $status   Código esperado (default 400).
     */
    protected function assertRestError( $response, $status = 400 ) {
        $this->assertGreaterThanOrEqual( 400, $response->get_status() );
        if ( $status ) {
            $this->assertEquals( $status, $response->get_status() );
        }
    }
}
