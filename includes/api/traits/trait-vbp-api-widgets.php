<?php
/**
 * Trait para preview y gestión de widgets VBP
 *
 * Este trait contiene todos los métodos relacionados con
 * preview de widgets y gestión de widgets registrados.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Widgets
 *
 * Contiene métodos para:
 * - Preview de widgets (get_widget_preview)
 * - Obtener info de widgets (get_widget_info)
 * - Listar widgets registrados (get_all_registered_widgets)
 * - Widgets de fallback (get_fallback_widgets)
 */
trait VBP_API_Widgets {

    /**
     * Obtiene el preview HTML de un widget específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_widget_preview( $request ) {
        $widget_type = $request->get_param( 'type' );
        $config      = $request->get_param( 'config' ) ?? array();

        // Cargar VBP Canvas si no está disponible
        if ( ! class_exists( 'Flavor_VBP_Canvas' ) ) {
            $canvas_path = FLAVOR_PLATFORM_PATH . 'includes/visual-builder-pro/class-vbp-canvas.php';
            if ( file_exists( $canvas_path ) ) {
                require_once $canvas_path;
            } else {
                return new WP_REST_Response( array(
                    'success' => false,
                    'error'   => 'VBP Canvas no disponible',
                ), 500 );
            }
        }

        // Obtener información del widget desde Block Library
        $widget_info = $this->get_widget_info( $widget_type );

        if ( ! $widget_info ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Widget no encontrado: ' . $widget_type,
            ), 404 );
        }

        // Crear elemento simulado para el preview
        $elemento = array(
            'type'    => $widget_type,
            'id'      => 'preview_' . uniqid(),
            'name'    => $widget_info['name'] ?? ucfirst( str_replace( '_', ' ', $widget_type ) ),
            'visible' => true,
            'locked'  => false,
            'data'    => array_merge( $widget_info['defaults'] ?? array(), $config ),
            'styles'  => array(),
        );

        // Usar VBP Canvas para generar el preview
        $canvas = Flavor_VBP_Canvas::get_instance();
        $preview_html = $canvas->render_widget_preview_public( $elemento, $widget_info );

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array(
                'type'      => $widget_type,
                'name'      => $widget_info['name'] ?? $widget_type,
                'module'    => $widget_info['module'] ?? '',
                'shortcode' => $widget_info['shortcode'] ?? '',
                'preview'   => $preview_html,
                'config'    => $config,
            ),
        ), 200 );
    }

    /**
     * Obtiene información de un widget específico
     *
     * @param string $widget_type Tipo de widget.
     * @return array|null
     */
    private function get_widget_info( $widget_type ) {
        $widgets = $this->get_all_registered_widgets();

        foreach ( $widgets as $widget ) {
            if ( $widget['type'] === $widget_type ) {
                return $widget;
            }
        }

        return null;
    }

    /**
     * Obtiene todos los widgets registrados del sistema
     *
     * @return array
     */
    private function get_all_registered_widgets() {
        $widgets = array();

        // Intentar cargar desde Block Library
        if ( $this->ensure_vbp_loaded() ) {
            $libreria   = Flavor_VBP_Block_Library::get_instance();
            $categorias = $libreria->get_categorias_con_bloques();

            foreach ( $categorias as $categoria ) {
                // Incluir widgets de módulos y de cualquier categoría que tenga shortcode
                $is_module_category = strpos( $categoria['name'], 'Módulo' ) !== false
                    || $categoria['name'] === 'Widgets'
                    || strpos( strtolower( $categoria['name'] ), 'widget' ) !== false;

                foreach ( $categoria['blocks'] as $bloque ) {
                    // Incluir si es categoría de módulos o si tiene shortcode
                    if ( $is_module_category || ! empty( $bloque['shortcode'] ) ) {
                        $widgets[] = array(
                            'type'      => $bloque['id'] ?? '',
                            'name'      => $bloque['name'] ?? '',
                            'icon'      => $bloque['icon'] ?? 'dashicons-admin-generic',
                            'module'    => $this->extract_module_from_category( $categoria['name'] ),
                            'shortcode' => $bloque['shortcode'] ?? '',
                            'defaults'  => $bloque['defaults'] ?? array(),
                            'fields'    => $bloque['fields'] ?? array(),
                            'category'  => $categoria['name'] ?? 'modules',
                        );
                    }
                }
            }
        }

        // Añadir widgets de fallback que no estén ya incluidos
        $fallback_widgets = $this->get_fallback_widgets();
        $existing_types = array_column( $widgets, 'type' );

        foreach ( $fallback_widgets as $fallback ) {
            if ( ! in_array( $fallback['type'], $existing_types, true ) ) {
                $widgets[] = $fallback;
            }
        }

        return $widgets;
    }

    /**
     * Extrae el nombre del módulo de la categoría
     *
     * @param string $category_name Nombre de la categoría.
     * @return string
     */
    private function extract_module_from_category( $category_name ) {
        // "Módulo: Marketplace" -> "marketplace"
        if ( strpos( $category_name, 'Módulo:' ) !== false ) {
            $module_name = str_replace( 'Módulo:', '', $category_name );
            return sanitize_title( trim( $module_name ) );
        }

        return sanitize_title( $category_name );
    }

    /**
     * Obtiene widgets de fallback cuando Block Library no está disponible
     *
     * @return array
     */
    private function get_fallback_widgets() {
        return array(
            array(
                'type'      => 'social_feed',
                'name'      => 'Feed Social',
                'icon'      => 'dashicons-share',
                'module'    => 'red-social',
                'shortcode' => '[flavor_social_feed]',
                'defaults'  => array( 'limit' => 10 ),
            ),
            array(
                'type'      => 'eventos',
                'name'      => 'Lista de Eventos',
                'icon'      => 'dashicons-calendar',
                'module'    => 'eventos',
                'shortcode' => '[flavor_eventos]',
                'defaults'  => array( 'limit' => 6, 'view' => 'grid' ),
            ),
            array(
                'type'      => 'marketplace_productos',
                'name'      => 'Productos Marketplace',
                'icon'      => 'dashicons-cart',
                'module'    => 'marketplace',
                'shortcode' => '[flavor_marketplace_productos]',
                'defaults'  => array( 'limit' => 12, 'columns' => 4 ),
            ),
            array(
                'type'      => 'cursos_catalogo',
                'name'      => 'Catálogo de Cursos',
                'icon'      => 'dashicons-welcome-learn-more',
                'module'    => 'cursos',
                'shortcode' => '[flavor_cursos_catalogo]',
                'defaults'  => array( 'limit' => 6 ),
            ),
            array(
                'type'      => 'encuestas_activas',
                'name'      => 'Encuestas Activas',
                'icon'      => 'dashicons-chart-bar',
                'module'    => 'encuestas',
                'shortcode' => '[flavor_encuestas_activas]',
                'defaults'  => array( 'limit' => 3 ),
            ),
            array(
                'type'      => 'transparencia_presupuesto',
                'name'      => 'Presupuesto Transparente',
                'icon'      => 'dashicons-money-alt',
                'module'    => 'transparencia',
                'shortcode' => '[flavor_transparencia_presupuesto]',
                'defaults'  => array(),
            ),
            array(
                'type'      => 'comunidades_listado',
                'name'      => 'Listado Comunidades',
                'icon'      => 'dashicons-groups',
                'module'    => 'comunidades',
                'shortcode' => '[flavor_comunidades]',
                'defaults'  => array( 'limit' => 8 ),
            ),
            array(
                'type'      => 'mapa_actores',
                'name'      => 'Mapa de Actores',
                'icon'      => 'dashicons-location',
                'module'    => 'mapa-actores',
                'shortcode' => '[flavor_mapa_actores]',
                'defaults'  => array( 'height' => '400px' ),
            ),
        );
    }
}
