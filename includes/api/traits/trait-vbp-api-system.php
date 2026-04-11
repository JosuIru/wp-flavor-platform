<?php
/**
 * Trait para operaciones de sistema VBP
 *
 * Este trait contiene métodos de sistema, caché, y utilidades.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_System
 *
 * Contiene métodos para:
 * - Estado del sistema (get_system_status)
 * - Limpiar caché (clear_vbp_cache)
 * - Flush permalinks (flush_permalinks)
 * - Obtener capabilities (get_capabilities)
 * - Listar módulos (list_modules)
 */
trait VBP_API_System {

    /**
     * Obtiene el estado del sistema VBP
     *
     * Fase 2: Incluye información de post_types soportados y conteo por tipo.
     *
     * @return WP_REST_Response
     */
    public function get_system_status() {
        // Fase 2: Conteo de posts por cada tipo soportado
        $posts_by_type = array();
        foreach ( $this->supported_post_types as $post_type ) {
            if ( post_type_exists( $post_type ) ) {
                $counts = wp_count_posts( $post_type );
                $posts_by_type[ $post_type ] = array(
                    'publish' => $counts->publish ?? 0,
                    'draft'   => $counts->draft ?? 0,
                    'total'   => ( $counts->publish ?? 0 ) + ( $counts->draft ?? 0 ),
                );
            }
        }

        $status = array(
            'timestamp'    => current_time( 'mysql' ),
            'api_version'  => '2.3.0', // Fase 3: Export, Screenshot, PDF
            'vbp_loaded'   => $this->ensure_vbp_loaded(),
            'post_type'    => post_type_exists( 'flavor_landing' ),
            // Fase 2: Post types soportados
            'supported_post_types' => $this->supported_post_types,
            'post_types_status'    => array_map( function( $type ) {
                return array(
                    'type'   => $type,
                    'exists' => post_type_exists( $type ),
                );
            }, $this->supported_post_types ),
            'classes'      => array(
                'VBP_Block_Library' => class_exists( 'Flavor_VBP_Block_Library' ),
                'VBP_Canvas'        => class_exists( 'Flavor_VBP_Canvas' ),
                'VBP_Editor'        => class_exists( 'Flavor_VBP_Editor' ),
                'Visual_Builder'    => class_exists( 'Flavor_Visual_Builder' ),
            ),
            // Fase 3: Capacidades de exportación
            'export_capabilities' => array(
                'react'      => true,
                'vue'        => true,
                'svelte'     => true,
                'html'       => true,
                'screenshot' => $this->check_screenshot_capabilities(),
                'pdf'        => $this->check_pdf_capabilities(),
            ),
            'endpoints'    => array(
                'schema'           => rest_url( self::NAMESPACE . '/claude/schema' ),
                'blocks'           => rest_url( self::NAMESPACE . '/claude/blocks' ),
                'pages'            => rest_url( self::NAMESPACE . '/claude/pages' ),
                'templates'        => rest_url( self::NAMESPACE . '/claude/templates' ),
                'flush_permalinks' => rest_url( self::NAMESPACE . '/claude/flush-permalinks' ),
                'diagnostics'      => rest_url( 'flavor-vbp/v1/diagnostics/status' ),
                'design_system'    => rest_url( self::NAMESPACE . '/claude/design-system' ),
                'export_components'=> rest_url( self::NAMESPACE . '/claude/pages/{id}/export/components' ),
            ),
            // Fase 2: Conteo por tipo de post
            'posts_by_type' => $posts_by_type,
            // Legacy: mantener landings para compatibilidad
            'landings'     => array(
                'total'     => wp_count_posts( 'flavor_landing' )->publish ?? 0,
                'draft'     => wp_count_posts( 'flavor_landing' )->draft ?? 0,
            ),
        );

        // Verificar rewrite rules
        $rules = get_option( 'rewrite_rules', array() );
        $has_rules = false;
        if ( is_array( $rules ) ) {
            foreach ( $rules as $rewrite ) {
                if ( strpos( $rewrite, 'flavor_landing' ) !== false ) {
                    $has_rules = true;
                    break;
                }
            }
        }
        $status['rewrite_rules_ok'] = $has_rules;
        $status['permalink_structure'] = get_option( 'permalink_structure' );

        // Determinar salud
        $healthy = $status['vbp_loaded'] && $status['post_type'] && $status['rewrite_rules_ok'];
        $status['health'] = $healthy ? 'ok' : 'issues';

        if ( ! $healthy ) {
            $status['issues'] = array();
            if ( ! $status['vbp_loaded'] ) {
                $status['issues'][] = 'VBP Block Library no cargada';
            }
            if ( ! $status['post_type'] ) {
                $status['issues'][] = 'Post type flavor_landing no registrado';
            }
            if ( ! $status['rewrite_rules_ok'] ) {
                $status['issues'][] = 'Rewrite rules no configuradas - usar POST /claude/flush-permalinks';
            }
        }

        return new WP_REST_Response( $status, 200 );
    }

    /**
     * Verifica capacidades de screenshot disponibles
     *
     * @return array
     */
    private function check_screenshot_capabilities() {
        $settings = get_option( 'flavor_vbp_screenshot_settings', array() );

        return array(
            'browserless'    => ! empty( $settings['browserless_token'] ),
            'screenshotone'  => ! empty( $settings['screenshotone_key'] ),
            'local_fallback' => true,
        );
    }

    /**
     * Verifica capacidades de PDF disponibles
     *
     * @return array
     */
    private function check_pdf_capabilities() {
        return array(
            'dompdf'         => class_exists( 'Dompdf\\Dompdf' ),
            'tcpdf'          => class_exists( 'TCPDF' ),
            'html_fallback'  => true,
        );
    }

    /**
     * Limpia la caché de VBP
     *
     * @param WP_REST_Request $request Request con opciones.
     * @return WP_REST_Response
     */
    public function clear_vbp_cache( $request ) {
        $page_id = $request->get_param( 'page_id' );
        $cleared = array();

        if ( $page_id ) {
            // Limpiar caché de página específica
            clean_post_cache( $page_id );
            delete_transient( "vbp_rendered_{$page_id}" );
            delete_transient( "vbp_styles_{$page_id}" );
            delete_transient( 'vbp_page_' . $page_id );
            delete_transient( 'vbp_css_' . $page_id );
            $cleared[] = "page_{$page_id}";
        } else {
            // Limpiar toda la caché de VBP
            global $wpdb;

            // Eliminar transients de VBP
            $wpdb->query(
                "DELETE FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_vbp_%'
                OR option_name LIKE '_transient_timeout_vbp_%'"
            );

            // Limpiar object cache si está disponible
            if ( function_exists( 'wp_cache_flush_group' ) ) {
                wp_cache_flush_group( 'vbp' );
            }

            $cleared[] = 'all_vbp_transients';
            $cleared[] = 'object_cache';
        }

        // Limpiar caché de plugins populares
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            wp_cache_clear_cache();
            $cleared[] = 'wp_super_cache';
        }

        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
            $cleared[] = 'w3_total_cache';
        }

        if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
            LiteSpeed_Cache_API::purge_all();
            $cleared[] = 'litespeed';
        }

        return new WP_REST_Response( array(
            'success'   => true,
            'message'   => 'Caché limpiada correctamente.',
            'page_id'   => $page_id ?: 'all',
            'cleared'   => $cleared,
            'timestamp' => current_time( 'c' ),
        ), 200 );
    }

    /**
     * Regenera permalinks
     *
     * @return WP_REST_Response
     */
    public function flush_permalinks() {
        flush_rewrite_rules( false );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Permalinks regenerados correctamente',
        ) );
    }

    /**
     * Obtiene las capabilities completas del sistema
     *
     * @return WP_REST_Response
     */
    public function get_capabilities() {
        // Obtener módulos activos
        $modulos_activos = get_option( 'flavor_active_modules', array() );
        if ( ! is_array( $modulos_activos ) ) {
            $modulos_activos = array();
        }

        // Animaciones disponibles
        $animaciones = array(
            'entrance' => array(
                'fadeIn', 'fadeInUp', 'fadeInDown', 'fadeInLeft', 'fadeInRight',
                'slideInUp', 'slideInDown', 'slideInLeft', 'slideInRight',
                'zoomIn', 'zoomInUp', 'zoomInDown',
                'bounceIn', 'bounceInUp', 'bounceInDown',
                'flipInX', 'flipInY', 'rotateIn',
            ),
            'hover' => array(
                'pulse', 'shake', 'bounce', 'swing', 'wobble', 'tada',
                'grow', 'shrink', 'float', 'sink',
                'skew', 'skewForward', 'skewBackward',
            ),
            'loop' => array(
                'pulse', 'bounce', 'shake', 'swing', 'wobble',
                'flash', 'rubberBand', 'heartBeat', 'spin',
            ),
        );

        // Tipos de sección disponibles
        $secciones = array(
            'hero'        => 'Sección principal con título, subtítulo y CTA',
            'features'    => 'Grid de características con iconos',
            'cta'         => 'Llamada a la acción',
            'testimonials'=> 'Testimonios de usuarios',
            'pricing'     => 'Tabla de precios',
            'faq'         => 'Preguntas frecuentes (acordeón)',
            'stats'       => 'Estadísticas/números destacados',
            'team'        => 'Equipo de trabajo',
            'contact'     => 'Formulario de contacto',
            'gallery'     => 'Galería de imágenes',
            'text'        => 'Bloque de texto libre',
            'section'     => 'Sección genérica con contenido',
            'card'        => 'Tarjeta individual',
            'grid'        => 'Grid flexible de elementos',
        );

        // Widgets de módulos disponibles
        $widgets_modulos = $this->get_module_widgets_for_capabilities();

        return new WP_REST_Response( array(
            'version'     => '2.2.0',
            'endpoints'   => array(
                'pages'           => rest_url( self::NAMESPACE . '/claude/pages' ),
                'styled_pages'    => rest_url( self::NAMESPACE . '/claude/pages/styled' ),
                'design_presets'  => rest_url( self::NAMESPACE . '/claude/design-presets' ),
                'templates'       => rest_url( self::NAMESPACE . '/claude/templates' ),
                'widgets'         => rest_url( self::NAMESPACE . '/claude/widgets' ),
                'validate'        => rest_url( self::NAMESPACE . '/claude/validate-elements' ),
                'status'          => rest_url( self::NAMESPACE . '/claude/status' ),
            ),
            'design_presets' => array_keys( $this->get_all_design_presets() ),
            'section_types'  => array_keys( $secciones ),
            'section_descriptions' => $secciones,
            'animations'     => $animaciones,
            'modules'        => array(
                'active'  => $modulos_activos,
                'count'   => count( $modulos_activos ),
                'widgets' => $widgets_modulos,
            ),
            'features'       => array(
                'auto_normalization'    => true,
                'multi_language_fields' => true,
                'auto_animations'       => true,
                'preset_styles'         => true,
                'module_widgets'        => true,
            ),
        ), 200 );
    }

    /**
     * Obtiene widgets de módulos para capabilities
     *
     * @return array
     */
    private function get_module_widgets_for_capabilities() {
        $modulos_activos = get_option( 'flavor_active_modules', array() );
        if ( ! is_array( $modulos_activos ) ) {
            return array();
        }

        $widget_mapping = array(
            'grupos-consumo'  => array( 'id' => 'module_grupos_consumo', 'name' => 'Grupos de Consumo' ),
            'eventos'         => array( 'id' => 'module_eventos', 'name' => 'Eventos' ),
            'marketplace'     => array( 'id' => 'module_marketplace', 'name' => 'Marketplace' ),
            'cursos'          => array( 'id' => 'module_cursos', 'name' => 'Cursos' ),
            'talleres'        => array( 'id' => 'module_talleres', 'name' => 'Talleres' ),
            'socios'          => array( 'id' => 'module_socios', 'name' => 'Socios' ),
            'biblioteca'      => array( 'id' => 'module_biblioteca', 'name' => 'Biblioteca' ),
            'foros'           => array( 'id' => 'module_foros', 'name' => 'Foros' ),
            'campanias'       => array( 'id' => 'module_campanias', 'name' => 'Campañas' ),
            'encuestas'       => array( 'id' => 'module_encuestas', 'name' => 'Encuestas' ),
            'transparencia'   => array( 'id' => 'module_transparencia', 'name' => 'Transparencia' ),
            'crowdfunding'    => array( 'id' => 'module_crowdfunding', 'name' => 'Crowdfunding' ),
            'banco-tiempo'    => array( 'id' => 'module_banco_tiempo', 'name' => 'Banco de Tiempo' ),
        );

        $available = array();
        foreach ( $modulos_activos as $modulo ) {
            if ( isset( $widget_mapping[ $modulo ] ) ) {
                $available[] = $widget_mapping[ $modulo ];
            }
        }

        return $available;
    }

    /**
     * Lista los módulos disponibles
     *
     * @return WP_REST_Response
     */
    public function list_modules() {
        $active_modules = get_option( 'flavor_active_modules', array() );
        if ( ! is_array( $active_modules ) ) {
            $active_modules = array();
        }

        $modules = array();
        foreach ( $active_modules as $module_id ) {
            $modules[] = array(
                'id'     => $module_id,
                'name'   => ucwords( str_replace( array( '_', '-' ), ' ', $module_id ) ),
                'active' => true,
            );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'modules' => $modules,
            'total'   => count( $modules ),
        ) );
    }
}
