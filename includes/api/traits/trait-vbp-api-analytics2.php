<?php
/**
 * Trait para Analytics Avanzado VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_AnalyticsAdvanced {


    /**
     * Obtiene analytics de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_analytics( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $period = $request->get_param( 'period' ) ?: '30d';

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $analytics_data = get_post_meta( $page_id, '_flavor_vbp_analytics', true ) ?: array();

        // Calcular estadísticas
        $page_views = $analytics_data['views'] ?? 0;
        $unique_visitors = $analytics_data['unique_visitors'] ?? 0;
        $avg_time_on_page = $analytics_data['avg_time'] ?? 0;
        $bounce_rate = $analytics_data['bounce_rate'] ?? 0;

        return new WP_REST_Response( array(
            'success'   => true,
            'page_id'   => $page_id,
            'period'    => $period,
            'analytics' => array(
                'views'           => $page_views,
                'unique_visitors' => $unique_visitors,
                'avg_time'        => $avg_time_on_page,
                'bounce_rate'     => $bounce_rate,
                'cta_clicks'      => $analytics_data['cta_clicks'] ?? 0,
                'form_submissions' => $analytics_data['form_submissions'] ?? 0,
            ),
        ), 200 );
    }

    /**
     * Registra evento de analytics
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function track_analytics_event( $request ) {
        $page_id = (int) $request->get_param( 'page_id' );
        $event_type = sanitize_text_field( $request->get_param( 'event_type' ) );
        $event_data = $request->get_param( 'event_data' ) ?: array();

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $analytics = get_post_meta( $page_id, '_flavor_vbp_analytics', true ) ?: array();

        // Incrementar contador según tipo de evento
        switch ( $event_type ) {
            case 'view':
                $analytics['views'] = ( $analytics['views'] ?? 0 ) + 1;
                break;
            case 'cta_click':
                $analytics['cta_clicks'] = ( $analytics['cta_clicks'] ?? 0 ) + 1;
                break;
            case 'form_submit':
                $analytics['form_submissions'] = ( $analytics['form_submissions'] ?? 0 ) + 1;
                break;
            case 'scroll':
                // Registrar profundidad de scroll
                $scroll_depth = $event_data['depth'] ?? 0;
                $analytics['scroll_depths'][] = $scroll_depth;
                break;
        }

        $analytics['last_updated'] = current_time( 'mysql' );

        update_post_meta( $page_id, '_flavor_vbp_analytics', $analytics );

        return new WP_REST_Response( array(
            'success' => true,
            'event'   => $event_type,
            'tracked' => true,
        ), 200 );
    }

    /**
     * Obtiene heatmap de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_page_heatmap( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $heatmap_type = $request->get_param( 'type' ) ?: 'click';

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $heatmap_data = get_post_meta( $page_id, '_flavor_vbp_heatmap_' . $heatmap_type, true ) ?: array();

        return new WP_REST_Response( array(
            'success' => true,
            'type'    => $heatmap_type,
            'data'    => $heatmap_data,
        ), 200 );
    }

    /**
     * Obtiene dashboard de analytics
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_analytics_dashboard( $request ) {
        $period = $request->get_param( 'period' ) ?: '30d';

        // Obtener todas las páginas VBP
        $pages = get_posts( array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => flavor_safe_posts_limit( -1 ),
            'post_status'    => 'publish',
        ) );

        $total_views = 0;
        $total_conversions = 0;
        $page_stats = array();

        foreach ( $pages as $page ) {
            $analytics = get_post_meta( $page->ID, '_flavor_vbp_analytics', true ) ?: array();
            $page_views = $analytics['views'] ?? 0;
            $conversions = $analytics['form_submissions'] ?? 0;

            $total_views += $page_views;
            $total_conversions += $conversions;

            $page_stats[] = array(
                'id'          => $page->ID,
                'title'       => $page->post_title,
                'views'       => $page_views,
                'conversions' => $conversions,
            );
        }

        // Ordenar por views
        usort( $page_stats, function( $a, $b ) {
            return $b['views'] - $a['views'];
        } );

        return new WP_REST_Response( array(
            'success'    => true,
            'period'     => $period,
            'summary'    => array(
                'total_views'       => $total_views,
                'total_conversions' => $total_conversions,
                'page_count'        => count( $pages ),
            ),
            'top_pages'  => array_slice( $page_stats, 0, 10 ),
        ), 200 );
    }

    /**
     * Compara analytics de páginas
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compare_pages_analytics( $request ) {
        $page_ids = $request->get_param( 'page_ids' );
        $metrics = $request->get_param( 'metrics' ) ?: array( 'views', 'conversions' );

        $comparison_data = array();

        foreach ( $page_ids as $page_id ) {
            $post = get_post( (int) $page_id );
            if ( ! $this->is_valid_vbp_post( $post ) ) {
                continue;
            }

            $analytics = get_post_meta( $page_id, '_flavor_vbp_analytics', true ) ?: array();

            $page_metrics = array(
                'id'    => $page_id,
                'title' => $post->post_title,
            );

            if ( in_array( 'views', $metrics, true ) ) {
                $page_metrics['views'] = $analytics['views'] ?? 0;
            }
            if ( in_array( 'conversions', $metrics, true ) ) {
                $page_metrics['conversions'] = $analytics['form_submissions'] ?? 0;
            }
            if ( in_array( 'bounce_rate', $metrics, true ) ) {
                $page_metrics['bounce_rate'] = $analytics['bounce_rate'] ?? 0;
            }
            if ( in_array( 'avg_time', $metrics, true ) ) {
                $page_metrics['avg_time'] = $analytics['avg_time'] ?? 0;
            }

            $comparison_data[] = $page_metrics;
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'metrics'    => $metrics,
            'comparison' => $comparison_data,
        ), 200 );
    }

    // =============================================
}
