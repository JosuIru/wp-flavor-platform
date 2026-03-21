<?php
/**
 * Visual Builder Pro - Biblioteca de Componentes Reutilizables
 *
 * Permite guardar, gestionar y reutilizar componentes personalizados.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar la biblioteca de componentes reutilizables
 *
 * @since 2.1.0
 */
class Flavor_VBP_Component_Library {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Component_Library|null
     */
    private static $instancia = null;

    /**
     * Nombre de la opción para almacenar componentes
     *
     * @var string
     */
    const OPTION_KEY = 'vbp_component_library';

    /**
     * Categorías predefinidas
     *
     * @var array
     */
    private $categorias_predefinidas = array(
        'custom'     => 'Personalizados',
        'headers'    => 'Cabeceras',
        'footers'    => 'Pie de página',
        'heroes'     => 'Heroes',
        'ctas'       => 'Llamadas a Acción',
        'features'   => 'Características',
        'pricing'    => 'Precios',
        'forms'      => 'Formularios',
        'navigation' => 'Navegación',
        'content'    => 'Contenido',
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Component_Library
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
        add_action( 'rest_api_init', array( $this, 'registrar_rutas_rest' ) );
    }

    /**
     * Registra las rutas REST para la biblioteca
     */
    public function registrar_rutas_rest() {
        $namespace = 'flavor-vbp/v1';

        // Listar componentes
        register_rest_route(
            $namespace,
            '/components',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'rest_listar_componentes' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                    'args'                => array(
                        'category' => array(
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'rest_guardar_componente' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
            )
        );

        // Obtener/actualizar/eliminar componente individual
        register_rest_route(
            $namespace,
            '/components/(?P<id>[a-zA-Z0-9_-]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'rest_obtener_componente' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'rest_actualizar_componente' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'rest_eliminar_componente' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
            )
        );

        // Categorías
        register_rest_route(
            $namespace,
            '/components/categories',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'rest_listar_categorias' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
            )
        );

        // Importar/Exportar
        register_rest_route(
            $namespace,
            '/components/import',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'rest_importar_componente' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
            )
        );

        register_rest_route(
            $namespace,
            '/components/(?P<id>[a-zA-Z0-9_-]+)/export',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'rest_exportar_componente' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
            )
        );
    }

    /**
     * Verifica permisos de acceso
     *
     * @return bool
     */
    public function verificar_permiso() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Guarda un componente en la biblioteca
     *
     * @param string $nombre   Nombre del componente.
     * @param array  $bloques  Array de bloques que conforman el componente.
     * @param string $categoria Categoría del componente.
     * @param array  $meta     Metadatos adicionales.
     * @return string|WP_Error ID del componente o error.
     */
    public function guardar_componente( $nombre, $bloques, $categoria = 'custom', $meta = array() ) {
        if ( empty( $nombre ) || empty( $bloques ) ) {
            return new WP_Error( 'datos_invalidos', __( 'Nombre y bloques son requeridos', 'flavor-chat-ia' ) );
        }

        $componentes = $this->obtener_todos_componentes();
        $id          = $this->generar_id( $nombre );

        $componente = array(
            'id'          => $id,
            'name'        => sanitize_text_field( $nombre ),
            'category'    => sanitize_key( $categoria ),
            'blocks'      => $bloques,
            'thumbnail'   => isset( $meta['thumbnail'] ) ? esc_url_raw( $meta['thumbnail'] ) : '',
            'description' => isset( $meta['description'] ) ? sanitize_textarea_field( $meta['description'] ) : '',
            'tags'        => isset( $meta['tags'] ) ? array_map( 'sanitize_text_field', (array) $meta['tags'] ) : array(),
            'created_at'  => current_time( 'mysql' ),
            'updated_at'  => current_time( 'mysql' ),
            'author'      => get_current_user_id(),
            'version'     => '1.0.0',
        );

        $componentes[ $id ] = $componente;

        update_option( self::OPTION_KEY, $componentes );

        return $id;
    }

    /**
     * Obtiene todos los componentes
     *
     * @param string|null $categoria Filtrar por categoría.
     * @return array
     */
    public function obtener_todos_componentes( $categoria = null ) {
        $componentes = get_option( self::OPTION_KEY, array() );

        if ( ! is_array( $componentes ) ) {
            $componentes = array();
        }

        if ( null !== $categoria ) {
            $componentes = array_filter(
                $componentes,
                function( $componente ) use ( $categoria ) {
                    return isset( $componente['category'] ) && $componente['category'] === $categoria;
                }
            );
        }

        return $componentes;
    }

    /**
     * Obtiene un componente por ID
     *
     * @param string $id ID del componente.
     * @return array|null
     */
    public function obtener_componente( $id ) {
        $componentes = $this->obtener_todos_componentes();
        return isset( $componentes[ $id ] ) ? $componentes[ $id ] : null;
    }

    /**
     * Actualiza un componente existente
     *
     * @param string $id     ID del componente.
     * @param array  $datos  Datos a actualizar.
     * @return bool|WP_Error
     */
    public function actualizar_componente( $id, $datos ) {
        $componentes = $this->obtener_todos_componentes();

        if ( ! isset( $componentes[ $id ] ) ) {
            return new WP_Error( 'not_found', __( 'Componente no encontrado', 'flavor-chat-ia' ) );
        }

        $permitidos = array( 'name', 'category', 'blocks', 'thumbnail', 'description', 'tags' );

        foreach ( $permitidos as $campo ) {
            if ( isset( $datos[ $campo ] ) ) {
                switch ( $campo ) {
                    case 'name':
                        $componentes[ $id ][ $campo ] = sanitize_text_field( $datos[ $campo ] );
                        break;
                    case 'category':
                        $componentes[ $id ][ $campo ] = sanitize_key( $datos[ $campo ] );
                        break;
                    case 'description':
                        $componentes[ $id ][ $campo ] = sanitize_textarea_field( $datos[ $campo ] );
                        break;
                    case 'thumbnail':
                        $componentes[ $id ][ $campo ] = esc_url_raw( $datos[ $campo ] );
                        break;
                    case 'tags':
                        $componentes[ $id ][ $campo ] = array_map( 'sanitize_text_field', (array) $datos[ $campo ] );
                        break;
                    case 'blocks':
                        $componentes[ $id ][ $campo ] = $datos[ $campo ];
                        break;
                }
            }
        }

        $componentes[ $id ]['updated_at'] = current_time( 'mysql' );

        update_option( self::OPTION_KEY, $componentes );

        return true;
    }

    /**
     * Elimina un componente
     *
     * @param string $id ID del componente.
     * @return bool|WP_Error
     */
    public function eliminar_componente( $id ) {
        $componentes = $this->obtener_todos_componentes();

        if ( ! isset( $componentes[ $id ] ) ) {
            return new WP_Error( 'not_found', __( 'Componente no encontrado', 'flavor-chat-ia' ) );
        }

        unset( $componentes[ $id ] );

        update_option( self::OPTION_KEY, $componentes );

        return true;
    }

    /**
     * Importa un componente desde JSON
     *
     * @param string $json JSON del componente.
     * @return string|WP_Error ID del componente importado o error.
     */
    public function importar_componente( $json ) {
        $datos = json_decode( $json, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_invalido', __( 'JSON inválido', 'flavor-chat-ia' ) );
        }

        if ( empty( $datos['name'] ) || empty( $datos['blocks'] ) ) {
            return new WP_Error( 'datos_incompletos', __( 'El componente debe tener nombre y bloques', 'flavor-chat-ia' ) );
        }

        return $this->guardar_componente(
            $datos['name'],
            $datos['blocks'],
            isset( $datos['category'] ) ? $datos['category'] : 'custom',
            array(
                'thumbnail'   => isset( $datos['thumbnail'] ) ? $datos['thumbnail'] : '',
                'description' => isset( $datos['description'] ) ? $datos['description'] : '',
                'tags'        => isset( $datos['tags'] ) ? $datos['tags'] : array(),
            )
        );
    }

    /**
     * Exporta un componente a JSON
     *
     * @param string $id ID del componente.
     * @return string|WP_Error JSON del componente o error.
     */
    public function exportar_componente( $id ) {
        $componente = $this->obtener_componente( $id );

        if ( ! $componente ) {
            return new WP_Error( 'not_found', __( 'Componente no encontrado', 'flavor-chat-ia' ) );
        }

        $exportar = array(
            'name'        => $componente['name'],
            'category'    => $componente['category'],
            'blocks'      => $componente['blocks'],
            'thumbnail'   => $componente['thumbnail'],
            'description' => $componente['description'],
            'tags'        => $componente['tags'],
            'version'     => $componente['version'],
            'exported_at' => current_time( 'mysql' ),
        );

        return wp_json_encode( $exportar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    }

    /**
     * Obtiene las categorías disponibles
     *
     * @return array
     */
    public function obtener_categorias() {
        $categorias = $this->categorias_predefinidas;

        // Añadir categorías personalizadas de componentes existentes
        $componentes = $this->obtener_todos_componentes();
        foreach ( $componentes as $componente ) {
            if ( ! empty( $componente['category'] ) && ! isset( $categorias[ $componente['category'] ] ) ) {
                $categorias[ $componente['category'] ] = ucfirst( $componente['category'] );
            }
        }

        return $categorias;
    }

    /**
     * Genera un ID único para el componente
     *
     * @param string $nombre Nombre del componente.
     * @return string
     */
    private function generar_id( $nombre ) {
        $base = sanitize_title( $nombre );
        $id   = $base;
        $i    = 1;

        $componentes = $this->obtener_todos_componentes();

        while ( isset( $componentes[ $id ] ) ) {
            $id = $base . '-' . $i;
            $i++;
        }

        return $id;
    }

    // ============================================
    // Callbacks REST API
    // ============================================

    /**
     * REST: Listar componentes
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function rest_listar_componentes( $request ) {
        $categoria   = $request->get_param( 'category' );
        $componentes = $this->obtener_todos_componentes( $categoria );

        // No incluir bloques en el listado para reducir payload
        $listado = array_map(
            function( $componente ) {
                return array(
                    'id'          => $componente['id'],
                    'name'        => $componente['name'],
                    'category'    => $componente['category'],
                    'thumbnail'   => $componente['thumbnail'],
                    'description' => $componente['description'],
                    'tags'        => $componente['tags'],
                    'created_at'  => $componente['created_at'],
                    'updated_at'  => $componente['updated_at'],
                );
            },
            array_values( $componentes )
        );

        return new WP_REST_Response(
            array(
                'components' => $listado,
                'total'      => count( $listado ),
            ),
            200
        );
    }

    /**
     * REST: Guardar componente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_guardar_componente( $request ) {
        $datos = $request->get_json_params();

        if ( empty( $datos['name'] ) || empty( $datos['blocks'] ) ) {
            return new WP_Error(
                'datos_invalidos',
                __( 'Nombre y bloques son requeridos', 'flavor-chat-ia' ),
                array( 'status' => 400 )
            );
        }

        $id = $this->guardar_componente(
            $datos['name'],
            $datos['blocks'],
            isset( $datos['category'] ) ? $datos['category'] : 'custom',
            array(
                'thumbnail'   => isset( $datos['thumbnail'] ) ? $datos['thumbnail'] : '',
                'description' => isset( $datos['description'] ) ? $datos['description'] : '',
                'tags'        => isset( $datos['tags'] ) ? $datos['tags'] : array(),
            )
        );

        if ( is_wp_error( $id ) ) {
            return $id;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'id'      => $id,
                'message' => __( 'Componente guardado correctamente', 'flavor-chat-ia' ),
            ),
            201
        );
    }

    /**
     * REST: Obtener componente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_obtener_componente( $request ) {
        $id         = $request->get_param( 'id' );
        $componente = $this->obtener_componente( $id );

        if ( ! $componente ) {
            return new WP_Error(
                'not_found',
                __( 'Componente no encontrado', 'flavor-chat-ia' ),
                array( 'status' => 404 )
            );
        }

        return new WP_REST_Response( $componente, 200 );
    }

    /**
     * REST: Actualizar componente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_actualizar_componente( $request ) {
        $id     = $request->get_param( 'id' );
        $datos  = $request->get_json_params();
        $result = $this->actualizar_componente( $id, $datos );

        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array( 'status' => 404 )
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __( 'Componente actualizado correctamente', 'flavor-chat-ia' ),
            ),
            200
        );
    }

    /**
     * REST: Eliminar componente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_eliminar_componente( $request ) {
        $id     = $request->get_param( 'id' );
        $result = $this->eliminar_componente( $id );

        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array( 'status' => 404 )
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __( 'Componente eliminado correctamente', 'flavor-chat-ia' ),
            ),
            200
        );
    }

    /**
     * REST: Listar categorías
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function rest_listar_categorias( $request ) {
        $categorias = $this->obtener_categorias();

        $resultado = array();
        foreach ( $categorias as $slug => $nombre ) {
            $resultado[] = array(
                'slug' => $slug,
                'name' => $nombre,
            );
        }

        return new WP_REST_Response( array( 'categories' => $resultado ), 200 );
    }

    /**
     * REST: Importar componente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_importar_componente( $request ) {
        $json = $request->get_param( 'json' );

        if ( empty( $json ) ) {
            $datos = $request->get_json_params();
            $json  = wp_json_encode( $datos );
        }

        $id = $this->importar_componente( $json );

        if ( is_wp_error( $id ) ) {
            return new WP_Error(
                $id->get_error_code(),
                $id->get_error_message(),
                array( 'status' => 400 )
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'id'      => $id,
                'message' => __( 'Componente importado correctamente', 'flavor-chat-ia' ),
            ),
            201
        );
    }

    /**
     * REST: Exportar componente
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_exportar_componente( $request ) {
        $id   = $request->get_param( 'id' );
        $json = $this->exportar_componente( $id );

        if ( is_wp_error( $json ) ) {
            return new WP_Error(
                $json->get_error_code(),
                $json->get_error_message(),
                array( 'status' => 404 )
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'json'    => $json,
            ),
            200
        );
    }
}

// Inicializar la biblioteca
Flavor_VBP_Component_Library::get_instance();
