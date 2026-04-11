<?php
/**
 * Trait para A/B Testing VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_ABTesting {


    /**
     * Lista variantes de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function list_page_variants( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $variants_json = get_post_meta( $page_id, '_flavor_vbp_ab_variants', true );
        $variants = $variants_json ? json_decode( $variants_json, true ) : array();

        $list = array();
        foreach ( $variants as $variant ) {
            $variant_post = get_post( $variant['page_id'] );
            $variant['status'] = $variant_post ? $variant_post->post_status : 'deleted';
            $variant['conversion_rate'] = $variant['views'] > 0
                ? round( ( $variant['conversions'] / $variant['views'] ) * 100, 2 )
                : 0;
            $list[] = $variant;
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'page_id'  => $page_id,
            'total'    => count( $list ),
            'variants' => $list,
        ), 200 );
    }

    /**
     * Obtiene estadísticas de A/B test
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_ab_test_stats( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $variants_json = get_post_meta( $page_id, '_flavor_vbp_ab_variants', true );
        $variants = $variants_json ? json_decode( $variants_json, true ) : array();

        // Estadísticas de la página original
        $original_views = (int) get_post_meta( $page_id, '_flavor_vbp_views', true );
        $original_conversions = (int) get_post_meta( $page_id, '_flavor_vbp_conversions', true );

        $stats = array(
            'original' => array(
                'page_id'         => $page_id,
                'name'            => 'Original',
                'views'           => $original_views,
                'conversions'     => $original_conversions,
                'conversion_rate' => $original_views > 0 ? round( ( $original_conversions / $original_views ) * 100, 2 ) : 0,
            ),
            'variants' => array(),
        );

        foreach ( $variants as $variant ) {
            $stats['variants'][] = array(
                'id'              => $variant['id'],
                'name'            => $variant['name'],
                'page_id'         => $variant['page_id'],
                'views'           => $variant['views'],
                'conversions'     => $variant['conversions'],
                'conversion_rate' => $variant['views'] > 0
                    ? round( ( $variant['conversions'] / $variant['views'] ) * 100, 2 )
                    : 0,
                'traffic'         => $variant['traffic_percentage'],
            );
        }

        // Determinar líder
        $best_rate = $stats['original']['conversion_rate'];
        $leader = 'original';
        foreach ( $stats['variants'] as $v ) {
            if ( $v['conversion_rate'] > $best_rate ) {
                $best_rate = $v['conversion_rate'];
                $leader = $v['id'];
            }
        }
        $stats['current_leader'] = $leader;

        return new WP_REST_Response( array(
            'success' => true,
            'stats'   => $stats,
        ), 200 );
    }

    /**
     * Declara ganador de A/B test
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function declare_ab_winner( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $variant_id = sanitize_text_field( $request->get_param( 'variant_id' ) );

        $variants_json = get_post_meta( $page_id, '_flavor_vbp_ab_variants', true );
        $variants = $variants_json ? json_decode( $variants_json, true ) : array();

        if ( ! isset( $variants[ $variant_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Variante no encontrada.',
            ), 404 );
        }

        $winner = $variants[ $variant_id ];

        // Copiar contenido del ganador a la página original
        $winner_elements = get_post_meta( $winner['page_id'], '_flavor_vbp_elements', true );
        $winner_styles = get_post_meta( $winner['page_id'], '_flavor_vbp_styles', true );

        update_post_meta( $page_id, '_flavor_vbp_elements', $winner_elements );
        update_post_meta( $page_id, '_flavor_vbp_styles', $winner_styles );

        // Archivar test
        update_post_meta( $page_id, '_flavor_vbp_ab_winner', $variant_id );
        update_post_meta( $page_id, '_flavor_vbp_ab_ended', current_time( 'mysql' ) );

        // Eliminar variantes
        foreach ( $variants as $v ) {
            wp_delete_post( $v['page_id'], true );
        }
        delete_post_meta( $page_id, '_flavor_vbp_ab_variants' );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Variante '{$winner['name']}' declarada ganadora y aplicada.",
        ), 200 );
    }

    /**
     * Elimina variante
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function delete_page_variant( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $variant_id = sanitize_text_field( $request->get_param( 'variant_id' ) );

        $variants_json = get_post_meta( $page_id, '_flavor_vbp_ab_variants', true );
        $variants = $variants_json ? json_decode( $variants_json, true ) : array();

        if ( ! isset( $variants[ $variant_id ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Variante no encontrada.',
            ), 404 );
        }

        // Eliminar página de variante
        wp_delete_post( $variants[ $variant_id ]['page_id'], true );

        unset( $variants[ $variant_id ] );
        update_post_meta( $page_id, '_flavor_vbp_ab_variants', wp_json_encode( $variants ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Variante eliminada.',
        ), 200 );
    }

    // =============================================
}
