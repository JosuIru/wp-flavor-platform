<?php
/**
 * Visual Builder Pro - Iframe Canvas (POC)
 *
 * Sirve el canvas del editor como un documento HTML completo renderizado
 * por el mismo pipeline PHP que usa el frontend (Flavor_VBP_Canvas), para
 * que la previsualizacion dentro del editor sea identica a lo que ve el
 * visitante del sitio.
 *
 * URL de acceso (frontend, requiere login + edit_post):
 *   /?flavor_vbp_iframe_canvas=1&post_id=123
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Iframe_Canvas {

    private static $instancia = null;

    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        add_action( 'template_redirect', array( $this, 'interceptar_peticion' ), 1 );
        add_action( 'rest_api_init', array( $this, 'registrar_rutas_rest' ) );
    }

    public function registrar_rutas_rest() {
        register_rest_route( 'flavor-vbp/v1', '/iframe-canvas/render-element', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'endpoint_render_elemento' ),
            'permission_callback' => array( $this, 'permisos_render_elemento' ),
            'args'                => array(
                'post_id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'element' => array(
                    'required' => true,
                    'type'     => 'object',
                ),
                'settings' => array(
                    'required' => false,
                    'type'     => 'object',
                ),
            ),
        ) );
    }

    public function permisos_render_elemento( $peticion_rest ) {
        $id_post_solicitado = absint( $peticion_rest->get_param( 'post_id' ) );
        if ( ! $id_post_solicitado ) {
            return new WP_Error( 'rest_missing_post_id', 'Falta post_id', array( 'status' => 400 ) );
        }
        if ( ! current_user_can( 'edit_post', $id_post_solicitado ) ) {
            return new WP_Error( 'rest_forbidden', 'Sin permiso', array( 'status' => 403 ) );
        }
        return true;
    }

    public function endpoint_render_elemento( $peticion_rest ) {
        $datos_elemento  = $peticion_rest->get_param( 'element' );
        $datos_settings  = $peticion_rest->get_param( 'settings' );
        $id_post_asociado = absint( $peticion_rest->get_param( 'post_id' ) );

        if ( empty( $datos_elemento ) || ! is_array( $datos_elemento ) ) {
            return new WP_Error( 'rest_invalid_element', 'Elemento invalido', array( 'status' => 400 ) );
        }

        // Contexto editor activo para inyectar contenteditable y wrapper
        if ( ! defined( 'VBP_EDITOR_CONTEXT' ) ) {
            define( 'VBP_EDITOR_CONTEXT', true );
        }

        $canvas_renderer = Flavor_VBP_Canvas::get_instance();
        $html_contenido  = $canvas_renderer->renderizar_elemento( $datos_elemento );

        // Envolver con el mismo wrapper que usa renderizar_documento en contexto editor
        $id_elemento = isset( $datos_elemento['id'] ) ? $datos_elemento['id'] : '';
        if ( $id_elemento ) {
            $html_final = sprintf(
                '<div class="vbp-iframe-element" data-element-id="%s" data-element-type="%s">%s</div>',
                esc_attr( $id_elemento ),
                esc_attr( $datos_elemento['type'] ?? '' ),
                $html_contenido
            );
        } else {
            $html_final = $html_contenido;
        }

        return rest_ensure_response( array(
            'success'    => true,
            'element_id' => $id_elemento,
            'html'       => $html_final,
        ) );
    }

    public function interceptar_peticion() {
        if ( empty( $_GET['flavor_vbp_iframe_canvas'] ) ) {
            return;
        }

        $id_post_solicitado = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
        if ( ! $id_post_solicitado ) {
            status_header( 400 );
            wp_die( esc_html__( 'Falta parametro post_id.', FLAVOR_PLATFORM_TEXT_DOMAIN ), '', array( 'response' => 400 ) );
        }

        if ( ! is_user_logged_in() ) {
            auth_redirect();
            exit;
        }

        if ( ! current_user_can( 'edit_post', $id_post_solicitado ) ) {
            status_header( 403 );
            wp_die( esc_html__( 'No tienes permiso para previsualizar este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN ), '', array( 'response' => 403 ) );
        }

        $post_solicitado = get_post( $id_post_solicitado );
        if ( ! $post_solicitado ) {
            status_header( 404 );
            wp_die( esc_html__( 'Post no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN ), '', array( 'response' => 404 ) );
        }

        $this->renderizar( $post_solicitado );
        exit;
    }

    private function renderizar( $post_solicitado ) {
        // Contexto editor: inyecta contenteditable y data-field en los bloques renderizados.
        if ( ! defined( 'VBP_EDITOR_CONTEXT' ) ) {
            define( 'VBP_EDITOR_CONTEXT', true );
        }

        // Obtener datos y renderizar body con el mismo motor del frontend.
        $editor_vbp       = Flavor_VBP_Editor::get_instance();
        $datos_documento  = $editor_vbp->obtener_datos_documento( $post_solicitado->ID );
        $canvas_renderer  = Flavor_VBP_Canvas::get_instance();
        $html_cuerpo      = $canvas_renderer->renderizar_documento( $datos_documento );

        $url_plugin  = FLAVOR_PLATFORM_URL;
        $version_css = defined( 'FLAVOR_PLATFORM_VERSION' ) ? FLAVOR_PLATFORM_VERSION : '2.3.0';

        // Assets de frontend - identicos a cargar_css_frontend() de class-vbp-canvas.php:56-141
        $assets_css = array(
            array( 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', null ),
            array( 'https://fonts.googleapis.com/icon?family=Material+Icons', null ),
            array( $url_plugin . 'assets/vbp/css/frontend-components.css', $version_css ),
            array( $url_plugin . 'assets/vbp/css/animations.css', $version_css ),
            array( $url_plugin . 'assets/vbp/css/editor-canvas-overlay.css', $version_css ),
        );

        $assets_js = array(
            array( $url_plugin . 'assets/vbp/js/vbp-frontend.js', $version_css ),
            array( $url_plugin . 'assets/vbp/js/vbp-animations.js', $version_css ),
            array( $url_plugin . 'assets/vbp/js/vbp-iframe-bridge.js', $version_css ),
        );

        // Config inline para vbp-frontend.js (AJAX + REST base)
        $config_inline = sprintf(
            'window.vbp_ajax_url = "%s"; window.VBP_Config = window.VBP_Config || {}; window.VBP_Config.restUrl = "%s"; window.VBP_Config.iframePostId = %d;',
            esc_js( admin_url( 'admin-ajax.php' ) ),
            esc_js( rest_url( 'flavor-vbp/v1/' ) ),
            (int) $post_solicitado->ID
        );

        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
        header( 'X-Frame-Options: SAMEORIGIN' );

        ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $post_solicitado->post_title ); ?> - VBP Canvas</title>
    <?php foreach ( $assets_css as $asset ) : ?>
        <link rel="stylesheet" href="<?php echo esc_url( $asset[0] . ( $asset[1] ? '?v=' . rawurlencode( $asset[1] ) : '' ) ); ?>">
    <?php endforeach; ?>
    <style id="vbp-iframe-canvas-base">
        html, body { margin: 0; padding: 0; }
        body.vbp-iframe-canvas-body { background: var(--flavor-bg, #ffffff); min-height: 100vh; }
    </style>
</head>
<body class="vbp-iframe-canvas-body single-flavor_landing" data-post-id="<?php echo (int) $post_solicitado->ID; ?>">
    <?php
    // Nota: no llamamos wp_head()/wp_footer() para mantener el documento
    // limpio, sin admin bar ni scripts del admin. Los assets necesarios
    // del canvas se cargan explicitamente arriba.
    echo $html_cuerpo; // ya sanitizado por el canvas renderer
    ?>
    <script><?php echo $config_inline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
    <?php foreach ( $assets_js as $asset ) : ?>
        <script src="<?php echo esc_url( $asset[0] . ( $asset[1] ? '?v=' . rawurlencode( $asset[1] ) : '' ) ); ?>"></script>
    <?php endforeach; ?>
</body>
</html>
<?php
    }

    public static function get_url_iframe( $id_post ) {
        return add_query_arg(
            array(
                'flavor_vbp_iframe_canvas' => 1,
                'post_id'                  => (int) $id_post,
            ),
            home_url( '/' )
        );
    }
}

Flavor_VBP_Iframe_Canvas::get_instance();
