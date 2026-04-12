<?php
/**
 * Trait para Screenshots y PDF VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_ScreenshotsPDF {

    /**
     * Preview multi-dispositivo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_multi_device_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $devices = $request->get_param( 'devices' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $device_configs = array(
            'desktop' => array( 'width' => 1920, 'height' => 1080, 'scale' => 0.5 ),
            'laptop'  => array( 'width' => 1366, 'height' => 768, 'scale' => 0.6 ),
            'tablet'  => array( 'width' => 768, 'height' => 1024, 'scale' => 0.7 ),
            'mobile'  => array( 'width' => 375, 'height' => 812, 'scale' => 0.8 ),
        );

        $previews = array();
        $base_url = get_permalink( $page_id );

        foreach ( $devices as $device ) {
            if ( isset( $device_configs[ $device ] ) ) {
                $config = $device_configs[ $device ];
                $previews[ $device ] = array(
                    'device'      => $device,
                    'url'         => add_query_arg( 'vbp_preview_device', $device, $base_url ),
                    'width'       => $config['width'],
                    'height'      => $config['height'],
                    'scale'       => $config['scale'],
                    'iframe_url'  => add_query_arg( array(
                        'vbp_preview'        => '1',
                        'vbp_preview_device' => $device,
                    ), $base_url ),
                );
            }
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'page_id'  => $page_id,
            'previews' => $previews,
        ), 200 );
    }

    /**
     * Compara páginas en preview
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function compare_pages_preview( $request ) {
        $page_ids = $request->get_param( 'page_ids' );
        $device = sanitize_text_field( $request->get_param( 'device' ) );

        $comparisons = array();

        foreach ( $page_ids as $page_id ) {
            $post = get_post( (int) $page_id );
            if ( ! $this->is_valid_vbp_post( $post ) ) {
                continue;
            }

            $comparisons[] = array(
                'page_id'    => $page_id,
                'title'      => $post->post_title,
                'url'        => get_permalink( $page_id ),
                'preview_url' => add_query_arg( array(
                    'vbp_preview'        => '1',
                    'vbp_preview_device' => $device,
                ), get_permalink( $page_id ) ),
                'status'     => $post->post_status,
            );
        }

        return new WP_REST_Response( array(
            'success'     => true,
            'device'      => $device,
            'comparisons' => $comparisons,
        ), 200 );
    }

    /**
     * Captura screenshot de página VBP
     *
     * Fase 3: Implementación real con múltiples proveedores.
     * Soporta: Browserless, ScreenshotOne, o generación local con Puppeteer.
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function capture_page_screenshot( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $device = sanitize_text_field( $request->get_param( 'device' ) ) ?: 'desktop';
        $full_page = (bool) $request->get_param( 'full_page' );
        $format = sanitize_text_field( $request->get_param( 'format' ) ) ?: 'png';
        $quality = (int) $request->get_param( 'quality' ) ?: 90;

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $page_url = get_permalink( $page_id );
        $settings = get_option( 'flavor_vbp_screenshot_settings', array() );

        // Dimensiones por dispositivo
        $viewport = $this->get_device_viewport( $device );

        // Intentar con proveedores configurados
        $screenshot_result = null;

        // Proveedor 1: Browserless (self-hosted o cloud)
        if ( ! empty( $settings['browserless_token'] ) ) {
            $screenshot_result = $this->capture_with_browserless(
                $page_url,
                $settings['browserless_token'],
                $settings['browserless_url'] ?? 'https://chrome.browserless.io',
                $viewport,
                $full_page,
                $format,
                $quality
            );
        }

        // Proveedor 2: ScreenshotOne API
        if ( ! $screenshot_result && ! empty( $settings['screenshotone_key'] ) ) {
            $screenshot_result = $this->capture_with_screenshotone(
                $page_url,
                $settings['screenshotone_key'],
                $viewport,
                $full_page,
                $format
            );
        }

        // Proveedor 3: Generar paquete para captura local con Puppeteer
        if ( ! $screenshot_result ) {
            $screenshot_result = $this->generate_screenshot_package( $page_id, $viewport, $full_page );
        }

        if ( is_wp_error( $screenshot_result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $screenshot_result->get_error_message(),
            ), 500 );
        }

        return new WP_REST_Response( array_merge( array(
            'success'  => true,
            'page_id'  => $page_id,
            'device'   => $device,
            'viewport' => $viewport,
            'format'   => $format,
        ), $screenshot_result ), 200 );
    }

    /**
     * Captura con Browserless
     *
     * @param string $url              URL a capturar.
     * @param string $token            Token de API.
     * @param string $browserless_url  URL del servicio.
     * @param array  $viewport         Dimensiones.
     * @param bool   $full_page        Captura completa.
     * @param string $format           Formato de imagen.
     * @param int    $quality          Calidad.
     * @return array|null
     */
    private function capture_with_browserless( $url, $token, $browserless_url, $viewport, $full_page, $format, $quality ) {
        $api_url = trailingslashit( $browserless_url ) . 'screenshot?token=' . $token;

        $body = array(
            'url'     => $url,
            'options' => array(
                'fullPage' => $full_page,
                'type'     => $format,
            ),
            'viewport' => array(
                'width'  => $viewport['width'],
                'height' => $viewport['height'],
            ),
        );

        if ( 'jpeg' === $format || 'jpg' === $format ) {
            $body['options']['quality'] = $quality;
        }

        $response = wp_remote_post( $api_url, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return null;
        }

        $image_data = wp_remote_retrieve_body( $response );

        // Guardar en uploads
        $upload_dir = wp_upload_dir();
        $filename = 'vbp-screenshot-' . time() . '.' . $format;
        $filepath = $upload_dir['path'] . '/' . $filename;

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $filepath, $image_data );

        return array(
            'provider'       => 'browserless',
            'screenshot_url' => $upload_dir['url'] . '/' . $filename,
            'file_path'      => $filepath,
            'file_size'      => strlen( $image_data ),
        );
    }

    /**
     * Captura con ScreenshotOne
     *
     * @param string $url       URL a capturar.
     * @param string $api_key   Clave API.
     * @param array  $viewport  Dimensiones.
     * @param bool   $full_page Captura completa.
     * @param string $format    Formato.
     * @return array|null
     */
    private function capture_with_screenshotone( $url, $api_key, $viewport, $full_page, $format ) {
        $api_url = add_query_arg( array(
            'access_key'      => $api_key,
            'url'             => rawurlencode( $url ),
            'viewport_width'  => $viewport['width'],
            'viewport_height' => $viewport['height'],
            'full_page'       => $full_page ? 'true' : 'false',
            'format'          => $format,
        ), 'https://api.screenshotone.com/take' );

        $response = wp_remote_get( $api_url, array( 'timeout' => 30 ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return null;
        }

        $image_data = wp_remote_retrieve_body( $response );

        $upload_dir = wp_upload_dir();
        $filename = 'vbp-screenshot-' . time() . '.' . $format;
        $filepath = $upload_dir['path'] . '/' . $filename;

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $filepath, $image_data );

        return array(
            'provider'       => 'screenshotone',
            'screenshot_url' => $upload_dir['url'] . '/' . $filename,
            'file_path'      => $filepath,
        );
    }

    /**
     * Genera paquete para captura local con Puppeteer
     *
     * @param int   $page_id   ID de página.
     * @param array $viewport  Dimensiones.
     * @param bool  $full_page Captura completa.
     * @return array
     */
    private function generate_screenshot_package( $page_id, $viewport, $full_page ) {
        $page_url = get_permalink( $page_id );

        // Generar script de Puppeteer
        $puppeteer_script = $this->generate_puppeteer_script( $page_url, $viewport, $full_page );

        return array(
            'provider'         => 'local',
            'requires_setup'   => true,
            'page_url'         => $page_url,
            'puppeteer_script' => $puppeteer_script,
            'instructions'     => array(
                'Para capturar screenshots localmente:',
                '1. Instalar Node.js y Puppeteer: npm install puppeteer',
                '2. Guardar el script puppeteer_script como capture.js',
                '3. Ejecutar: node capture.js',
                'O configurar un servicio externo en Ajustes > VBP > Screenshots',
            ),
        );
    }

    /**
     * Genera script de Puppeteer
     *
     * @param string $url       URL a capturar.
     * @param array  $viewport  Dimensiones.
     * @param bool   $full_page Captura completa.
     * @return string
     */
    private function generate_puppeteer_script( $url, $viewport, $full_page ) {
        $full_page_str = $full_page ? 'true' : 'false';
        $width = $viewport['width'];
        $height = $viewport['height'];

        return "const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    await page.setViewport({
        width: {$width},
        height: {$height}
    });

    await page.goto('{$url}', { waitUntil: 'networkidle2' });

    await page.screenshot({
        path: 'screenshot.png',
        fullPage: {$full_page_str}
    });

    await browser.close();
    console.log('Screenshot saved to screenshot.png');
})();
";
    }

    /**
     * Obtiene viewport por tipo de dispositivo
     *
     * @param string $device Tipo de dispositivo.
     * @return array
     */
    private function get_device_viewport( $device ) {
        $viewports = array(
            'desktop'   => array( 'width' => 1920, 'height' => 1080 ),
            'laptop'    => array( 'width' => 1366, 'height' => 768 ),
            'tablet'    => array( 'width' => 768, 'height' => 1024 ),
            'mobile'    => array( 'width' => 375, 'height' => 812 ),
            'mobile-lg' => array( 'width' => 414, 'height' => 896 ),
        );

        return $viewports[ $device ] ?? $viewports['desktop'];
    }

    /**
     * Preview personalizado con datos de usuario
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_personalized_preview( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $user_data = $request->get_param( 'user_data' ) ?: array();
        $location = $request->get_param( 'location' ) ?: array();
        $device_type = sanitize_text_field( $request->get_param( 'device_type' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        // Generar token de preview personalizado
        $preview_token = wp_generate_password( 32, false );
        $preview_data = array(
            'page_id'     => $page_id,
            'user_data'   => $user_data,
            'location'    => $location,
            'device_type' => $device_type,
            'created_at'  => time(),
        );

        set_transient( 'vbp_personalized_preview_' . $preview_token, $preview_data, HOUR_IN_SECONDS );

        $preview_url = add_query_arg( array(
            'vbp_personalized' => $preview_token,
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'preview_url' => $preview_url,
            'token'       => $preview_token,
            'expires_in'  => 3600,
        ), 200 );
    }

    /**
     * Preview de cambios sin guardar
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function preview_draft_changes( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $elements = $request->get_param( 'elements' );
        $device = sanitize_text_field( $request->get_param( 'device' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        // Generar token de preview temporal
        $preview_token = wp_generate_password( 32, false );

        set_transient( 'vbp_draft_preview_' . $preview_token, array(
            'page_id'    => $page_id,
            'elements'   => $elements,
            'device'     => $device,
            'created_at' => time(),
        ), 15 * MINUTE_IN_SECONDS );

        $preview_url = add_query_arg( array(
            'vbp_draft_preview' => $preview_token,
            'device'            => $device,
        ), get_permalink( $page_id ) );

        return new WP_REST_Response( array(
            'success'     => true,
            'preview_url' => $preview_url,
            'token'       => $preview_token,
            'expires_in'  => 900,
        ), 200 );
    }

    /**
     * Exporta página VBP como PDF
     *
     * Fase 3: Implementación real con DomPDF/TCPDF o generación de HTML.
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_preview_pdf( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_notes = (bool) $request->get_param( 'include_notes' );
        $format = sanitize_text_field( $request->get_param( 'format' ) ) ?: 'A4';

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        // Generar HTML para el PDF
        $html_content = $this->generate_pdf_html( $post, $elements, $include_notes );
        $css_content = $this->elements_to_css( $elements );

        // Intentar generar PDF con librerías disponibles
        $pdf_result = null;

        if ( class_exists( 'Dompdf\\Dompdf' ) ) {
            $pdf_result = $this->generate_pdf_with_dompdf( $html_content, $css_content, $post->post_title, $format );
        } elseif ( class_exists( 'TCPDF' ) ) {
            $pdf_result = $this->generate_pdf_with_tcpdf( $html_content, $post->post_title, $format );
        }

        // Si no hay librería PDF disponible, devolver HTML para conversión externa
        if ( ! $pdf_result ) {
            $pdf_result = $this->generate_pdf_package( $html_content, $css_content, $post->post_title );
        }

        if ( is_wp_error( $pdf_result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => $pdf_result->get_error_message(),
            ), 500 );
        }

        return new WP_REST_Response( array_merge( array(
            'success'       => true,
            'page_id'       => $page_id,
            'title'         => $post->post_title,
            'format'        => $format,
            'include_notes' => $include_notes,
        ), $pdf_result ), 200 );
    }

    /**
     * Genera HTML optimizado para exportar a PDF
     *
     * @param WP_Post $post          Post.
     * @param array   $elements      Elementos VBP.
     * @param bool    $include_notes Incluir notas de revisión.
     * @return string
     */
    private function generate_pdf_html( $post, $elements, $include_notes ) {
        $title = esc_html( $post->post_title );
        $date = date_i18n( get_option( 'date_format' ), strtotime( $post->post_modified ) );
        $content = $this->elements_to_html( $elements );

        $notes_html = '';
        if ( $include_notes ) {
            $notes = get_post_meta( $post->ID, '_vbp_review_comments', true ) ?: array();
            if ( ! empty( $notes ) ) {
                $notes_html = '<div class="pdf-notes"><h2>Notas de Revisión</h2><ul>';
                foreach ( $notes as $note ) {
                    $notes_html .= '<li>' . esc_html( $note['content'] ?? '' ) . '</li>';
                }
                $notes_html .= '</ul></div>';
            }
        }

        return "<!DOCTYPE html>
<html lang=\"es\">
<head>
    <meta charset=\"UTF-8\">
    <title>{$title}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 40px; color: #333; }
        .pdf-header { border-bottom: 2px solid #3b82f6; padding-bottom: 20px; margin-bottom: 30px; }
        .pdf-header h1 { margin: 0; font-size: 28px; color: #1e293b; }
        .pdf-header .meta { color: #64748b; font-size: 12px; margin-top: 10px; }
        .vbp-page { max-width: 100%; }
        .vbp-section { margin-bottom: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; }
        .vbp-heading { font-size: 22px; color: #1e293b; margin-bottom: 15px; }
        .vbp-text, .vbp-paragraph { line-height: 1.6; margin-bottom: 10px; }
        .vbp-button { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; border-radius: 6px; }
        .vbp-image { max-width: 100%; height: auto; border-radius: 8px; }
        .pdf-notes { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .pdf-notes h2 { font-size: 18px; color: #64748b; }
        .pdf-notes li { margin-bottom: 10px; color: #475569; }
        .pdf-footer { margin-top: 40px; text-align: center; color: #94a3b8; font-size: 11px; }
    </style>
</head>
<body>
    <div class=\"pdf-header\">
        <h1>{$title}</h1>
        <div class=\"meta\">Generado el {$date} · Flavor VBP</div>
    </div>
    <div class=\"vbp-page\">
{$content}
    </div>
    {$notes_html}
    <div class=\"pdf-footer\">Documento generado por Flavor Visual Builder Pro</div>
</body>
</html>";
    }

    /**
     * Genera PDF usando DomPDF
     *
     * @param string $html   Contenido HTML.
     * @param string $css    Estilos CSS adicionales.
     * @param string $title  Título del documento.
     * @param string $format Formato de página (A4, Letter, etc).
     * @return array|WP_Error
     */
    private function generate_pdf_with_dompdf( $html, $css, $title, $format ) {
        try {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml( $html );
            $dompdf->setPaper( $format, 'portrait' );
            $dompdf->render();

            $pdf_content = $dompdf->output();

            $upload_dir = wp_upload_dir();
            $filename = sanitize_file_name( $title ) . '-' . time() . '.pdf';
            $filepath = $upload_dir['path'] . '/' . $filename;

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $filepath, $pdf_content );

            return array(
                'provider'  => 'dompdf',
                'pdf_url'   => $upload_dir['url'] . '/' . $filename,
                'file_path' => $filepath,
                'file_size' => strlen( $pdf_content ),
            );
        } catch ( \Exception $e ) {
            return new WP_Error( 'pdf_generation_error', $e->getMessage() );
        }
    }

    /**
     * Genera PDF usando TCPDF
     *
     * @param string $html   Contenido HTML.
     * @param string $title  Título del documento.
     * @param string $format Formato de página.
     * @return array|WP_Error
     */
    private function generate_pdf_with_tcpdf( $html, $title, $format ) {
        try {
            $pdf = new \TCPDF( 'P', 'mm', $format, true, 'UTF-8', false );
            $pdf->SetCreator( 'Flavor VBP' );
            $pdf->SetAuthor( 'Flavor Platform' );
            $pdf->SetTitle( $title );
            $pdf->AddPage();
            $pdf->writeHTML( $html, true, false, true, false, '' );

            $upload_dir = wp_upload_dir();
            $filename = sanitize_file_name( $title ) . '-' . time() . '.pdf';
            $filepath = $upload_dir['path'] . '/' . $filename;

            $pdf->Output( $filepath, 'F' );

            return array(
                'provider'  => 'tcpdf',
                'pdf_url'   => $upload_dir['url'] . '/' . $filename,
                'file_path' => $filepath,
            );
        } catch ( \Exception $e ) {
            return new WP_Error( 'pdf_generation_error', $e->getMessage() );
        }
    }

    /**
     * Genera paquete HTML para conversión externa a PDF
     *
     * @param string $html  Contenido HTML.
     * @param string $css   Estilos CSS.
     * @param string $title Título del documento.
     * @return array
     */
    private function generate_pdf_package( $html, $css, $title ) {
        $upload_dir = wp_upload_dir();
        $html_filename = sanitize_file_name( $title ) . '-' . time() . '.html';
        $html_filepath = $upload_dir['path'] . '/' . $html_filename;

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $html_filepath, $html );

        return array(
            'provider'       => 'html',
            'requires_setup' => true,
            'html_url'       => $upload_dir['url'] . '/' . $html_filename,
            'html_content'   => $html,
            'instructions'   => array(
                'No hay librería PDF instalada. Opciones disponibles:',
                '1. Instalar DomPDF: composer require dompdf/dompdf',
                '2. Abrir el HTML en navegador y guardar como PDF',
                '3. Usar wkhtmltopdf: wkhtmltopdf archivo.html salida.pdf',
            ),
        );
    }

    // =============================================
}
