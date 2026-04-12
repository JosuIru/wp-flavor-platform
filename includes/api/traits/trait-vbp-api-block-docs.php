<?php
/**
 * Trait para Documentación de Bloques VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_BlockDocs {

    /**
     * Obtiene documentación de bloque
     */
    public function get_block_documentation( $request ) {
        $block_type = sanitize_text_field( $request->get_param( 'block_type' ) );
        $docs = $this->get_block_docs_data();

        if ( $block_type && isset( $docs[ $block_type ] ) ) {
            return new WP_REST_Response( array( 'success' => true, 'documentation' => $docs[ $block_type ] ), 200 );
        }

        return new WP_REST_Response( array( 'success' => true, 'documentation' => $docs ), 200 );
    }

    private function get_block_docs_data() {
        return array(
            'heading' => array(
                'name' => 'Heading',
                'description' => 'Bloque de encabezado (h1-h6)',
                'props' => array(
                    'text' => array( 'type' => 'string', 'required' => true, 'description' => 'Texto del encabezado' ),
                    'level' => array( 'type' => 'integer', 'required' => false, 'default' => 2, 'description' => 'Nivel (1-6)' ),
                    'align' => array( 'type' => 'string', 'required' => false, 'default' => 'left', 'options' => array( 'left', 'center', 'right' ) ),
                ),
            ),
            'text' => array(
                'name' => 'Text',
                'description' => 'Bloque de texto/párrafo',
                'props' => array(
                    'content' => array( 'type' => 'string', 'required' => true, 'description' => 'Contenido del texto' ),
                    'align' => array( 'type' => 'string', 'required' => false, 'default' => 'left' ),
                ),
            ),
            'image' => array(
                'name' => 'Image',
                'description' => 'Bloque de imagen',
                'props' => array(
                    'src' => array( 'type' => 'string', 'required' => true, 'description' => 'URL de la imagen' ),
                    'alt' => array( 'type' => 'string', 'required' => true, 'description' => 'Texto alternativo' ),
                    'width' => array( 'type' => 'string', 'required' => false, 'description' => 'Ancho' ),
                    'height' => array( 'type' => 'string', 'required' => false, 'description' => 'Alto' ),
                ),
            ),
            'button' => array(
                'name' => 'Button',
                'description' => 'Bloque de botón',
                'props' => array(
                    'text' => array( 'type' => 'string', 'required' => true, 'description' => 'Texto del botón' ),
                    'url' => array( 'type' => 'string', 'required' => false, 'description' => 'URL de destino' ),
                    'style' => array( 'type' => 'string', 'required' => false, 'default' => 'primary', 'options' => array( 'primary', 'secondary', 'outline', 'ghost' ) ),
                    'size' => array( 'type' => 'string', 'required' => false, 'default' => 'md', 'options' => array( 'sm', 'md', 'lg' ) ),
                ),
            ),
            'container' => array(
                'name' => 'Container',
                'description' => 'Contenedor de bloques',
                'props' => array(
                    'className' => array( 'type' => 'string', 'required' => false, 'description' => 'Clases CSS' ),
                ),
            ),
            'columns' => array(
                'name' => 'Columns',
                'description' => 'Layout de columnas',
                'props' => array(
                    'columns' => array( 'type' => 'integer', 'required' => false, 'default' => 2, 'description' => 'Número de columnas (1-6)' ),
                    'gap' => array( 'type' => 'string', 'required' => false, 'default' => '20px', 'description' => 'Espacio entre columnas' ),
                ),
            ),
            'section' => array(
                'name' => 'Section',
                'description' => 'Sección con fondo y padding',
                'props' => array(
                    'className' => array( 'type' => 'string', 'required' => false ),
                    'padding' => array( 'type' => 'string', 'required' => false, 'default' => '40px 20px' ),
                    'background' => array( 'type' => 'object', 'required' => false ),
                ),
            ),
        );
    }

    /**
     * Obtiene ejemplos de bloques
     */
    public function get_block_examples( $request ) {
        $block_type = sanitize_text_field( $request->get_param( 'block_type' ) );

        $examples = array(
            'heading' => array(
                array( 'name' => 'H1 centrado', 'code' => array( 'type' => 'heading', 'data' => array( 'text' => 'Título Principal', 'level' => 1, 'align' => 'center' ) ) ),
                array( 'name' => 'H2 simple', 'code' => array( 'type' => 'heading', 'data' => array( 'text' => 'Subtítulo', 'level' => 2 ) ) ),
            ),
            'button' => array(
                array( 'name' => 'Botón primario', 'code' => array( 'type' => 'button', 'data' => array( 'text' => 'Comenzar', 'style' => 'primary', 'url' => '#' ) ) ),
                array( 'name' => 'Botón outline', 'code' => array( 'type' => 'button', 'data' => array( 'text' => 'Ver más', 'style' => 'outline', 'url' => '#' ) ) ),
            ),
        );

        if ( $block_type && isset( $examples[ $block_type ] ) ) {
            return new WP_REST_Response( array( 'success' => true, 'examples' => $examples[ $block_type ] ), 200 );
        }

        return new WP_REST_Response( array( 'success' => true, 'examples' => $examples ), 200 );
    }

    /**
     * Busca bloques por funcionalidad
     */
    public function search_blocks_by_functionality( $request ) {
        $query = sanitize_text_field( $request->get_param( 'query' ) );

        $docs = $this->get_block_docs_data();
        $results = array();

        foreach ( $docs as $block_type => $doc ) {
            if ( stripos( $doc['name'], $query ) !== false || stripos( $doc['description'], $query ) !== false ) {
                $results[] = array( 'type' => $block_type, 'name' => $doc['name'], 'description' => $doc['description'] );
            }
        }

        return new WP_REST_Response( array( 'success' => true, 'results' => $results, 'query' => $query ), 200 );
    }

    // =============================================
}
