<?php
/**
 * Trait para operaciones CRUD de páginas VBP
 *
 * Este trait contiene todos los métodos relacionados con la creación,
 * lectura, actualización y eliminación de páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Pages
 *
 * Contiene métodos para:
 * - Crear páginas (create_page, create_styled_page)
 * - Leer páginas (get_page, list_pages)
 * - Actualizar páginas (update_page)
 * - Duplicar páginas (duplicate_page)
 * - Publicar páginas (publish_page)
 * - Obtener URL (get_page_url)
 */
trait VBP_API_Pages {

    /**
     * Crea una nueva página VBP
     *
     * @param WP_REST_Request $request Request con parámetros.
     * @return WP_REST_Response
     */
    public function create_page( $request ) {
        $title = $request->get_param( 'title' );
        $elements = $request->get_param( 'elements' );
        $template = $request->get_param( 'template' );
        $status = $request->get_param( 'status' );
        $context = $request->get_param( 'context' ) ?: array();
        $settings = $request->get_param( 'settings' ) ?: array();
        $design_preset = $request->get_param( 'design_preset' );

        // Fase 2: Soporte para múltiples post_types
        $post_type = $request->get_param( 'post_type' ) ?: 'flavor_landing';
        if ( ! $this->is_supported_post_type( $post_type ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Post type no soportado: ' . $post_type,
                'supported_types' => $this->supported_post_types,
            ), 400 );
        }

        // Si hay template, obtener elementos predefinidos con contexto
        if ( $template && empty( $elements ) ) {
            // Usar el título de la página como topic si no se especifica
            if ( empty( $context['topic'] ) && ! empty( $title ) ) {
                $context['topic'] = $title;
            }
            $elements = $this->get_template_elements( $template, $context );
        }

        // Procesar elementos para asegurar estructura VBP completa
        $elements = $this->prepare_elements( $elements ?: array() );

        // Crear post con post_type especificado
        $post_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_type'   => $post_type,
            'post_status' => $status,
        ) );

        if ( is_wp_error( $post_id ) ) {
            return new WP_REST_Response( array( 'error' => $post_id->get_error_message() ), 500 );
        }

        // Settings por defecto
        $default_settings = array(
            'pageWidth'       => 1200,
            'backgroundColor' => '#ffffff',
        );

        // Aplicar design preset si se especifica
        if ( $design_preset ) {
            $preset_settings = $this->get_design_preset( $design_preset );
            if ( $preset_settings ) {
                $default_settings = array_merge( $default_settings, $preset_settings );
                $default_settings['design_preset'] = $design_preset;
            }
        }

        // Merge con settings enviados en la request (tienen prioridad)
        $final_settings = array_merge( $default_settings, $settings );

        // Guardar datos VBP
        $vbp_data = array(
            'version'  => '2.0.15',
            'elements' => $elements,
            'settings' => $final_settings,
        );

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );
        update_post_meta( $post_id, '_flavor_vbp_version', '2.0.15' );

        return new WP_REST_Response( array(
            'success'  => true,
            'id'       => $post_id,
            'title'    => $title,
            'status'   => $status,
            'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'view_url' => get_permalink( $post_id ),
        ), 201 );
    }

    /**
     * Obtiene una página VBP
     *
     * @param WP_REST_Request $request Request con ID de página.
     * @return WP_REST_Response
     */
    public function get_page( $request ) {
        $post_id = $request->get_param( 'id' );
        $post = get_post( $post_id );

        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada o no es un post type soportado',
                'supported_types' => $this->supported_post_types,
            ), 404 );
        }

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );

        return new WP_REST_Response( array(
            'success'  => true,
            'id'       => $post_id,
            'title'    => $post->post_title,
            'status'   => $post->post_status,
            'post_type' => $post->post_type,
            'elements' => $vbp_data['elements'] ?? array(),
            'settings' => $vbp_data['settings'] ?? array(),
            'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'view_url' => get_permalink( $post_id ),
        ) );
    }

    /**
     * Actualiza una página VBP existente
     *
     * @param WP_REST_Request $request Request con parámetros.
     * @return WP_REST_Response
     */
    public function update_page( $request ) {
        $post_id = $request->get_param( 'id' );
        $post = get_post( $post_id );

        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada o no es un post type soportado',
                'supported_types' => $this->supported_post_types,
            ), 404 );
        }

        $update_data = array( 'ID' => $post_id );

        // Actualizar título si se proporciona
        $title = $request->get_param( 'title' );
        if ( $title !== null ) {
            $update_data['post_title'] = $title;
        }

        // Actualizar estado si se proporciona
        $status = $request->get_param( 'status' );
        if ( $status !== null ) {
            $update_data['post_status'] = $status;
        }

        // Actualizar post si hay cambios
        if ( count( $update_data ) > 1 ) {
            wp_update_post( $update_data );
        }

        // Actualizar elementos VBP si se proporcionan
        $elements = $request->get_param( 'elements' );
        $settings = $request->get_param( 'settings' );
        $design_preset = $request->get_param( 'design_preset' );

        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true ) ?: array();

        if ( $elements !== null ) {
            $vbp_data['elements'] = $this->prepare_elements( $elements );
        }

        if ( $settings !== null ) {
            $vbp_data['settings'] = array_merge( $vbp_data['settings'] ?? array(), $settings );
        }

        // Aplicar design preset si se especifica
        if ( $design_preset ) {
            $preset_settings = $this->get_design_preset( $design_preset );
            if ( $preset_settings ) {
                $vbp_data['settings'] = array_merge( $vbp_data['settings'] ?? array(), $preset_settings );
                $vbp_data['settings']['design_preset'] = $design_preset;
            }
        }

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );

        $updated_post = get_post( $post_id );

        return new WP_REST_Response( array(
            'success'  => true,
            'id'       => $post_id,
            'title'    => $updated_post->post_title,
            'status'   => $updated_post->post_status,
            'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'view_url' => get_permalink( $post_id ),
        ) );
    }

    /**
     * Lista páginas VBP
     *
     * @param WP_REST_Request $request Request con filtros.
     * @return WP_REST_Response
     */
    public function list_pages( $request ) {
        $status = $request->get_param( 'status' );
        $post_type_filter = $request->get_param( 'post_type' ) ?: 'all';

        // Determinar post_types a buscar
        $post_types = ( 'all' === $post_type_filter )
            ? $this->supported_post_types
            : array( $post_type_filter );

        // Validar que el post_type sea soportado
        foreach ( $post_types as $post_type ) {
            if ( ! $this->is_supported_post_type( $post_type ) ) {
                return new WP_REST_Response( array(
                    'error' => 'Post type no soportado: ' . $post_type,
                    'supported_types' => $this->supported_post_types,
                ), 400 );
            }
        }

        $args = array(
            'post_type'      => $post_types,
            'post_status'    => $status,
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_flavor_vbp_data',
                    'compare' => 'EXISTS',
                ),
            ),
        );

        $posts = get_posts( $args );

        $pages = array_map( function( $post ) {
            $vbp_data = get_post_meta( $post->ID, '_flavor_vbp_data', true );
            $element_count = isset( $vbp_data['elements'] ) ? count( $vbp_data['elements'] ) : 0;

            return array(
                'id'            => $post->ID,
                'title'         => $post->post_title,
                'status'        => $post->post_status,
                'post_type'     => $post->post_type,
                'element_count' => $element_count,
                'modified'      => $post->post_modified,
                'edit_url'      => admin_url( "admin.php?page=vbp-editor&post_id={$post->ID}" ),
                'view_url'      => get_permalink( $post->ID ),
            );
        }, $posts );

        return new WP_REST_Response( array(
            'success' => true,
            'pages'   => $pages,
            'total'   => count( $pages ),
        ) );
    }

    /**
     * Duplica una página VBP
     *
     * @param WP_REST_Request $request Request con ID y opciones.
     * @return WP_REST_Response
     */
    public function duplicate_page( $request ) {
        $post_id = $request->get_param( 'id' );
        $post = get_post( $post_id );

        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada o no es un post type soportado',
            ), 404 );
        }

        $new_title = $request->get_param( 'title' ) ?: sprintf( 'Copia de %s', $post->post_title );
        $new_slug = $request->get_param( 'slug' );
        $new_status = $request->get_param( 'status' ) ?: 'draft';
        $copy_meta = $request->get_param( 'copy_meta' ) !== false;

        // Crear nuevo post
        $new_post_data = array(
            'post_title'   => $new_title,
            'post_type'    => $post->post_type,
            'post_status'  => $new_status,
            'post_content' => $post->post_content,
        );

        if ( $new_slug ) {
            $new_post_data['post_name'] = $new_slug;
        }

        $new_post_id = wp_insert_post( $new_post_data );

        if ( is_wp_error( $new_post_id ) ) {
            return new WP_REST_Response( array(
                'error' => $new_post_id->get_error_message(),
            ), 500 );
        }

        // Copiar datos VBP
        $vbp_data = get_post_meta( $post_id, '_flavor_vbp_data', true );
        if ( $vbp_data ) {
            // Regenerar IDs de elementos para evitar conflictos
            if ( isset( $vbp_data['elements'] ) ) {
                $vbp_data['elements'] = $this->regenerate_element_ids( $vbp_data['elements'] );
            }
            update_post_meta( $new_post_id, '_flavor_vbp_data', $vbp_data );
        }

        // Copiar versión VBP
        $vbp_version = get_post_meta( $post_id, '_flavor_vbp_version', true );
        if ( $vbp_version ) {
            update_post_meta( $new_post_id, '_flavor_vbp_version', $vbp_version );
        }

        // Copiar metadatos adicionales si se solicita
        if ( $copy_meta ) {
            $meta_keys_to_copy = array(
                '_yoast_wpseo_title',
                '_yoast_wpseo_metadesc',
                '_flavor_vbp_settings',
            );

            foreach ( $meta_keys_to_copy as $meta_key ) {
                $meta_value = get_post_meta( $post_id, $meta_key, true );
                if ( $meta_value ) {
                    update_post_meta( $new_post_id, $meta_key, $meta_value );
                }
            }
        }

        return new WP_REST_Response( array(
            'success'       => true,
            'id'            => $new_post_id,
            'original_id'   => $post_id,
            'title'         => $new_title,
            'status'        => $new_status,
            'edit_url'      => admin_url( "admin.php?page=vbp-editor&post_id={$new_post_id}" ),
            'view_url'      => get_permalink( $new_post_id ),
        ), 201 );
    }

    /**
     * Publica una página VBP
     *
     * @param WP_REST_Request $request Request con ID.
     * @return WP_REST_Response
     */
    public function publish_page( $request ) {
        $post_id = $request->get_param( 'id' );
        $post = get_post( $post_id );

        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada o no es un post type soportado',
            ), 404 );
        }

        if ( 'publish' === $post->post_status ) {
            return new WP_REST_Response( array(
                'success' => true,
                'message' => 'La página ya está publicada',
                'id'      => $post_id,
                'url'     => get_permalink( $post_id ),
            ) );
        }

        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => 'publish',
        ) );

        // Limpiar caché de permalinks
        flush_rewrite_rules( false );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Página publicada correctamente',
            'id'      => $post_id,
            'url'     => get_permalink( $post_id ),
        ) );
    }

    /**
     * Obtiene la URL de una página VBP
     *
     * @param WP_REST_Request $request Request con ID.
     * @return WP_REST_Response
     */
    public function get_page_url( $request ) {
        $post_id = $request->get_param( 'id' );
        $post = get_post( $post_id );

        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'error' => 'Página no encontrada o no es un post type soportado',
            ), 404 );
        }

        $permalink = get_permalink( $post_id );
        $preview_url = add_query_arg( 'preview', 'true', $permalink );

        return new WP_REST_Response( array(
            'success'     => true,
            'id'          => $post_id,
            'url'         => $permalink,
            'preview_url' => $preview_url,
            'edit_url'    => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'status'      => $post->post_status,
            'is_public'   => 'publish' === $post->post_status,
        ) );
    }

    /**
     * Regenera los IDs de elementos para evitar conflictos al duplicar
     *
     * @param array $elements Elementos a procesar.
     * @return array Elementos con IDs regenerados.
     */
    private function regenerate_element_ids( $elements ) {
        $regenerated = array();

        foreach ( $elements as $element ) {
            // Generar nuevo ID
            $element['id'] = 'el_' . bin2hex( random_bytes( 6 ) );

            // Procesar children recursivamente
            if ( ! empty( $element['children'] ) && is_array( $element['children'] ) ) {
                $element['children'] = $this->regenerate_element_ids( $element['children'] );
            }

            $regenerated[] = $element;
        }

        return $regenerated;
    }
}
