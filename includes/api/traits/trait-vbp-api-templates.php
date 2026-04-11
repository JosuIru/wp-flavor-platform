<?php
/**
 * Trait para operaciones de plantillas VBP
 *
 * Este trait contiene todos los métodos relacionados con
 * bulk operations, export/import de plantillas y preview.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_Templates
 *
 * Contiene métodos para:
 * - Creación masiva de páginas (bulk_create_pages)
 * - Export/Import de plantillas (export_template, import_template)
 * - Clonación remota (clone_remote_template)
 * - Preview de páginas (preview_page)
 */
trait VBP_API_Templates {

    /**
     * Crea múltiples páginas VBP en una sola petición
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function bulk_create_pages( $request ) {
        $pages_data = $request->get_param( 'pages' );
        $default_preset = sanitize_key( $request->get_param( 'default_preset' ) );
        $default_status = sanitize_key( $request->get_param( 'default_status' ) );

        if ( empty( $pages_data ) || ! is_array( $pages_data ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Se requiere un array de páginas',
            ), 400 );
        }

        $created_pages = array();
        $errors = array();
        $start_time = microtime( true );

        foreach ( $pages_data as $index => $page_config ) {
            $title = sanitize_text_field( $page_config['title'] ?? '' );

            if ( empty( $title ) ) {
                $errors[] = array(
                    'index' => $index,
                    'error' => 'El título es requerido',
                );
                continue;
            }

            $preset = sanitize_key( $page_config['preset'] ?? $default_preset );
            $status = sanitize_key( $page_config['status'] ?? $default_status );
            $sections = $page_config['sections'] ?? array( 'hero', 'features', 'cta' );
            $context = $page_config['context'] ?? array();
            $slug = sanitize_title( $page_config['slug'] ?? $title );

            // Generar elementos desde las secciones
            $elements = array();
            foreach ( $sections as $section_type ) {
                $section_data = $this->create_section( $section_type, $context );
                if ( $section_data ) {
                    $elements[] = array(
                        'type' => $section_type,
                        'data' => $section_data,
                    );
                }
            }

            // Preparar elementos
            $elements = $this->prepare_elements( $elements );

            // Crear post
            $post_id = wp_insert_post( array(
                'post_title'  => $title,
                'post_name'   => $slug,
                'post_type'   => 'flavor_landing',
                'post_status' => $status,
            ) );

            if ( is_wp_error( $post_id ) ) {
                $errors[] = array(
                    'index' => $index,
                    'title' => $title,
                    'error' => $post_id->get_error_message(),
                );
                continue;
            }

            // Obtener preset de diseño
            $preset_settings = $this->get_design_preset( $preset );
            $settings = array(
                'pageWidth'       => 1200,
                'backgroundColor' => '#ffffff',
                'design_preset'   => $preset,
            );

            if ( $preset_settings ) {
                $settings = array_merge( $settings, $preset_settings );
            }

            // Guardar datos VBP
            $vbp_data = array(
                'version'  => '2.0.15',
                'elements' => $elements,
                'settings' => $settings,
            );

            update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );
            update_post_meta( $post_id, '_flavor_vbp_version', '2.0.15' );

            $created_pages[] = array(
                'index'    => $index,
                'id'       => $post_id,
                'title'    => $title,
                'slug'     => get_post_field( 'post_name', $post_id ),
                'status'   => $status,
                'preset'   => $preset,
                'sections' => count( $elements ),
                'edit_url' => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
                'view_url' => get_permalink( $post_id ),
            );
        }

        $elapsed_time = round( ( microtime( true ) - $start_time ) * 1000, 2 );

        return new WP_REST_Response( array(
            'success'       => count( $errors ) === 0,
            'created_count' => count( $created_pages ),
            'error_count'   => count( $errors ),
            'pages'         => $created_pages,
            'errors'        => $errors,
            'elapsed_ms'    => $elapsed_time,
        ), count( $created_pages ) > 0 ? 201 : 400 );
    }

    /**
     * Exporta una página VBP como plantilla JSON
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_template( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $post = get_post( $page_id );

        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página VBP no encontrada',
            ), 404 );
        }

        $vbp_data = get_post_meta( $page_id, '_flavor_vbp_data', true );

        if ( empty( $vbp_data ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'La página no tiene contenido VBP',
            ), 400 );
        }

        $template = array(
            'version'     => '1.0',
            'exported_at' => current_time( 'c' ),
            'source_id'   => $page_id,
            'source_site' => home_url(),
            'title'       => $post->post_title,
            'slug'        => $post->post_name,
            'vbp_version' => $vbp_data['version'] ?? '2.0.15',
            'elements'    => $vbp_data['elements'] ?? array(),
            'settings'    => $vbp_data['settings'] ?? array(),
            'meta'        => array(
                'sections_count' => count( $vbp_data['elements'] ?? array() ),
                'preset'         => $vbp_data['settings']['design_preset'] ?? 'custom',
            ),
        );

        return new WP_REST_Response( array(
            'success'  => true,
            'template' => $template,
        ), 200 );
    }

    /**
     * Importa una plantilla JSON como nueva página VBP
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function import_template( $request ) {
        $template = $request->get_param( 'template' );
        $custom_title = sanitize_text_field( $request->get_param( 'title' ) );
        $status = sanitize_key( $request->get_param( 'status' ) );

        if ( empty( $template ) || ! is_array( $template ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Datos de plantilla inválidos',
            ), 400 );
        }

        // Extraer datos de la plantilla
        $title = ! empty( $custom_title ) ? $custom_title : ( $template['title'] ?? 'Página Importada' );
        $elements = $template['elements'] ?? array();
        $settings = $template['settings'] ?? array();

        if ( empty( $elements ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'La plantilla no contiene elementos',
            ), 400 );
        }

        // Regenerar IDs para evitar conflictos
        $elements = $this->regenerate_element_ids( $elements );

        // Crear post
        $post_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_type'   => 'flavor_landing',
            'post_status' => $status,
        ) );

        if ( is_wp_error( $post_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $post_id->get_error_message(),
            ), 500 );
        }

        // Guardar datos VBP
        $vbp_data = array(
            'version'  => '2.0.15',
            'elements' => $elements,
            'settings' => $settings,
        );

        update_post_meta( $post_id, '_flavor_vbp_data', $vbp_data );
        update_post_meta( $post_id, '_flavor_vbp_version', '2.0.15' );
        update_post_meta( $post_id, '_flavor_imported_from', $template['source_site'] ?? 'unknown' );
        update_post_meta( $post_id, '_flavor_imported_at', current_time( 'mysql' ) );

        return new WP_REST_Response( array(
            'success'        => true,
            'id'             => $post_id,
            'title'          => $title,
            'status'         => $status,
            'sections_count' => count( $elements ),
            'imported_from'  => $template['source_site'] ?? 'unknown',
            'edit_url'       => admin_url( "admin.php?page=vbp-editor&post_id={$post_id}" ),
            'view_url'       => get_permalink( $post_id ),
        ), 201 );
    }

    /**
     * Clona una página VBP desde un sitio remoto
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function clone_remote_template( $request ) {
        $source_url = esc_url_raw( $request->get_param( 'source_url' ) );
        $page_id = (int) $request->get_param( 'page_id' );
        $remote_api_key = sanitize_text_field( $request->get_param( 'api_key' ) );
        $custom_title = sanitize_text_field( $request->get_param( 'title' ) );
        $status = sanitize_key( $request->get_param( 'status' ) );

        // Validar URL
        if ( empty( $source_url ) || ! filter_var( $source_url, FILTER_VALIDATE_URL ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'URL de origen inválida',
            ), 400 );
        }

        // Construir endpoint remoto
        $export_endpoint = trailingslashit( $source_url ) . 'wp-json/flavor-vbp/v1/claude/templates/export/' . $page_id;

        // Hacer petición al sitio remoto
        $response = wp_remote_get( $export_endpoint, array(
            'headers' => array(
                'X-VBP-Key' => $remote_api_key,
            ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Error al conectar con el sitio remoto: ' . $response->get_error_message(),
            ), 502 );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'El sitio remoto devolvió error: ' . $response_code,
            ), $response_code );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data['success'] ) || empty( $data['template'] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Respuesta inválida del sitio remoto',
            ), 502 );
        }

        $template = $data['template'];

        // Usar el método de importación existente
        $import_request = new WP_REST_Request( 'POST' );
        $import_request->set_param( 'template', $template );
        $import_request->set_param( 'title', $custom_title ?: $template['title'] );
        $import_request->set_param( 'status', $status );

        $result = $this->import_template( $import_request );
        $result_data = $result->get_data();

        if ( ! empty( $result_data['success'] ) ) {
            $result_data['cloned_from'] = array(
                'url'     => $source_url,
                'page_id' => $page_id,
            );
        }

        return new WP_REST_Response( $result_data, $result->get_status() );
    }

    /**
     * Genera preview HTML de elementos VBP sin guardar
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function preview_page( $request ) {
        $elements = $request->get_param( 'elements' );
        $preset = sanitize_key( $request->get_param( 'preset' ) );
        $settings = $request->get_param( 'settings' );

        if ( empty( $elements ) || ! is_array( $elements ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Se requieren elementos para el preview',
            ), 400 );
        }

        // Cargar VBP Canvas si está disponible
        if ( ! class_exists( 'Flavor_VBP_Canvas' ) ) {
            $canvas_path = FLAVOR_PLATFORM_PATH . 'includes/visual-builder-pro/class-vbp-canvas.php';
            if ( file_exists( $canvas_path ) ) {
                require_once $canvas_path;
            }
        }

        // Preparar elementos
        $elements = $this->prepare_elements( $elements );

        // Obtener preset de diseño
        $preset_settings = $this->get_design_preset( $preset );
        $final_settings = array_merge(
            array(
                'pageWidth'       => 1200,
                'backgroundColor' => '#ffffff',
                'design_preset'   => $preset,
            ),
            $preset_settings ?: array(),
            $settings ?: array()
        );

        // Generar CSS de preset
        $preset_css = $this->generate_preset_css( $preset_settings );

        // Renderizar HTML
        $html_parts = array();
        if ( class_exists( 'Flavor_VBP_Canvas' ) ) {
            $canvas = Flavor_VBP_Canvas::get_instance();
            foreach ( $elements as $element ) {
                $html_parts[] = $canvas->render_element_public( $element );
            }
        } else {
            // Fallback: renderizado básico
            foreach ( $elements as $element ) {
                $html_parts[] = $this->render_element_basic( $element );
            }
        }

        $preview_html = implode( "\n", $html_parts );

        // Construir HTML completo con estilos
        $full_html = sprintf(
            '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview VBP</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        %s
    </style>
</head>
<body style="background-color: %s;">
    <div class="vbp-preview" style="max-width: %dpx; margin: 0 auto;">
        %s
    </div>
</body>
</html>',
            $preset_css,
            esc_attr( $final_settings['backgroundColor'] ),
            (int) $final_settings['pageWidth'],
            $preview_html
        );

        return new WP_REST_Response( array(
            'success'        => true,
            'html'           => $full_html,
            'elements_count' => count( $elements ),
            'preset'         => $preset,
            'settings'       => $final_settings,
        ), 200 );
    }

    /**
     * Renderizado básico de elemento (fallback)
     *
     * @param array $element Elemento a renderizar.
     * @return string HTML.
     */
    private function render_element_basic( $element ) {
        $type = $element['type'] ?? 'text';
        $data = $element['data'] ?? array();
        $html = '';

        switch ( $type ) {
            case 'hero':
                $html = sprintf(
                    '<section class="vbp-hero" style="padding: 80px 20px; background: %s; color: #fff; text-align: center;">
                        <h1>%s</h1>
                        <p>%s</p>
                    </section>',
                    esc_attr( $data['color_fondo'] ?? '#1a1a2e' ),
                    esc_html( $data['titulo'] ?? '' ),
                    esc_html( $data['subtitulo'] ?? '' )
                );
                break;

            case 'features':
                $items_html = '';
                $items = $data['items'] ?? array();
                foreach ( $items as $item ) {
                    $items_html .= sprintf(
                        '<div style="flex: 1; padding: 20px; text-align: center;">
                            <h3>%s</h3>
                            <p>%s</p>
                        </div>',
                        esc_html( $item['titulo'] ?? '' ),
                        esc_html( $item['descripcion'] ?? '' )
                    );
                }
                $html = sprintf(
                    '<section class="vbp-features" style="padding: 60px 20px;">
                        <h2 style="text-align: center;">%s</h2>
                        <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 40px;">%s</div>
                    </section>',
                    esc_html( $data['titulo'] ?? '' ),
                    $items_html
                );
                break;

            case 'cta':
                $html = sprintf(
                    '<section class="vbp-cta" style="padding: 60px 20px; background: #f5f5f5; text-align: center;">
                        <h2>%s</h2>
                        <p>%s</p>
                        <a href="%s" style="display: inline-block; margin-top: 20px; padding: 12px 30px; background: #333; color: #fff; text-decoration: none; border-radius: 5px;">%s</a>
                    </section>',
                    esc_html( $data['titulo'] ?? '' ),
                    esc_html( $data['subtitulo'] ?? '' ),
                    esc_url( $data['boton_url'] ?? '#' ),
                    esc_html( $data['boton_texto'] ?? 'Contactar' )
                );
                break;

            default:
                $html = sprintf(
                    '<div class="vbp-element vbp-%s" style="padding: 20px;">%s</div>',
                    esc_attr( $type ),
                    esc_html( $data['contenido'] ?? $data['texto'] ?? '' )
                );
        }

        return $html;
    }

    /**
     * Genera CSS de un preset de diseño
     *
     * @param array $preset Preset de diseño.
     * @return string CSS.
     */
    private function generate_preset_css( $preset ) {
        if ( empty( $preset ) ) {
            return '';
        }

        $colors = $preset['colors'] ?? array();
        $css = ':root {';

        foreach ( $colors as $name => $value ) {
            $css .= sprintf( '--vbp-%s: %s;', esc_attr( $name ), esc_attr( $value ) );
        }

        $css .= '}';

        // Añadir tipografía si existe
        if ( ! empty( $preset['typography']['font_family'] ) ) {
            $css .= sprintf(
                'body { font-family: %s; }',
                esc_attr( $preset['typography']['font_family'] )
            );
        }

        return $css;
    }
}
