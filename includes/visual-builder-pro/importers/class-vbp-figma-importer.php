<?php
/**
 * Visual Builder Pro - Figma Importer
 *
 * Controlador principal para importación de diseños de Figma.
 *
 * @package FlavorPlatform
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Controlador de importación de Figma
 */
class Flavor_VBP_Figma_Importer {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Figma_Importer|null
     */
    private static $instancia = null;

    /**
     * Cliente API de Figma
     *
     * @var Flavor_VBP_Figma_API|null
     */
    private $api = null;

    /**
     * Conversor de nodos
     *
     * @var Flavor_VBP_Figma_Converter|null
     */
    private $converter = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Figma_Importer
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
        $this->load_dependencies();
        $this->register_rest_routes();
        $this->register_ajax_handlers();
    }

    /**
     * Carga dependencias
     */
    private function load_dependencies() {
        require_once __DIR__ . '/class-vbp-figma-api.php';
        require_once __DIR__ . '/class-vbp-figma-converter.php';

        $this->api = Flavor_VBP_Figma_API::get_instance();
        $this->converter = Flavor_VBP_Figma_Converter::get_instance();
    }

    /**
     * Registra rutas REST
     */
    private function register_rest_routes() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registra handlers AJAX
     */
    private function register_ajax_handlers() {
        add_action( 'wp_ajax_flavor_verify_figma_token', array( $this, 'ajax_verify_token' ) );
        add_action( 'wp_ajax_flavor_save_figma_token', array( $this, 'ajax_save_token' ) );
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        $namespace = 'flavor-vbp/v1';

        // Importar desde Figma
        register_rest_route(
            $namespace,
            '/import-figma',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'import_from_figma' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'file_key'      => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'node_id'       => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'url'           => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ),
                    'import_images' => array(
                        'required'          => false,
                        'type'              => 'boolean',
                        'default'           => true,
                    ),
                ),
            )
        );

        // Previsualizar estructura de Figma
        register_rest_route(
            $namespace,
            '/preview-figma',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'preview_figma' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'file_key' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'url'      => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ),
                ),
            )
        );

        // Verificar conexión con Figma
        register_rest_route(
            $namespace,
            '/figma-status',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_figma_status' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );
    }

    /**
     * Verifica permisos
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Importa diseño desde Figma
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function import_from_figma( $request ) {
        // Verificar configuración
        if ( ! $this->api->is_configured() ) {
            return new WP_Error(
                'figma_not_configured',
                __( 'Token de Figma no configurado. Configúralo en Ajustes > Chat IA.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Obtener parámetros
        $file_key = $request->get_param( 'file_key' );
        $node_id = $request->get_param( 'node_id' );
        $url = $request->get_param( 'url' );
        $import_images = $request->get_param( 'import_images' );

        // Parsear URL si se proporciona
        if ( $url && ! $file_key ) {
            $parsed = $this->api->parse_url( $url );

            if ( is_wp_error( $parsed ) ) {
                return $parsed;
            }

            $file_key = $parsed['file_key'];
            $node_id = $parsed['node_id'] ?: $node_id;
        }

        if ( ! $file_key ) {
            return new WP_Error(
                'missing_file_key',
                __( 'Se requiere file_key o URL de Figma', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Obtener datos del archivo
        if ( $node_id ) {
            // Obtener nodo específico
            $response = $this->api->get_file_nodes( $file_key, array( $node_id ) );
        } else {
            // Obtener archivo completo
            $response = $this->api->get_file( $file_key );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Extraer el nodo a convertir
        $node = null;

        if ( $node_id && isset( $response['nodes'][ $node_id ] ) ) {
            $node = $response['nodes'][ $node_id ]['document'];
        } elseif ( isset( $response['document'] ) ) {
            // Obtener primera página
            $pages = $response['document']['children'] ?? array();
            if ( ! empty( $pages ) ) {
                // Obtener primer frame de la primera página
                $first_page = $pages[0];
                $frames = $first_page['children'] ?? array();
                if ( ! empty( $frames ) ) {
                    $node = $frames[0];
                }
            }
        }

        if ( ! $node ) {
            return new WP_Error(
                'no_node_found',
                __( 'No se encontró contenido para importar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        // Obtener imágenes si es necesario
        $images_map = array();
        if ( $import_images ) {
            $images_map = $this->fetch_images( $file_key, $node );
        }

        // Convertir a estructura VBP
        $vbp_document = $this->converter->convert_frame_to_page( $node, $images_map );

        return new WP_REST_Response(
            array(
                'success'  => true,
                'elements' => $vbp_document['elements'],
                'settings' => $vbp_document['settings'],
                'assets'   => $images_map,
                'source'   => array(
                    'file_key' => $file_key,
                    'node_id'  => $node_id ?: $node['id'],
                    'name'     => $node['name'] ?? '',
                ),
            ),
            200
        );
    }

    /**
     * Previsualiza estructura de archivo Figma
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function preview_figma( $request ) {
        // Verificar configuración
        if ( ! $this->api->is_configured() ) {
            return new WP_Error(
                'figma_not_configured',
                __( 'Token de Figma no configurado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $file_key = $request->get_param( 'file_key' );
        $url = $request->get_param( 'url' );

        // Parsear URL si se proporciona
        if ( $url && ! $file_key ) {
            $parsed = $this->api->parse_url( $url );

            if ( is_wp_error( $parsed ) ) {
                return $parsed;
            }

            $file_key = $parsed['file_key'];
        }

        if ( ! $file_key ) {
            return new WP_Error(
                'missing_file_key',
                __( 'Se requiere file_key o URL de Figma', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Obtener info del archivo
        $file_info = $this->api->get_file_info( $file_key );

        if ( is_wp_error( $file_info ) ) {
            return $file_info;
        }

        // Obtener frames importables de cada página
        $importable_frames = array();

        foreach ( $file_info['pages'] as $page ) {
            $page_frames = $this->get_page_frames( $file_key, $page['id'] );
            if ( ! empty( $page_frames ) ) {
                $importable_frames[] = array(
                    'page_id'   => $page['id'],
                    'page_name' => $page['name'],
                    'frames'    => $page_frames,
                );
            }
        }

        return new WP_REST_Response(
            array(
                'success'   => true,
                'file'      => array(
                    'key'          => $file_key,
                    'name'         => $file_info['name'],
                    'lastModified' => $file_info['lastModified'],
                    'thumbnail'    => $file_info['thumbnailUrl'],
                ),
                'pages'     => $file_info['pages'],
                'frames'    => $importable_frames,
            ),
            200
        );
    }

    /**
     * Obtiene frames de una página
     *
     * @param string $file_key Key del archivo.
     * @param string $page_id ID de la página.
     * @return array
     */
    private function get_page_frames( $file_key, $page_id ) {
        $response = $this->api->get_file_nodes( $file_key, array( $page_id ) );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $page = $response['nodes'][ $page_id ]['document'] ?? null;

        if ( ! $page || empty( $page['children'] ) ) {
            return array();
        }

        $frames = array();

        foreach ( $page['children'] as $child ) {
            if ( in_array( $child['type'], array( 'FRAME', 'COMPONENT' ), true ) ) {
                $frames[] = array(
                    'id'     => $child['id'],
                    'name'   => $child['name'],
                    'type'   => $child['type'],
                    'width'  => $child['absoluteBoundingBox']['width'] ?? 0,
                    'height' => $child['absoluteBoundingBox']['height'] ?? 0,
                );
            }
        }

        return $frames;
    }

    /**
     * Obtiene estado de la conexión con Figma
     *
     * @return WP_REST_Response
     */
    public function get_figma_status() {
        $configured = $this->api->is_configured();

        if ( ! $configured ) {
            return new WP_REST_Response(
                array(
                    'configured' => false,
                    'connected'  => false,
                    'message'    => __( 'Token de Figma no configurado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                200
            );
        }

        // Verificar conexión
        $verification = $this->api->verify_connection();

        return new WP_REST_Response(
            array(
                'configured' => true,
                'connected'  => $verification['success'],
                'user'       => $verification['user'] ?? null,
                'message'    => $verification['success']
                    ? __( 'Conectado a Figma', FLAVOR_PLATFORM_TEXT_DOMAIN )
                    : ( $verification['error'] ?? __( 'Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN ) ),
            ),
            200
        );
    }

    /**
     * Obtiene y descarga imágenes de un nodo
     *
     * @param string $file_key Key del archivo.
     * @param array  $node Nodo.
     * @return array Mapa de node_id => url_local.
     */
    private function fetch_images( $file_key, $node ) {
        $image_node_ids = array();

        // Buscar nodos que necesitan imágenes
        $this->find_image_nodes( $node, $image_node_ids );

        if ( empty( $image_node_ids ) ) {
            return array();
        }

        // Obtener URLs de imágenes de Figma
        $response = $this->api->get_images( $file_key, $image_node_ids );

        if ( is_wp_error( $response ) || empty( $response['images'] ) ) {
            return array();
        }

        $images_map = array();

        // Descargar cada imagen
        foreach ( $response['images'] as $node_id => $image_url ) {
            if ( ! empty( $image_url ) ) {
                $local_url = $this->api->download_image( $image_url );

                if ( ! is_wp_error( $local_url ) ) {
                    $images_map[ $node_id ] = $local_url;
                }
            }
        }

        return $images_map;
    }

    /**
     * Busca nodos que contienen imágenes
     *
     * @param array $node Nodo.
     * @param array $image_ids Array de IDs (por referencia).
     */
    private function find_image_nodes( $node, &$image_ids ) {
        // Verificar fills para imágenes
        if ( isset( $node['fills'] ) ) {
            foreach ( $node['fills'] as $fill ) {
                if ( 'IMAGE' === ( $fill['type'] ?? '' ) ) {
                    $image_ids[] = $node['id'];
                    break;
                }
            }
        }

        // Verificar tipo de nodo
        if ( in_array( $node['type'] ?? '', array( 'VECTOR', 'ELLIPSE', 'RECTANGLE' ), true ) ) {
            // Podría ser un icono o imagen
            if ( isset( $node['fills'] ) ) {
                $image_ids[] = $node['id'];
            }
        }

        // Recursivo para hijos
        if ( isset( $node['children'] ) ) {
            foreach ( $node['children'] as $child ) {
                $this->find_image_nodes( $child, $image_ids );
            }
        }
    }

    /**
     * AJAX: Verificar token de Figma
     */
    public function ajax_verify_token() {
        check_ajax_referer( 'flavor_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $token = sanitize_text_field( $_POST['token'] ?? '' );

        if ( empty( $token ) ) {
            wp_send_json_error( array( 'message' => __( 'Token vacío', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        // Establecer temporalmente el token
        $this->api->set_token( $token, false );

        // Verificar conexión
        $verification = $this->api->verify_connection();

        if ( $verification['success'] ) {
            wp_send_json_success( array(
                'message' => __( 'Token válido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'user'    => $verification['user'],
            ) );
        } else {
            wp_send_json_error( array(
                'message' => $verification['error'],
            ) );
        }
    }

    /**
     * AJAX: Guardar token de Figma
     */
    public function ajax_save_token() {
        check_ajax_referer( 'flavor_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $token = sanitize_text_field( $_POST['token'] ?? '' );
        $scope = sanitize_text_field( $_POST['scope'] ?? 'user' );

        if ( 'global' === $scope ) {
            // Guardar en opciones globales
            $settings = get_option( 'flavor_chat_ia_settings', array() );
            $settings['figma_personal_token'] = $token;
            update_option( 'flavor_chat_ia_settings', $settings );
        } else {
            // Guardar en user_meta
            $this->api->set_token( $token, true );
        }

        wp_send_json_success( array(
            'message' => __( 'Token guardado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        ) );
    }

    /**
     * Obtiene instrucciones para obtener token de Figma
     *
     * @return array
     */
    public function get_token_instructions() {
        return array(
            'title' => __( 'Cómo obtener tu token de Figma', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'steps' => array(
                __( 'Inicia sesión en Figma', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                __( 'Ve a tu perfil (esquina superior derecha) > Settings', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                __( 'Desplázate hasta "Personal access tokens"', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                __( 'Haz clic en "Generate new token"', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                __( 'Dale un nombre descriptivo y copia el token generado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                __( 'Pega el token aquí y guárdalo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            'link'  => 'https://www.figma.com/developers/api#access-tokens',
        );
    }
}

// Inicializar
Flavor_VBP_Figma_Importer::get_instance();
