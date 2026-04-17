<?php
/**
 * Tests de Flavor_VBP_Query_Signature.
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/visual-builder-pro/collections/class-query-signature.php';

class QuerySignatureTest extends Flavor_TestCase {

    public function test_sign_returns_same_hash_for_same_input() {
        $firma_a = Flavor_VBP_Query_Signature::sign( 'eventos', array( 'estado' => 'publicado', 'limit' => 10 ) );
        $firma_b = Flavor_VBP_Query_Signature::sign( 'eventos', array( 'estado' => 'publicado', 'limit' => 10 ) );

        $this->assertSame( $firma_a, $firma_b );
        $this->assertNotEmpty( $firma_a );
    }

    public function test_sign_is_stable_against_key_ordering() {
        // La serialización canónica ordena las claves, así que dos args con
        // el mismo contenido pero distinto orden deben firmar igual.
        $firma_ordenada   = Flavor_VBP_Query_Signature::sign( 'eventos', array( 'estado' => 'publicado', 'limit' => 10 ) );
        $firma_desordenada = Flavor_VBP_Query_Signature::sign( 'eventos', array( 'limit' => 10, 'estado' => 'publicado' ) );

        $this->assertSame( $firma_ordenada, $firma_desordenada );
    }

    public function test_sign_ignores_page_field() {
        // 'page' debe excluirse: dos firmas con distinta página pero mismo
        // resto de args son idénticas (así la misma firma sirve para paginar).
        $firma_pagina_1 = Flavor_VBP_Query_Signature::sign( 'eventos', array( 'estado' => 'publicado', 'page' => 1 ) );
        $firma_pagina_5 = Flavor_VBP_Query_Signature::sign( 'eventos', array( 'estado' => 'publicado', 'page' => 5 ) );

        $this->assertSame( $firma_pagina_1, $firma_pagina_5 );
    }

    public function test_different_source_produces_different_signature() {
        $firma_eventos = Flavor_VBP_Query_Signature::sign( 'eventos', array( 'estado' => 'publicado' ) );
        $firma_socios  = Flavor_VBP_Query_Signature::sign( 'socios',  array( 'estado' => 'publicado' ) );

        $this->assertNotSame( $firma_eventos, $firma_socios );
    }

    public function test_different_args_produce_different_signature() {
        $firma_publicado = Flavor_VBP_Query_Signature::sign( 'eventos', array( 'estado' => 'publicado' ) );
        $firma_borrador  = Flavor_VBP_Query_Signature::sign( 'eventos', array( 'estado' => 'borrador' ) );

        $this->assertNotSame( $firma_publicado, $firma_borrador );
    }

    public function test_verify_accepts_matching_signature() {
        $args = array( 'estado' => 'publicado', 'limit' => 10 );
        $firma = Flavor_VBP_Query_Signature::sign( 'eventos', $args );

        $this->assertTrue( Flavor_VBP_Query_Signature::verify( 'eventos', $args, $firma ) );
    }

    public function test_verify_rejects_mismatching_source() {
        $args  = array( 'estado' => 'publicado' );
        $firma = Flavor_VBP_Query_Signature::sign( 'eventos', $args );

        $this->assertFalse( Flavor_VBP_Query_Signature::verify( 'socios', $args, $firma ) );
    }

    public function test_verify_rejects_mismatching_args() {
        $args_originales = array( 'estado' => 'publicado' );
        $firma           = Flavor_VBP_Query_Signature::sign( 'eventos', $args_originales );

        $args_manipulados = array( 'estado' => 'borrador' );

        $this->assertFalse( Flavor_VBP_Query_Signature::verify( 'eventos', $args_manipulados, $firma ) );
    }

    public function test_verify_rejects_empty_signature() {
        $args = array( 'estado' => 'publicado' );

        $this->assertFalse( Flavor_VBP_Query_Signature::verify( 'eventos', $args, '' ) );
        $this->assertFalse( Flavor_VBP_Query_Signature::verify( 'eventos', $args, null ) );
    }

    public function test_verify_allows_page_to_change() {
        // La firma se calcula sin 'page', así que verificar con una página
        // distinta a la del firmado debe aceptarse (es el caso de load more).
        $args_al_firmar = array( 'estado' => 'publicado', 'limit' => 10 );
        $firma          = Flavor_VBP_Query_Signature::sign( 'eventos', $args_al_firmar );

        $args_al_verificar = array( 'estado' => 'publicado', 'limit' => 10, 'page' => 3 );
        unset( $args_al_verificar['page'] );

        $this->assertTrue( Flavor_VBP_Query_Signature::verify( 'eventos', $args_al_verificar, $firma ) );
    }
}
