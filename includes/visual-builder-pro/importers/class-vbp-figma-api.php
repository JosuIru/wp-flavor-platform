<?php
/**
 * Visual Builder Pro - Figma API Client
 *
 * Cliente para comunicarse con la API de Figma.
 *
 * @package FlavorChatIA
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cliente API de Figma
 */
class Flavor_VBP_Figma_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Figma_API|null
     */
    private static $instancia = null;

    /**
     * Base URL de la API de Figma
     *
     * @var string
     */
    const API_BASE_URL = 'https://api.figma.com/v1';

    /**
     * Token de acceso personal
     *
     * @var string|null
     */
    private $access_token = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Figma_API
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
        $this->load_token();
    }

    /**
     * Carga el token de acceso
     */
    private function load_token() {
        $user_id = get_current_user_id();
        if ( $user_id ) {
            $this->access_token = get_user_meta( $user_id, 'flavor_figma_token', true );
        }

        // Fallback a configuración global
        if ( empty( $this->access_token ) ) {
            $settings = get_option( 'flavor_chat_ia_settings', array() );
            $this->access_token = $settings['figma_personal_token'] ?? '';
        }
    }

    /**
     * Establece el token de acceso
     *
     * @param string $token Token de acceso.
     * @param bool   $save_to_user Si guardar en user_meta.
     */
    public function set_token( $token, $save_to_user = true ) {
        $this->access_token = $token;

        if ( $save_to_user ) {
            $user_id = get_current_user_id();
            if ( $user_id ) {
                update_user_meta( $user_id, 'flavor_figma_token', $token );
            }
        }
    }

    /**
     * Verifica si hay un token configurado
     *
     * @return bool
     */
    public function is_configured() {
        return ! empty( $this->access_token );
    }

    /**
     * Verifica la conexión con Figma
     *
     * @return array
     */
    public function verify_connection() {
        if ( ! $this->is_configured() ) {
            return array(
                'success' => false,
                'error'   => __( 'Token de Figma no configurado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            );
        }

        $response = $this->make_request( '/me' );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message(),
            );
        }

        return array(
            'success' => true,
            'user'    => array(
                'id'     => $response['id'] ?? '',
                'email'  => $response['email'] ?? '',
                'handle' => $response['handle'] ?? '',
            ),
        );
    }

    /**
     * Obtiene información de un archivo de Figma
     *
     * @param string $file_key Key del archivo.
     * @return array|WP_Error
     */
    public function get_file( $file_key ) {
        return $this->make_request( "/files/{$file_key}" );
    }

    /**
     * Obtiene nodos específicos de un archivo
     *
     * @param string $file_key Key del archivo.
     * @param array  $node_ids IDs de los nodos.
     * @return array|WP_Error
     */
    public function get_file_nodes( $file_key, $node_ids ) {
        $ids = implode( ',', $node_ids );
        return $this->make_request( "/files/{$file_key}/nodes?ids={$ids}" );
    }

    /**
     * Obtiene imágenes de nodos
     *
     * @param string $file_key Key del archivo.
     * @param array  $node_ids IDs de los nodos.
     * @param string $format Formato de imagen (jpg, png, svg, pdf).
     * @param int    $scale Escala (1-4).
     * @return array|WP_Error
     */
    public function get_images( $file_key, $node_ids, $format = 'png', $scale = 2 ) {
        $ids = implode( ',', $node_ids );
        return $this->make_request( "/images/{$file_key}?ids={$ids}&format={$format}&scale={$scale}" );
    }

    /**
     * Obtiene los estilos de un archivo
     *
     * @param string $file_key Key del archivo.
     * @return array|WP_Error
     */
    public function get_file_styles( $file_key ) {
        return $this->make_request( "/files/{$file_key}/styles" );
    }

    /**
     * Obtiene los componentes de un archivo
     *
     * @param string $file_key Key del archivo.
     * @return array|WP_Error
     */
    public function get_file_components( $file_key ) {
        return $this->make_request( "/files/{$file_key}/components" );
    }

    /**
     * Parsea una URL de Figma para extraer file_key y node_id
     *
     * @param string $url URL de Figma.
     * @return array|WP_Error
     */
    public function parse_url( $url ) {
        // Patrones de URL de Figma:
        // https://www.figma.com/file/{file_key}/{file_name}?node-id={node_id}
        // https://www.figma.com/design/{file_key}/{file_name}?node-id={node_id}

        $patterns = array(
            '/figma\.com\/(?:file|design)\/([a-zA-Z0-9]+)\/[^?]*(?:\?.*node-id=([0-9:-]+))?/',
            '/figma\.com\/(?:file|design)\/([a-zA-Z0-9]+)/',
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $url, $matches ) ) {
                $result = array(
                    'file_key' => $matches[1],
                    'node_id'  => isset( $matches[2] ) ? $matches[2] : null,
                );

                // Convertir node-id de formato URL a formato API
                if ( $result['node_id'] ) {
                    $result['node_id'] = str_replace( '-', ':', $result['node_id'] );
                }

                return $result;
            }
        }

        return new WP_Error(
            'invalid_url',
            __( 'URL de Figma no válida', FLAVOR_PLATFORM_TEXT_DOMAIN )
        );
    }

    /**
     * Realiza una petición a la API de Figma
     *
     * @param string $endpoint Endpoint de la API.
     * @param string $method Método HTTP.
     * @param array  $body Body de la petición.
     * @return array|WP_Error
     */
    private function make_request( $endpoint, $method = 'GET', $body = null ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error(
                'no_token',
                __( 'Token de Figma no configurado', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        $url = self::API_BASE_URL . $endpoint;

        $args = array(
            'method'  => $method,
            'headers' => array(
                'X-Figma-Token' => $this->access_token,
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 30,
        );

        if ( $body && 'POST' === $method ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $status_code !== 200 ) {
            $error_message = $data['err'] ?? $data['message'] ?? __( 'Error en la API de Figma', FLAVOR_PLATFORM_TEXT_DOMAIN );

            if ( $status_code === 403 ) {
                $error_message = __( 'Token de Figma no válido o sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN );
            } elseif ( $status_code === 404 ) {
                $error_message = __( 'Archivo o nodo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN );
            }

            return new WP_Error(
                'figma_api_error',
                $error_message,
                array( 'status' => $status_code )
            );
        }

        return $data;
    }

    /**
     * Descarga una imagen de Figma y la guarda localmente
     *
     * @param string $image_url URL de la imagen.
     * @param string $filename Nombre del archivo.
     * @return string|WP_Error URL local de la imagen.
     */
    public function download_image( $image_url, $filename = null ) {
        if ( empty( $image_url ) ) {
            return new WP_Error( 'no_url', __( 'URL de imagen vacía', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        // Descargar la imagen
        $response = wp_remote_get( $image_url, array( 'timeout' => 30 ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $image_data = wp_remote_retrieve_body( $response );
        $content_type = wp_remote_retrieve_header( $response, 'content-type' );

        // Determinar extensión
        $extension = 'png';
        if ( strpos( $content_type, 'jpeg' ) !== false || strpos( $content_type, 'jpg' ) !== false ) {
            $extension = 'jpg';
        } elseif ( strpos( $content_type, 'svg' ) !== false ) {
            $extension = 'svg';
        }

        // Generar nombre de archivo si no se proporciona
        if ( ! $filename ) {
            $filename = 'figma-import-' . uniqid() . '.' . $extension;
        } else {
            $filename = sanitize_file_name( $filename ) . '.' . $extension;
        }

        // Subir a la biblioteca de medios
        $upload = wp_upload_bits( $filename, null, $image_data );

        if ( $upload['error'] ) {
            return new WP_Error( 'upload_error', $upload['error'] );
        }

        // Crear attachment
        $attachment_id = wp_insert_attachment(
            array(
                'post_mime_type' => $content_type,
                'post_title'     => pathinfo( $filename, PATHINFO_FILENAME ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ),
            $upload['file']
        );

        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        // Generar metadatos
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
        wp_update_attachment_metadata( $attachment_id, $attachment_data );

        return wp_get_attachment_url( $attachment_id );
    }

    /**
     * Obtiene información básica de un archivo
     *
     * @param string $file_key Key del archivo.
     * @return array|WP_Error
     */
    public function get_file_info( $file_key ) {
        $response = $this->get_file( $file_key );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return array(
            'name'          => $response['name'] ?? '',
            'lastModified'  => $response['lastModified'] ?? '',
            'version'       => $response['version'] ?? '',
            'thumbnailUrl'  => $response['thumbnailUrl'] ?? '',
            'document'      => $response['document'] ?? null,
            'pages'         => $this->extract_pages( $response['document'] ?? array() ),
        );
    }

    /**
     * Extrae las páginas de un documento
     *
     * @param array $document Documento de Figma.
     * @return array
     */
    private function extract_pages( $document ) {
        if ( empty( $document['children'] ) ) {
            return array();
        }

        $pages = array();

        foreach ( $document['children'] as $child ) {
            if ( 'CANVAS' === ( $child['type'] ?? '' ) ) {
                $pages[] = array(
                    'id'       => $child['id'],
                    'name'     => $child['name'],
                    'children' => count( $child['children'] ?? array() ),
                );
            }
        }

        return $pages;
    }
}
