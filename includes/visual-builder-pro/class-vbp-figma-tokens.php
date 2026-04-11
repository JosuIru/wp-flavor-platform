<?php
/**
 * Visual Builder Pro - Figma Tokens Sync
 *
 * Sistema de sincronizacion de Design Tokens con Figma
 * Soporta multiples formatos: Figma Tokens, Style Dictionary, W3C
 *
 * @package Flavor_Platform
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para sincronizacion de Design Tokens con Figma
 */
class VBP_Figma_Tokens {

    /**
     * Instancia singleton
     *
     * @var VBP_Figma_Tokens
     */
    private static $instance = null;

    /**
     * Namespace de la API REST
     *
     * @var string
     */
    private $namespace = 'flavor-vbp/v1';

    /**
     * Opcion para guardar configuracion de Figma
     *
     * @var string
     */
    private $option_key = 'vbp_figma_tokens_config';

    /**
     * Historial de sincronizaciones
     *
     * @var string
     */
    private $history_key = 'vbp_figma_sync_history';

    /**
     * Obtener instancia singleton
     *
     * @return VBP_Figma_Tokens
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Registrar rutas de la API REST
     */
    public function register_routes() {
        // Sincronizar desde Figma
        register_rest_route(
            $this->namespace,
            '/tokens/sync-figma',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'sync_from_figma' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'file_key' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'node_id'  => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Importar tokens desde JSON
        register_rest_route(
            $this->namespace,
            '/tokens/import',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'import_tokens' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'format' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'enum'              => array( 'figma', 'style-dictionary', 'w3c', 'json' ),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'tokens' => array(
                        'required' => true,
                        'type'     => 'object',
                    ),
                ),
            )
        );

        // Exportar tokens
        register_rest_route(
            $this->namespace,
            '/tokens/export',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'export_tokens' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'format' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'default'           => 'json',
                        'enum'              => array( 'json', 'css', 'scss', 'tailwind', 'figma', 'w3c' ),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Obtener tokens actuales
        register_rest_route(
            $this->namespace,
            '/tokens',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_tokens' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        // Actualizar token individual
        register_rest_route(
            $this->namespace,
            '/tokens/(?P<token_key>[a-zA-Z0-9_-]+)',
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'update_token' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'value' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Webhook para actualizaciones automaticas de Figma
        register_rest_route(
            $this->namespace,
            '/tokens/webhook',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_webhook' ),
                'permission_callback' => array( $this, 'verify_webhook_signature' ),
            )
        );

        // Historial de sincronizaciones
        register_rest_route(
            $this->namespace,
            '/tokens/history',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_sync_history' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        // Configuracion de Figma
        register_rest_route(
            $this->namespace,
            '/tokens/config',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_config' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'save_config' ),
                    'permission_callback' => array( $this, 'check_admin_permission' ),
                    'args'                => array(
                        'access_token' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'file_key'     => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'auto_sync'    => array(
                            'type' => 'boolean',
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Verificar permisos de usuario
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verificar permisos de administrador
     *
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Verificar firma del webhook
     *
     * @param WP_REST_Request $request Request.
     * @return bool
     */
    public function verify_webhook_signature( $request ) {
        $config = get_option( $this->option_key, array() );

        if ( empty( $config['webhook_secret'] ) ) {
            return false;
        }

        $signature = $request->get_header( 'X-Figma-Signature' );
        $body      = $request->get_body();
        $expected  = hash_hmac( 'sha256', $body, $config['webhook_secret'] );

        return hash_equals( $expected, $signature );
    }

    /**
     * Sincronizar tokens desde Figma
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function sync_from_figma( $request ) {
        $file_key = $request->get_param( 'file_key' );
        $node_id  = $request->get_param( 'node_id' );
        $config   = get_option( $this->option_key, array() );

        if ( empty( $config['access_token'] ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Figma access token not configured',
                ),
                400
            );
        }

        // Obtener estilos del archivo de Figma
        $figma_response = $this->fetch_figma_styles( $file_key, $config['access_token'] );

        if ( is_wp_error( $figma_response ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $figma_response->get_error_message(),
                ),
                500
            );
        }

        // Convertir estilos de Figma a tokens
        $tokens = $this->convert_figma_styles_to_tokens( $figma_response );

        // Registrar en historial
        $this->add_sync_history(
            array(
                'source'       => 'figma',
                'file_key'     => $file_key,
                'tokens_count' => count( $tokens ),
                'timestamp'    => current_time( 'mysql' ),
                'user_id'      => get_current_user_id(),
            )
        );

        return new WP_REST_Response(
            array(
                'success'      => true,
                'tokens'       => $tokens,
                'tokens_count' => count( $tokens ),
                'synced_at'    => current_time( 'c' ),
            )
        );
    }

    /**
     * Obtener estilos desde la API de Figma
     *
     * @param string $file_key     Clave del archivo.
     * @param string $access_token Token de acceso.
     * @return array|WP_Error
     */
    private function fetch_figma_styles( $file_key, $access_token ) {
        $styles_url = "https://api.figma.com/v1/files/{$file_key}/styles";

        $response = wp_remote_get(
            $styles_url,
            array(
                'headers' => array(
                    'X-Figma-Token' => $access_token,
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $body          = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $response_code ) {
            $error_message = isset( $body['err'] ) ? $body['err'] : 'Figma API error';
            return new WP_Error( 'figma_api_error', $error_message );
        }

        // Ahora obtener los nodos de estilos con sus valores
        if ( empty( $body['meta']['styles'] ) ) {
            return array( 'styles' => array() );
        }

        $style_nodes = array();
        foreach ( $body['meta']['styles'] as $style ) {
            $style_nodes[ $style['node_id'] ] = $style;
        }

        // Obtener detalles de los nodos
        $node_ids   = implode( ',', array_keys( $style_nodes ) );
        $nodes_url  = "https://api.figma.com/v1/files/{$file_key}/nodes?ids={$node_ids}";
        $nodes_response = wp_remote_get(
            $nodes_url,
            array(
                'headers' => array(
                    'X-Figma-Token' => $access_token,
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $nodes_response ) ) {
            return $nodes_response;
        }

        $nodes_body = json_decode( wp_remote_retrieve_body( $nodes_response ), true );

        return array(
            'styles' => $body['meta']['styles'],
            'nodes'  => isset( $nodes_body['nodes'] ) ? $nodes_body['nodes'] : array(),
        );
    }

    /**
     * Convertir estilos de Figma a tokens
     *
     * @param array $figma_data Datos de Figma.
     * @return array
     */
    private function convert_figma_styles_to_tokens( $figma_data ) {
        $tokens = array();

        if ( empty( $figma_data['styles'] ) ) {
            return $tokens;
        }

        foreach ( $figma_data['styles'] as $style ) {
            $style_type = $style['style_type'];
            $style_name = $this->sanitize_token_name( $style['name'] );
            $node_id    = $style['node_id'];
            $token_key  = '--flavor-' . $style_name;

            $node_data = isset( $figma_data['nodes'][ $node_id ] ) ?
                $figma_data['nodes'][ $node_id ]['document'] : null;

            if ( ! $node_data ) {
                continue;
            }

            switch ( $style_type ) {
                case 'FILL':
                    $value = $this->extract_fill_color( $node_data );
                    if ( $value ) {
                        $tokens[ $token_key ] = array(
                            'value' => $value,
                            'type'  => 'color',
                            'name'  => $style['name'],
                        );
                    }
                    break;

                case 'TEXT':
                    $typography_tokens = $this->extract_typography( $node_data, $style_name );
                    $tokens            = array_merge( $tokens, $typography_tokens );
                    break;

                case 'EFFECT':
                    $effect_value = $this->extract_effect( $node_data );
                    if ( $effect_value ) {
                        $tokens[ $token_key ] = array(
                            'value' => $effect_value,
                            'type'  => 'shadow',
                            'name'  => $style['name'],
                        );
                    }
                    break;

                case 'GRID':
                    $grid_tokens = $this->extract_grid( $node_data, $style_name );
                    $tokens      = array_merge( $tokens, $grid_tokens );
                    break;
            }
        }

        return $tokens;
    }

    /**
     * Sanitizar nombre de token
     *
     * @param string $name Nombre original.
     * @return string
     */
    private function sanitize_token_name( $name ) {
        $sanitized_name = strtolower( $name );
        $sanitized_name = preg_replace( '/[^a-z0-9]+/', '-', $sanitized_name );
        $sanitized_name = trim( $sanitized_name, '-' );
        return $sanitized_name;
    }

    /**
     * Extraer color de fill
     *
     * @param array $node_data Datos del nodo.
     * @return string|null
     */
    private function extract_fill_color( $node_data ) {
        if ( empty( $node_data['fills'] ) ) {
            return null;
        }

        $fill = $node_data['fills'][0];

        if ( 'SOLID' !== $fill['type'] ) {
            return null;
        }

        $color   = $fill['color'];
        $opacity = isset( $fill['opacity'] ) ? $fill['opacity'] : 1;

        $r = round( $color['r'] * 255 );
        $g = round( $color['g'] * 255 );
        $b = round( $color['b'] * 255 );
        $a = round( $color['a'] * $opacity, 2 );

        if ( 1 === $a ) {
            return sprintf( '#%02x%02x%02x', $r, $g, $b );
        }

        return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, $a );
    }

    /**
     * Extraer tipografia
     *
     * @param array  $node_data  Datos del nodo.
     * @param string $style_name Nombre del estilo.
     * @return array
     */
    private function extract_typography( $node_data, $style_name ) {
        $tokens = array();
        $style  = isset( $node_data['style'] ) ? $node_data['style'] : array();

        if ( isset( $style['fontFamily'] ) ) {
            $tokens[ '--flavor-font-family-' . $style_name ] = array(
                'value' => $style['fontFamily'],
                'type'  => 'fontFamily',
            );
        }

        if ( isset( $style['fontSize'] ) ) {
            $tokens[ '--flavor-font-size-' . $style_name ] = array(
                'value' => $style['fontSize'] . 'px',
                'type'  => 'dimension',
            );
        }

        if ( isset( $style['fontWeight'] ) ) {
            $tokens[ '--flavor-font-weight-' . $style_name ] = array(
                'value' => $style['fontWeight'],
                'type'  => 'fontWeight',
            );
        }

        if ( isset( $style['lineHeightPx'] ) ) {
            $tokens[ '--flavor-line-height-' . $style_name ] = array(
                'value' => $style['lineHeightPx'] . 'px',
                'type'  => 'dimension',
            );
        }

        if ( isset( $style['letterSpacing'] ) ) {
            $tokens[ '--flavor-letter-spacing-' . $style_name ] = array(
                'value' => $style['letterSpacing'] . 'px',
                'type'  => 'dimension',
            );
        }

        return $tokens;
    }

    /**
     * Extraer efectos (sombras)
     *
     * @param array $node_data Datos del nodo.
     * @return string|null
     */
    private function extract_effect( $node_data ) {
        if ( empty( $node_data['effects'] ) ) {
            return null;
        }

        $shadows = array();

        foreach ( $node_data['effects'] as $effect ) {
            if ( ! in_array( $effect['type'], array( 'DROP_SHADOW', 'INNER_SHADOW' ), true ) ) {
                continue;
            }

            $color  = $effect['color'];
            $offset = isset( $effect['offset'] ) ? $effect['offset'] : array( 'x' => 0, 'y' => 0 );
            $radius = isset( $effect['radius'] ) ? $effect['radius'] : 0;
            $spread = isset( $effect['spread'] ) ? $effect['spread'] : 0;

            $r = round( $color['r'] * 255 );
            $g = round( $color['g'] * 255 );
            $b = round( $color['b'] * 255 );
            $a = round( $color['a'], 2 );

            $inset   = 'INNER_SHADOW' === $effect['type'] ? 'inset ' : '';
            $shadow_css = sprintf(
                '%s%dpx %dpx %dpx %dpx rgba(%d, %d, %d, %s)',
                $inset,
                $offset['x'],
                $offset['y'],
                $radius,
                $spread,
                $r,
                $g,
                $b,
                $a
            );

            $shadows[] = $shadow_css;
        }

        return ! empty( $shadows ) ? implode( ', ', $shadows ) : null;
    }

    /**
     * Extraer grid/spacing
     *
     * @param array  $node_data  Datos del nodo.
     * @param string $style_name Nombre del estilo.
     * @return array
     */
    private function extract_grid( $node_data, $style_name ) {
        $tokens = array();

        if ( empty( $node_data['layoutGrids'] ) ) {
            return $tokens;
        }

        foreach ( $node_data['layoutGrids'] as $index => $grid ) {
            $suffix = $index > 0 ? '-' . ( $index + 1 ) : '';

            if ( isset( $grid['sectionSize'] ) ) {
                $tokens[ '--flavor-grid-size-' . $style_name . $suffix ] = array(
                    'value' => $grid['sectionSize'] . 'px',
                    'type'  => 'dimension',
                );
            }

            if ( isset( $grid['gutterSize'] ) ) {
                $tokens[ '--flavor-grid-gutter-' . $style_name . $suffix ] = array(
                    'value' => $grid['gutterSize'] . 'px',
                    'type'  => 'dimension',
                );
            }
        }

        return $tokens;
    }

    /**
     * Importar tokens desde JSON
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function import_tokens( $request ) {
        $format = $request->get_param( 'format' );
        $tokens = $request->get_param( 'tokens' );

        $converted_tokens = array();

        switch ( $format ) {
            case 'figma':
                $converted_tokens = $this->parse_figma_tokens_format( $tokens );
                break;

            case 'style-dictionary':
                $converted_tokens = $this->parse_style_dictionary_format( $tokens );
                break;

            case 'w3c':
                $converted_tokens = $this->parse_w3c_format( $tokens );
                break;

            case 'json':
            default:
                $converted_tokens = $this->parse_generic_json( $tokens );
                break;
        }

        // Guardar tokens
        $saved = $this->save_tokens( $converted_tokens );

        // Registrar en historial
        $this->add_sync_history(
            array(
                'source'       => 'import-' . $format,
                'tokens_count' => count( $converted_tokens ),
                'timestamp'    => current_time( 'mysql' ),
                'user_id'      => get_current_user_id(),
            )
        );

        return new WP_REST_Response(
            array(
                'success'      => $saved,
                'tokens_count' => count( $converted_tokens ),
                'imported_at'  => current_time( 'c' ),
            )
        );
    }

    /**
     * Parsear formato Figma Tokens
     *
     * @param array $tokens Tokens.
     * @return array
     */
    private function parse_figma_tokens_format( $tokens ) {
        $result = array();

        foreach ( $tokens as $category => $category_tokens ) {
            if ( ! is_array( $category_tokens ) ) {
                continue;
            }

            foreach ( $category_tokens as $name => $token_data ) {
                $token_key = '--flavor-' . $this->sanitize_token_name( $category . '-' . $name );

                if ( isset( $token_data['value'] ) ) {
                    $result[ $token_key ] = array(
                        'value' => $token_data['value'],
                        'type'  => isset( $token_data['type'] ) ? $token_data['type'] : 'string',
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Parsear formato Style Dictionary
     *
     * @param array $tokens Tokens.
     * @return array
     */
    private function parse_style_dictionary_format( $tokens ) {
        $result     = array();
        $properties = isset( $tokens['properties'] ) ? $tokens['properties'] : $tokens;

        $this->flatten_style_dict_props( $properties, '', $result );

        return $result;
    }

    /**
     * Aplanar propiedades de Style Dictionary
     *
     * @param array  $props  Propiedades.
     * @param string $prefix Prefijo.
     * @param array  $result Resultado.
     */
    private function flatten_style_dict_props( $props, $prefix, &$result ) {
        foreach ( $props as $key => $value ) {
            $current_key = $prefix ? $prefix . '-' . $key : $key;

            if ( is_array( $value ) && isset( $value['value'] ) ) {
                $token_key             = '--flavor-' . $this->sanitize_token_name( $current_key );
                $result[ $token_key ] = array(
                    'value' => $value['value'],
                    'type'  => isset( $value['type'] ) ? $value['type'] : 'string',
                );
            } elseif ( is_array( $value ) ) {
                $this->flatten_style_dict_props( $value, $current_key, $result );
            }
        }
    }

    /**
     * Parsear formato W3C
     *
     * @param array $tokens Tokens.
     * @return array
     */
    private function parse_w3c_format( $tokens ) {
        $result = array();

        $this->flatten_w3c_tokens( $tokens, '', $result );

        return $result;
    }

    /**
     * Aplanar tokens W3C
     *
     * @param array  $tokens Tokens.
     * @param string $prefix Prefijo.
     * @param array  $result Resultado.
     */
    private function flatten_w3c_tokens( $tokens, $prefix, &$result ) {
        foreach ( $tokens as $key => $value ) {
            // Ignorar propiedades meta
            if ( strpos( $key, '$' ) === 0 && '$value' !== $key ) {
                continue;
            }

            $current_key = $prefix ? $prefix . '-' . $key : $key;

            if ( is_array( $value ) && isset( $value['$value'] ) ) {
                $token_key             = '--flavor-' . $this->sanitize_token_name( $current_key );
                $result[ $token_key ] = array(
                    'value' => $value['$value'],
                    'type'  => isset( $value['$type'] ) ? $value['$type'] : 'string',
                );
            } elseif ( is_array( $value ) ) {
                $this->flatten_w3c_tokens( $value, $current_key, $result );
            }
        }
    }

    /**
     * Parsear JSON generico
     *
     * @param array $tokens Tokens.
     * @return array
     */
    private function parse_generic_json( $tokens ) {
        $result = array();

        foreach ( $tokens as $key => $value ) {
            $token_key = strpos( $key, '--' ) === 0 ? $key : '--flavor-' . $this->sanitize_token_name( $key );

            if ( is_array( $value ) ) {
                $result[ $token_key ] = array(
                    'value' => isset( $value['value'] ) ? $value['value'] : ( isset( $value['$value'] ) ? $value['$value'] : '' ),
                    'type'  => isset( $value['type'] ) ? $value['type'] : ( isset( $value['$type'] ) ? $value['$type'] : 'string' ),
                );
            } else {
                $result[ $token_key ] = array(
                    'value' => $value,
                    'type'  => 'string',
                );
            }
        }

        return $result;
    }

    /**
     * Guardar tokens
     *
     * @param array $tokens Tokens a guardar.
     * @return bool
     */
    private function save_tokens( $tokens ) {
        return update_option( 'vbp_design_tokens', $tokens );
    }

    /**
     * Obtener tokens actuales
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function get_tokens( $request ) {
        $tokens = get_option( 'vbp_design_tokens', array() );

        return new WP_REST_Response(
            array(
                'success' => true,
                'tokens'  => $tokens,
            )
        );
    }

    /**
     * Actualizar token individual
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function update_token( $request ) {
        $token_key = '--flavor-' . $request->get_param( 'token_key' );
        $value     = $request->get_param( 'value' );

        $tokens                = get_option( 'vbp_design_tokens', array() );
        $tokens[ $token_key ] = array(
            'value' => $value,
            'type'  => isset( $tokens[ $token_key ]['type'] ) ? $tokens[ $token_key ]['type'] : 'string',
        );

        update_option( 'vbp_design_tokens', $tokens );

        return new WP_REST_Response(
            array(
                'success'   => true,
                'token_key' => $token_key,
                'value'     => $value,
            )
        );
    }

    /**
     * Exportar tokens
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function export_tokens( $request ) {
        $format = $request->get_param( 'format' );
        $tokens = get_option( 'vbp_design_tokens', array() );

        $output = '';

        switch ( $format ) {
            case 'css':
                $output = $this->export_as_css( $tokens );
                break;

            case 'scss':
                $output = $this->export_as_scss( $tokens );
                break;

            case 'tailwind':
                $output = $this->export_as_tailwind( $tokens );
                break;

            case 'figma':
                $output = $this->export_as_figma_tokens( $tokens );
                break;

            case 'w3c':
                $output = $this->export_as_w3c( $tokens );
                break;

            case 'json':
            default:
                $output = $tokens;
                break;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'format'  => $format,
                'output'  => $output,
            )
        );
    }

    /**
     * Exportar como CSS
     *
     * @param array $tokens Tokens.
     * @return string
     */
    private function export_as_css( $tokens ) {
        $css = "/* Flavor Design Tokens */\n:root {\n";

        foreach ( $tokens as $key => $data ) {
            $value = is_array( $data ) ? $data['value'] : $data;
            $css  .= "  {$key}: {$value};\n";
        }

        $css .= "}\n";
        return $css;
    }

    /**
     * Exportar como SCSS
     *
     * @param array $tokens Tokens.
     * @return string
     */
    private function export_as_scss( $tokens ) {
        $scss = "// Flavor Design Tokens\n\n";

        foreach ( $tokens as $key => $data ) {
            $value     = is_array( $data ) ? $data['value'] : $data;
            $scss_var  = str_replace( '--flavor-', '$flavor-', $key );
            $scss     .= "{$scss_var}: {$value};\n";
        }

        $scss .= "\n:root {\n";
        foreach ( $tokens as $key => $data ) {
            $scss_var = str_replace( '--flavor-', '$flavor-', $key );
            $scss    .= "  {$key}: #{" . $scss_var . "};\n";
        }
        $scss .= "}\n";

        return $scss;
    }

    /**
     * Exportar como configuracion de Tailwind
     *
     * @param array $tokens Tokens.
     * @return string
     */
    private function export_as_tailwind( $tokens ) {
        $config = array(
            'theme' => array(
                'extend' => array(
                    'colors'       => array(),
                    'spacing'      => array(),
                    'borderRadius' => array(),
                    'fontFamily'   => array(),
                    'fontSize'     => array(),
                    'boxShadow'    => array(),
                ),
            ),
        );

        foreach ( $tokens as $key => $data ) {
            $clean_key = str_replace( '--flavor-', '', $key );
            $css_var   = "var({$key})";

            if ( strpos( $clean_key, 'color' ) !== false ||
                strpos( $clean_key, 'bg' ) !== false ||
                strpos( $clean_key, 'primary' ) !== false ||
                strpos( $clean_key, 'secondary' ) !== false ) {
                $config['theme']['extend']['colors'][ $clean_key ] = $css_var;
            } elseif ( strpos( $clean_key, 'spacing' ) !== false ) {
                $config['theme']['extend']['spacing'][ str_replace( 'spacing-', '', $clean_key ) ] = $css_var;
            } elseif ( strpos( $clean_key, 'radius' ) !== false ) {
                $config['theme']['extend']['borderRadius'][ str_replace( 'radius-', '', $clean_key ) ] = $css_var;
            } elseif ( strpos( $clean_key, 'font-family' ) !== false ) {
                $config['theme']['extend']['fontFamily'][ str_replace( 'font-family-', '', $clean_key ) ] = $css_var;
            } elseif ( strpos( $clean_key, 'font-size' ) !== false ) {
                $config['theme']['extend']['fontSize'][ str_replace( 'font-size-', '', $clean_key ) ] = $css_var;
            } elseif ( strpos( $clean_key, 'shadow' ) !== false ) {
                $config['theme']['extend']['boxShadow'][ str_replace( 'shadow-', '', $clean_key ) ] = $css_var;
            }
        }

        return "module.exports = " . wp_json_encode( $config, JSON_PRETTY_PRINT ) . ";\n";
    }

    /**
     * Exportar como Figma Tokens
     *
     * @param array $tokens Tokens.
     * @return array
     */
    private function export_as_figma_tokens( $tokens ) {
        $figma_tokens = array();

        foreach ( $tokens as $key => $data ) {
            $clean_key = str_replace( '--flavor-', '', $key );
            $parts     = explode( '-', $clean_key );
            $category  = $parts[0];
            $name      = implode( '-', array_slice( $parts, 1 ) ) ?: 'default';

            if ( ! isset( $figma_tokens[ $category ] ) ) {
                $figma_tokens[ $category ] = array();
            }

            $figma_tokens[ $category ][ $name ] = array(
                'value' => is_array( $data ) ? $data['value'] : $data,
                'type'  => is_array( $data ) && isset( $data['type'] ) ? $data['type'] : 'string',
            );
        }

        return $figma_tokens;
    }

    /**
     * Exportar como W3C Design Tokens
     *
     * @param array $tokens Tokens.
     * @return array
     */
    private function export_as_w3c( $tokens ) {
        $w3c_tokens = array();

        foreach ( $tokens as $key => $data ) {
            $clean_key = str_replace( '--flavor-', '', $key );
            $parts     = explode( '-', $clean_key );
            $current   = &$w3c_tokens;

            for ( $i = 0; $i < count( $parts ) - 1; $i++ ) {
                if ( ! isset( $current[ $parts[ $i ] ] ) ) {
                    $current[ $parts[ $i ] ] = array();
                }
                $current = &$current[ $parts[ $i ] ];
            }

            $last_part            = $parts[ count( $parts ) - 1 ];
            $current[ $last_part ] = array(
                '$value' => is_array( $data ) ? $data['value'] : $data,
                '$type'  => is_array( $data ) && isset( $data['type'] ) ? $data['type'] : 'string',
            );
        }

        return $w3c_tokens;
    }

    /**
     * Manejar webhook de Figma
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function handle_webhook( $request ) {
        $body   = json_decode( $request->get_body(), true );
        $config = get_option( $this->option_key, array() );

        if ( empty( $body['file_key'] ) || $body['file_key'] !== $config['file_key'] ) {
            return new WP_REST_Response( array( 'success' => false ), 400 );
        }

        // Evento de actualizacion de estilos
        if ( isset( $body['event_type'] ) && 'LIBRARY_PUBLISH' === $body['event_type'] ) {
            // Sincronizar automaticamente si esta habilitado
            if ( ! empty( $config['auto_sync'] ) ) {
                $request = new WP_REST_Request( 'POST' );
                $request->set_param( 'file_key', $body['file_key'] );
                $this->sync_from_figma( $request );
            }
        }

        return new WP_REST_Response( array( 'success' => true ) );
    }

    /**
     * Obtener historial de sincronizaciones
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function get_sync_history( $request ) {
        $history = get_option( $this->history_key, array() );

        return new WP_REST_Response(
            array(
                'success' => true,
                'history' => $history,
            )
        );
    }

    /**
     * Agregar entrada al historial
     *
     * @param array $entry Entrada.
     */
    private function add_sync_history( $entry ) {
        $history   = get_option( $this->history_key, array() );
        $history[] = $entry;

        // Limitar a ultimas 50 entradas
        if ( count( $history ) > 50 ) {
            $history = array_slice( $history, -50 );
        }

        update_option( $this->history_key, $history );
    }

    /**
     * Obtener configuracion
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function get_config( $request ) {
        $config = get_option( $this->option_key, array() );

        // No exponer el access token completo
        if ( ! empty( $config['access_token'] ) ) {
            $config['access_token_set'] = true;
            $config['access_token']     = substr( $config['access_token'], 0, 8 ) . '...';
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'config'  => $config,
            )
        );
    }

    /**
     * Guardar configuracion
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function save_config( $request ) {
        $config = get_option( $this->option_key, array() );

        if ( $request->has_param( 'access_token' ) ) {
            $config['access_token'] = $request->get_param( 'access_token' );
        }

        if ( $request->has_param( 'file_key' ) ) {
            $config['file_key'] = $request->get_param( 'file_key' );
        }

        if ( $request->has_param( 'auto_sync' ) ) {
            $config['auto_sync'] = (bool) $request->get_param( 'auto_sync' );
        }

        update_option( $this->option_key, $config );

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Configuration saved',
            )
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting(
            'vbp_figma_tokens',
            $this->option_key,
            array(
                'type'              => 'object',
                'sanitize_callback' => array( $this, 'sanitize_config' ),
            )
        );
    }

    /**
     * Sanitizar configuracion
     *
     * @param array $config Configuracion.
     * @return array
     */
    public function sanitize_config( $config ) {
        return array(
            'access_token'   => isset( $config['access_token'] ) ? sanitize_text_field( $config['access_token'] ) : '',
            'file_key'       => isset( $config['file_key'] ) ? sanitize_text_field( $config['file_key'] ) : '',
            'auto_sync'      => isset( $config['auto_sync'] ) ? (bool) $config['auto_sync'] : false,
            'webhook_secret' => isset( $config['webhook_secret'] ) ? sanitize_text_field( $config['webhook_secret'] ) : '',
        );
    }
}

// Inicializar
VBP_Figma_Tokens::instance();
