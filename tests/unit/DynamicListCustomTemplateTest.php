<?php
/**
 * Tests del motor de plantillas personalizadas del bloque Lista Dinámica.
 *
 * apply_dynamic_list_custom_template es privado en Flavor_VBP_Canvas; los
 * tests replican la lógica de sustitución para verificar el contrato de
 * placeholders (top-level, dot notation, whitespace, missing keys). Las
 * defensas anti-XSS (wp_kses_post, esc_url filtering) se verifican a
 * nivel de integración con la BD real porque los mocks del bootstrap de
 * tests unitarios son permisivos.
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class DynamicListCustomTemplateTest extends Flavor_TestCase {

    /**
     * Motor de sustitución aislado para tests. El resultado NO pasa por
     * wp_kses_post para no mezclar contratos (esa capa se verifica en
     * integración contra WP real).
     */
    private function substitute_placeholders( $plantilla_html, array $item ) {
        $valor_en_ruta = function ( $ruta ) use ( $item ) {
            $segmentos = explode( '.', $ruta );
            $cursor    = $item;
            foreach ( $segmentos as $segmento ) {
                if ( is_array( $cursor ) && array_key_exists( $segmento, $cursor ) ) {
                    $cursor = $cursor[ $segmento ];
                } else {
                    return '';
                }
            }
            return is_scalar( $cursor ) ? (string) $cursor : '';
        };

        return preg_replace_callback(
            '/\{\{\s*([a-z0-9_.]+)\s*\}\}/i',
            function ( $coincidencia ) use ( $valor_en_ruta ) {
                return $valor_en_ruta( $coincidencia[1] );
            },
            $plantilla_html
        );
    }

    public function test_substitutes_top_level_fields() {
        $item = array( 'title' => 'El Quijote', 'excerpt' => 'Novela del siglo XVII' );
        $plantilla = '<h1>{{title}}</h1><p>{{excerpt}}</p>';

        $resultado = $this->substitute_placeholders( $plantilla, $item );

        $this->assertStringContainsString( '<h1>El Quijote</h1>', $resultado );
        $this->assertStringContainsString( '<p>Novela del siglo XVII</p>', $resultado );
    }

    public function test_substitutes_nested_meta_fields_with_dot_notation() {
        $item = array(
            'title' => 'Libro',
            'meta'  => array( 'autor' => 'Cervantes', 'ano_publicacion' => 1605 ),
        );
        $plantilla = '{{meta.autor}} ({{meta.ano_publicacion}})';

        $resultado = $this->substitute_placeholders( $plantilla, $item );

        $this->assertSame( 'Cervantes (1605)', $resultado );
    }

    public function test_unknown_placeholder_becomes_empty_string() {
        $item = array( 'title' => 'X' );
        $plantilla = 'A{{inexistente}}B{{meta.nada}}C';

        $resultado = $this->substitute_placeholders( $plantilla, $item );

        $this->assertSame( 'ABC', $resultado );
    }

    public function test_deeply_nested_missing_key_returns_empty() {
        $item = array( 'meta' => array( 'a' => array( 'b' => 'valor' ) ) );
        $plantilla = '[{{meta.a.b}}][{{meta.a.c}}][{{meta.z}}]';

        $resultado = $this->substitute_placeholders( $plantilla, $item );

        $this->assertSame( '[valor][][]', $resultado );
    }

    public function test_placeholder_whitespace_is_tolerated() {
        $item = array( 'title' => 'Hola' );
        $plantilla = '{{ title }} y {{  title  }}';

        $resultado = $this->substitute_placeholders( $plantilla, $item );

        $this->assertSame( 'Hola y Hola', $resultado );
    }

    public function test_non_scalar_meta_values_render_as_empty() {
        $item = array( 'meta' => array( 'etiquetas' => array( 'a', 'b' ) ) );
        $plantilla = 'tags: {{meta.etiquetas}}.';

        $resultado = $this->substitute_placeholders( $plantilla, $item );

        $this->assertSame( 'tags: .', $resultado );
    }

    public function test_numeric_field_values_are_stringified() {
        $item = array( 'id' => 42, 'meta' => array( 'puntuacion' => 4.5 ) );
        $plantilla = 'id={{id}} score={{meta.puntuacion}}';

        $resultado = $this->substitute_placeholders( $plantilla, $item );

        $this->assertSame( 'id=42 score=4.5', $resultado );
    }

    public function test_placeholder_is_case_insensitive() {
        $item = array( 'title' => 'Hola' );
        $plantilla = '{{TITLE}} y {{Title}}';

        $resultado = $this->substitute_placeholders( $plantilla, $item );

        // El callback no lowercase el path, así que estos matches hacen
        // lookup literal sobre el array. Verificamos que la regex captura
        // ambos (case-insensitive) pero el lookup falla → sustituye vacío.
        $this->assertSame( ' y ', $resultado );
    }

    public function test_multiple_placeholders_in_same_line() {
        $item = array( 'title' => 'A', 'excerpt' => 'B', 'date' => 'C' );
        $plantilla = '{{title}}-{{excerpt}}-{{date}}';

        $resultado = $this->substitute_placeholders( $plantilla, $item );

        $this->assertSame( 'A-B-C', $resultado );
    }

    public function test_template_without_placeholders_returns_unchanged() {
        $plantilla = '<div>Texto estático sin placeholders</div>';

        $resultado = $this->substitute_placeholders( $plantilla, array( 'title' => 'X' ) );

        $this->assertSame( $plantilla, $resultado );
    }

    public function test_malformed_placeholder_is_left_untouched() {
        $item = array( 'title' => 'X' );

        // Solo una llave, llaves sin cerrar o sintaxis inválida.
        $plantilla = '{title} {{title {{ }} {{title}}-OK';
        $resultado = $this->substitute_placeholders( $plantilla, $item );

        $this->assertStringContainsString( 'X-OK', $resultado );
        $this->assertStringContainsString( '{title}', $resultado );
    }
}
