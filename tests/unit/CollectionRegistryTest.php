<?php
/**
 * Tests del sistema de colecciones de VBP (registry + sanitización).
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';

require_once FLAVOR_PLUGIN_DIR . '/includes/visual-builder-pro/collections/interface-collection-source.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/visual-builder-pro/collections/class-collection-registry.php';

/**
 * Fuente simulada que devuelve los query_args recibidos, para verificar que
 * el registry aplica sanitización antes de llegar a query().
 */
class Flavor_Fake_Collection_Source implements Flavor_VBP_Collection_Source {

    private $identifier;
    private $label;
    private $fields;

    public function __construct( $identifier, $label, $fields ) {
        $this->identifier = $identifier;
        $this->label      = $label;
        $this->fields     = $fields;
    }

    public function get_identifier() {
        return $this->identifier;
    }

    public function get_label() {
        return $this->label;
    }

    public function get_description() {
        return '';
    }

    public function get_query_fields() {
        return $this->fields;
    }

    public function query( array $query_args ) {
        return array( array( 'args_recibidos' => $query_args ) );
    }

    public function get_total_count( array $query_args ) {
        return 1;
    }
}

class CollectionRegistryTest extends Flavor_TestCase {

    protected function setUp(): void {
        parent::setUp();
        Flavor_VBP_Collection_Registry::reset_for_tests();
    }

    public function test_register_and_retrieve_source() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $source   = new Flavor_Fake_Collection_Source( 'eventos_fake', 'Eventos Fake', array() );

        $this->assertTrue( $registry->register( $source ) );
        $this->assertTrue( $registry->has( 'eventos_fake' ) );
        $this->assertSame( $source, $registry->get( 'eventos_fake' ) );
    }

    public function test_register_rejects_empty_identifier() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $source   = new Flavor_Fake_Collection_Source( '', 'Sin ID', array() );

        $this->assertFalse( $registry->register( $source ) );
        $this->assertSame( array(), $registry->all() );
    }

    public function test_unregister_removes_only_the_target() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $registry->register( new Flavor_Fake_Collection_Source( 'a', 'A', array() ) );
        $registry->register( new Flavor_Fake_Collection_Source( 'b', 'B', array() ) );

        $this->assertTrue( $registry->unregister( 'a' ) );
        $this->assertFalse( $registry->has( 'a' ) );
        $this->assertTrue( $registry->has( 'b' ) );
        $this->assertFalse( $registry->unregister( 'no_existe' ) );
    }

    public function test_all_returns_sources_sorted_alphabetically_by_label() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $registry->register( new Flavor_Fake_Collection_Source( 'x', 'Zebra', array() ) );
        $registry->register( new Flavor_Fake_Collection_Source( 'y', 'Alfa', array() ) );
        $registry->register( new Flavor_Fake_Collection_Source( 'z', 'manzana', array() ) );

        $etiquetas = array_map(
            static function ( Flavor_VBP_Collection_Source $s ) {
                return $s->get_label();
            },
            $registry->all()
        );

        $this->assertSame( array( 'Alfa', 'manzana', 'Zebra' ), $etiquetas );
    }

    public function test_to_public_array_exposes_metadata_only() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $registry->register( new Flavor_Fake_Collection_Source(
            'items',
            'Items',
            array( 'estado' => array( 'type' => 'enum', 'options' => array( 'on', 'off' ) ) )
        ) );

        $public = $registry->to_public_array();

        $this->assertCount( 1, $public );
        $this->assertSame( 'items', $public[0]['id'] );
        $this->assertSame( 'Items', $public[0]['label'] );
        $this->assertArrayHasKey( 'fields', $public[0] );
        $this->assertArrayHasKey( 'estado', $public[0]['fields'] );
    }

    public function test_sanitize_query_args_coerces_int_and_clamps_range() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $source   = new Flavor_Fake_Collection_Source(
            'items',
            'Items',
            array(
                'limit' => array( 'type' => 'int', 'min' => 1, 'max' => 20, 'default' => 5 ),
            )
        );

        $this->assertSame( array( 'limit' => 20 ), $registry->sanitize_query_args( $source, array( 'limit' => '99' ) ) );
        $this->assertSame( array( 'limit' => 1 ), $registry->sanitize_query_args( $source, array( 'limit' => '-5' ) ) );
        $this->assertSame( array( 'limit' => 5 ), $registry->sanitize_query_args( $source, array( 'limit' => 'abc' ) ) );
        $this->assertSame( array( 'limit' => 5 ), $registry->sanitize_query_args( $source, array() ) );
    }

    public function test_sanitize_query_args_validates_enum_against_whitelist() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $source   = new Flavor_Fake_Collection_Source(
            'items',
            'Items',
            array(
                'estado' => array(
                    'type'    => 'enum',
                    'options' => array( 'publicado', 'borrador' ),
                    'default' => 'publicado',
                ),
            )
        );

        $this->assertSame( array( 'estado' => 'borrador' ), $registry->sanitize_query_args( $source, array( 'estado' => 'borrador' ) ) );
        $this->assertSame( array( 'estado' => 'publicado' ), $registry->sanitize_query_args( $source, array( 'estado' => 'DROP TABLE' ) ) );
    }

    public function test_sanitize_query_args_validates_date_format() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $source   = new Flavor_Fake_Collection_Source(
            'items',
            'Items',
            array(
                'fecha' => array( 'type' => 'date', 'default' => null ),
            )
        );

        $this->assertSame( array( 'fecha' => '2026-04-17' ), $registry->sanitize_query_args( $source, array( 'fecha' => '2026-04-17' ) ) );
        $this->assertSame( array( 'fecha' => null ), $registry->sanitize_query_args( $source, array( 'fecha' => '17/04/2026' ) ) );
        $this->assertSame( array( 'fecha' => null ), $registry->sanitize_query_args( $source, array( 'fecha' => '2026-13-45' ) ) );
    }

    public function test_sanitize_query_args_drops_unknown_fields() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $source   = new Flavor_Fake_Collection_Source(
            'items',
            'Items',
            array(
                'estado' => array( 'type' => 'string', 'default' => 'on' ),
            )
        );

        $cleaned = $registry->sanitize_query_args( $source, array(
            'estado'           => 'off',
            'campo_inventado'  => 'valor_malicioso',
            'otro_desconocido' => 123,
        ) );

        $this->assertSame( array( 'estado' => 'off' ), $cleaned );
    }

    public function test_register_overrides_existing_source() {
        $registry = Flavor_VBP_Collection_Registry::get_instance();
        $original = new Flavor_Fake_Collection_Source( 'items', 'Original', array() );
        $override = new Flavor_Fake_Collection_Source( 'items', 'Override', array() );

        $registry->register( $original );
        $registry->register( $override );

        $this->assertSame( 'Override', $registry->get( 'items' )->get_label() );
        $this->assertCount( 1, $registry->all() );
    }

    // ===== invalidate_source_cache =====

    public function test_invalidate_source_cache_rejects_empty_identifier() {
        // Identifier vacío o que sanitize_key reduce a '' debe retornar 0
        // sin tocar la base de datos.
        $this->assertSame( 0, Flavor_VBP_Collection_Registry::invalidate_source_cache( '' ) );
        $this->assertSame( 0, Flavor_VBP_Collection_Registry::invalidate_source_cache( null ) );
        $this->assertSame( 0, Flavor_VBP_Collection_Registry::invalidate_source_cache( '!!!' ) );
    }
}
