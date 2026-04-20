<?php
/**
 * Visual Builder Pro - Unsplash Integration
 *
 * Integración con la API de Unsplash para imágenes de stock.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para integración con Unsplash
 *
 * @since 2.0.0
 */
class Flavor_VBP_Unsplash {

    /**
     * URL base de la API de Unsplash
     *
     * @var string
     */
    const API_URL = 'https://api.unsplash.com';

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Unsplash|null
     */
    private static $instancia = null;

    /**
     * Access Key de Unsplash
     *
     * @var string
     */
    private $access_key;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Unsplash
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->access_key = $this->get_access_key();
        add_action( 'rest_api_init', array( $this, 'registrar_rutas_rest' ) );
        add_action( 'admin_init', array( $this, 'registrar_settings' ) );
    }

    /**
     * Obtiene el Access Key de Unsplash
     *
     * @return string
     */
    private function get_access_key() {
        return get_option( 'vbp_unsplash_access_key', '' );
    }

    /**
     * Verifica si la API está configurada
     *
     * @return bool
     */
    public function esta_configurado() {
        return ! empty( $this->access_key );
    }

    /**
     * Registra los settings de Unsplash
     */
    public function registrar_settings() {
        register_setting( 'vbp_settings', 'vbp_unsplash_access_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
    }

    /**
     * Registra las rutas REST API
     */
    public function registrar_rutas_rest() {
        $namespace = 'flavor-vbp/v1';

        // Buscar imágenes
        register_rest_route(
            $namespace,
            '/unsplash/search',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'buscar_imagenes' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
                'args'                => array(
                    'query'    => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'page'     => array(
                        'default'           => 1,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default'           => 20,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'orientation' => array(
                        'default'           => '',
                        'type'              => 'string',
                        'enum'              => array( '', 'landscape', 'portrait', 'squarish' ),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Obtener imagen específica
        register_rest_route(
            $namespace,
            '/unsplash/photos/(?P<id>[a-zA-Z0-9_-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_imagen' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
            )
        );

        // Descargar/trackear descarga (requerido por Unsplash)
        register_rest_route(
            $namespace,
            '/unsplash/photos/(?P<id>[a-zA-Z0-9_-]+)/download',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'registrar_descarga' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
            )
        );

        // Imágenes populares/curadas
        register_rest_route(
            $namespace,
            '/unsplash/curated',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_curadas' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
                'args'                => array(
                    'page'     => array(
                        'default'           => 1,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default'           => 20,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Estado de configuración
        register_rest_route(
            $namespace,
            '/unsplash/status',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_estado' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
            )
        );
    }

    /**
     * Verifica permiso de acceso
     *
     * @return bool
     */
    public function verificar_permiso() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Busca imágenes en Unsplash
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function buscar_imagenes( $request ) {
        if ( ! $this->esta_configurado() ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Unsplash no está configurado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                400
            );
        }

        $query       = $request->get_param( 'query' );
        $page        = $request->get_param( 'page' );
        $per_page    = min( 30, $request->get_param( 'per_page' ) );
        $orientation = $request->get_param( 'orientation' );

        $url = add_query_arg(
            array(
                'query'       => $query,
                'page'        => $page,
                'per_page'    => $per_page,
                'orientation' => $orientation ?: null,
            ),
            self::API_URL . '/search/photos'
        );

        $response = $this->hacer_peticion( $url );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response(
                array( 'error' => $response->get_error_message() ),
                500
            );
        }

        return new WP_REST_Response(
            array(
                'results'    => $this->formatear_imagenes( $response['results'] ?? array() ),
                'total'      => $response['total'] ?? 0,
                'totalPages' => $response['total_pages'] ?? 0,
            ),
            200
        );
    }

    /**
     * Obtiene una imagen específica
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_imagen( $request ) {
        if ( ! $this->esta_configurado() ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Unsplash no está configurado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                400
            );
        }

        $id       = $request->get_param( 'id' );
        $url      = self::API_URL . '/photos/' . $id;
        $response = $this->hacer_peticion( $url );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response(
                array( 'error' => $response->get_error_message() ),
                500
            );
        }

        return new WP_REST_Response(
            $this->formatear_imagen( $response ),
            200
        );
    }

    /**
     * Registra una descarga (requerido por Unsplash API Guidelines)
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function registrar_descarga( $request ) {
        if ( ! $this->esta_configurado() ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Unsplash no está configurado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                400
            );
        }

        $id  = $request->get_param( 'id' );
        $url = self::API_URL . '/photos/' . $id . '/download';

        $response = $this->hacer_peticion( $url );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response(
                array( 'error' => $response->get_error_message() ),
                500
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'url'     => $response['url'] ?? '',
            ),
            200
        );
    }

    /**
     * Obtiene imágenes curadas/populares
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_curadas( $request ) {
        if ( ! $this->esta_configurado() ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Unsplash no está configurado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
                400
            );
        }

        $page     = $request->get_param( 'page' );
        $per_page = min( 30, $request->get_param( 'per_page' ) );

        $url = add_query_arg(
            array(
                'page'     => $page,
                'per_page' => $per_page,
                'order_by' => 'popular',
            ),
            self::API_URL . '/photos'
        );

        $response = $this->hacer_peticion( $url );

        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response(
                array( 'error' => $response->get_error_message() ),
                500
            );
        }

        return new WP_REST_Response(
            array(
                'results' => $this->formatear_imagenes( $response ),
            ),
            200
        );
    }

    /**
     * Obtiene el estado de configuración
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_estado( $request ) {
        return new WP_REST_Response(
            array(
                'configured' => $this->esta_configurado(),
                'message'    => $this->esta_configurado()
                    ? __( 'Unsplash está configurado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN )
                    : __( 'Configura tu Access Key de Unsplash en los ajustes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            200
        );
    }

    /**
     * Hace una petición a la API de Unsplash
     *
     * @param string $url URL de la API.
     * @return array|WP_Error
     */
    private function hacer_peticion( $url ) {
        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Client-ID ' . $this->access_key,
                    'Accept'        => 'application/json',
                ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code !== 200 ) {
            $mensaje = isset( $data['errors'][0] ) ? $data['errors'][0] : __( 'Error en la API de Unsplash', FLAVOR_PLATFORM_TEXT_DOMAIN );
            return new WP_Error( 'unsplash_error', $mensaje );
        }

        return $data;
    }

    /**
     * Formatea un array de imágenes
     *
     * @param array $imagenes Imágenes de la API.
     * @return array
     */
    private function formatear_imagenes( $imagenes ) {
        return array_map( array( $this, 'formatear_imagen' ), $imagenes );
    }

    /**
     * Formatea una imagen individual
     *
     * @param array $imagen Datos de la imagen.
     * @return array
     */
    private function formatear_imagen( $imagen ) {
        return array(
            'id'          => $imagen['id'],
            'width'       => $imagen['width'],
            'height'      => $imagen['height'],
            'color'       => $imagen['color'],
            'description' => $imagen['description'] ?? $imagen['alt_description'] ?? '',
            'urls'        => array(
                'raw'     => $imagen['urls']['raw'],
                'full'    => $imagen['urls']['full'],
                'regular' => $imagen['urls']['regular'],
                'small'   => $imagen['urls']['small'],
                'thumb'   => $imagen['urls']['thumb'],
            ),
            'user'        => array(
                'name'     => $imagen['user']['name'],
                'username' => $imagen['user']['username'],
                'link'     => $imagen['user']['links']['html'],
            ),
            'links'       => array(
                'html'     => $imagen['links']['html'],
                'download' => $imagen['links']['download_location'] ?? '',
            ),
        );
    }

    /**
     * Genera el HTML de atribución para una imagen
     *
     * @param array $imagen Datos de la imagen.
     * @return string
     */
    public function generar_atribucion( $imagen ) {
        $nombre   = esc_html( $imagen['user']['name'] );
        $username = esc_attr( $imagen['user']['username'] );
        $link     = esc_url( $imagen['user']['link'] );

        return sprintf(
            '<span class="vbp-unsplash-attribution">%s <a href="%s?utm_source=flavor_vbp&utm_medium=referral" target="_blank" rel="noopener">%s</a> %s <a href="https://unsplash.com/?utm_source=flavor_vbp&utm_medium=referral" target="_blank" rel="noopener">Unsplash</a></span>',
            __( 'Foto de', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            $link,
            $nombre,
            __( 'en', FLAVOR_PLATFORM_TEXT_DOMAIN )
        );
    }
}
