<?php
/**
 * Visual Builder Pro - API REST de Símbolos
 *
 * Endpoints REST para gestionar símbolos e instancias sincronizadas.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.22
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para la API REST de símbolos
 */
class Flavor_VBP_Symbols_API {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Symbols_API|null
     */
    private static $instancia = null;

    /**
     * Namespace de la API
     *
     * @var string
     */
    private $namespace = 'flavor-vbp/v1';

    /**
     * Instancia del gestor de símbolos
     *
     * @var Flavor_VBP_Symbols
     */
    private $symbols_manager;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Symbols_API
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
        $this->symbols_manager = Flavor_VBP_Symbols::get_instance();
        add_action( 'rest_api_init', array( $this, 'registrar_endpoints' ) );
    }

    /**
     * Registra los endpoints REST
     */
    public function registrar_endpoints() {
        // === ENDPOINTS DE SÍMBOLOS ===

        // GET /symbols - Listar símbolos
        register_rest_route(
            $this->namespace,
            '/symbols',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_symbols' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'category' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'search' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'orderby' => array(
                        'type'              => 'string',
                        'default'           => 'updated_at',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'order' => array(
                        'type'              => 'string',
                        'default'           => 'DESC',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'limit' => array(
                        'type'              => 'integer',
                        'default'           => 50,
                        'sanitize_callback' => 'absint',
                    ),
                    'offset' => array(
                        'type'              => 'integer',
                        'default'           => 0,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // GET /symbols/{id} - Obtener símbolo específico
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_symbol' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // POST /symbols - Crear símbolo
        register_rest_route(
            $this->namespace,
            '/symbols',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_create_symbol' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'name' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'content' => array(
                        'required' => true,
                        'type'     => 'array',
                    ),
                    'description' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'category' => array(
                        'type'              => 'string',
                        'default'           => 'custom',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'thumbnail' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ),
                    'exposed_properties' => array(
                        'type' => 'array',
                    ),
                ),
            )
        );

        // PUT /symbols/{id} - Actualizar símbolo
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)',
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'api_update_symbol' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // DELETE /symbols/{id} - Eliminar símbolo
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'api_delete_symbol' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // GET /symbols/categories - Listar categorías
        register_rest_route(
            $this->namespace,
            '/symbols/categories',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_categories' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
            )
        );

        // GET /symbols/{id}/instances - Obtener instancias de un símbolo
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/instances',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_symbol_instances' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // POST /symbols/{id}/sync - Sincronizar todas las instancias
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/sync',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_sync_symbol_instances' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // === ENDPOINTS DE INSTANCIAS EN DOCUMENTOS ===

        // POST /documents/{doc_id}/instances - Registrar instancia en documento
        register_rest_route(
            $this->namespace,
            '/documents/(?P<doc_id>\d+)/instances',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_register_instance' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'doc_id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'symbol_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'element_id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'variant' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                        'default'           => '',
                    ),
                ),
            )
        );

        // GET /documents/{doc_id}/instances - Obtener instancias de un documento
        register_rest_route(
            $this->namespace,
            '/documents/(?P<doc_id>\d+)/instances',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_document_instances' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'doc_id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // === ENDPOINTS DE INSTANCIAS INDIVIDUALES ===

        // GET /instances/{id} - Obtener instancia específica
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_instance' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // PUT /instances/{id} - Actualizar overrides de instancia
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)',
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'api_update_instance' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'overrides' => array(
                        'type' => 'array',
                    ),
                ),
            )
        );

        // DELETE /instances/{id} - Eliminar instancia
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'api_delete_instance' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // POST /instances/{id}/detach - Desvincular instancia del símbolo
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)/detach',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_detach_instance' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // POST /instances/{id}/sync - Sincronizar instancia individual
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)/sync',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_sync_instance' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // GET /instances/{id}/content - Obtener contenido renderizado de instancia
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)/content',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_instance_content' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // PUT /instances/{id}/variant - Cambiar variante de instancia
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)/variant',
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'api_set_instance_variant' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'variant' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // POST /instances/{id}/create-variant - Crear variante desde instancia
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)/create-variant',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_create_variant_from_instance' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'name' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'key' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // === ENDPOINTS DE VARIANTES DE SÍMBOLOS ===

        // GET /symbols/{id}/variants - Listar variantes de un símbolo
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/variants',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_symbol_variants' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // POST /symbols/{id}/variants - Crear variante
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/variants',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_create_symbol_variant' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'key' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'name' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'overrides' => array(
                        'type'    => 'object',
                        'default' => array(),
                    ),
                ),
            )
        );

        // PUT /symbols/{id}/variants/{key} - Actualizar variante
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/variants/(?P<key>[a-z0-9_-]+)',
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'api_update_symbol_variant' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'key' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // DELETE /symbols/{id}/variants/{key} - Eliminar variante
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/variants/(?P<key>[a-z0-9_-]+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'api_delete_symbol_variant' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'key' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // POST /symbols/{id}/variants/{key}/duplicate - Duplicar variante
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/variants/(?P<key>[a-z0-9_-]+)/duplicate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_duplicate_symbol_variant' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'key' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'new_key' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'new_name' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // PUT /symbols/{id}/default-variant - Establecer variante por defecto
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/default-variant',
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'api_set_default_variant' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'variant' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // GET /symbols/{id}/variants/{key}/content - Obtener contenido con variante aplicada
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/variants/(?P<key>[a-z0-9_-]+)/content',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_variant_content' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'key' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // === ENDPOINTS DE SWAP INSTANCE ===

        // POST /instances/{id}/swap - Cambiar símbolo de la instancia
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)/swap',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_swap_instance' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'new_symbol_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'preserve_compatible' => array(
                        'type'    => 'boolean',
                        'default' => true,
                    ),
                ),
            )
        );

        // GET /instances/{id}/swap-suggestions - Obtener sugerencias de swap
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)/swap-suggestions',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_swap_suggestions' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'limit' => array(
                        'type'    => 'integer',
                        'default' => 10,
                    ),
                ),
            )
        );

        // POST /instances/{id}/check-compatibility - Verificar compatibilidad antes de swap
        register_rest_route(
            $this->namespace,
            '/instances/(?P<id>\d+)/check-compatibility',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_check_swap_compatibility' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'target_symbol_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                ),
            )
        );

        // GET /symbols/{id}/similar - Obtener símbolos similares
        register_rest_route(
            $this->namespace,
            '/symbols/(?P<id>\d+)/similar',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_similar_symbols' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => array( $this, 'validar_id' ),
                    ),
                    'limit' => array(
                        'type'    => 'integer',
                        'default' => 10,
                    ),
                ),
            )
        );

        // === ENDPOINTS DE IMPORT/EXPORT ===

        // GET /symbols/export - Exportar símbolos
        register_rest_route(
            $this->namespace,
            '/symbols/export',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_export_symbols' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'ids' => array(
                        'type'              => 'string',
                        'description'       => __( 'IDs de símbolos separados por coma (vacío = todos)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // POST /symbols/import - Importar símbolos
        register_rest_route(
            $this->namespace,
            '/symbols/import',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_import_symbols' ),
                'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
                'args'                => array(
                    'symbols' => array(
                        'required'    => true,
                        'type'        => 'array',
                        'description' => __( 'Array de símbolos a importar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'mode' => array(
                        'type'              => 'string',
                        'default'           => 'merge',
                        'enum'              => array( 'merge', 'replace' ),
                        'description'       => __( 'merge = no sobrescribe existentes, replace = sobrescribe', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // POST /symbols/import/validate - Validar datos de importación
        register_rest_route(
            $this->namespace,
            '/symbols/import/validate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_validate_import' ),
                'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
                'args'                => array(
                    'data' => array(
                        'required'    => true,
                        'type'        => 'object',
                        'description' => __( 'Datos de importación a validar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                ),
            )
        );
    }

    // ========================
    // Métodos de Permisos
    // ========================

    /**
     * Verifica permisos de lectura
     *
     * @return bool
     */
    public function verificar_permisos_lectura() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verifica permisos de escritura
     *
     * @return bool
     */
    public function verificar_permisos_escritura() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Valida que el ID sea un número positivo
     *
     * @param mixed $param Parámetro a validar.
     * @return bool
     */
    public function validar_id( $param ) {
        return is_numeric( $param ) && intval( $param ) > 0;
    }

    // ========================
    // Callbacks de Símbolos
    // ========================

    /**
     * GET /symbols - Lista todos los símbolos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function api_get_symbols( $request ) {
        $args = array(
            'category' => $request->get_param( 'category' ),
            'search'   => $request->get_param( 'search' ),
            'orderby'  => $request->get_param( 'orderby' ),
            'order'    => $request->get_param( 'order' ),
            'limit'    => $request->get_param( 'limit' ),
            'offset'   => $request->get_param( 'offset' ),
        );

        $simbolos = $this->symbols_manager->get_symbols( $args );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'symbols' => $simbolos,
                    'total'   => count( $simbolos ),
                ),
            )
        );
    }

    /**
     * GET /symbols/{id} - Obtiene un símbolo específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_get_symbol( $request ) {
        $symbol_id = absint( $request->get_param( 'id' ) );
        $simbolo   = $this->symbols_manager->get_symbol( $symbol_id );

        if ( ! $simbolo ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => $simbolo,
            )
        );
    }

    /**
     * POST /symbols - Crea un nuevo símbolo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_create_symbol( $request ) {
        $params = $request->get_json_params();

        $nombre   = isset( $params['name'] ) ? $params['name'] : '';
        $content  = isset( $params['content'] ) ? $params['content'] : array();
        $opciones = array(
            'description'         => isset( $params['description'] ) ? $params['description'] : '',
            'category'            => isset( $params['category'] ) ? $params['category'] : 'custom',
            'thumbnail'           => isset( $params['thumbnail'] ) ? $params['thumbnail'] : '',
            'exposed_properties'  => isset( $params['exposed_properties'] ) ? $params['exposed_properties'] : array(),
        );

        $resultado = $this->symbols_manager->create_symbol( $nombre, $content, $opciones );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $simbolo_creado = $this->symbols_manager->get_symbol( $resultado );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'id'      => $resultado,
                    'symbol'  => $simbolo_creado,
                    'message' => __( 'Símbolo creado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * PUT /symbols/{id} - Actualiza un símbolo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_update_symbol( $request ) {
        $symbol_id = absint( $request->get_param( 'id' ) );
        $datos     = $request->get_json_params();

        $resultado = $this->symbols_manager->update_symbol( $symbol_id, $datos );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $simbolo_actualizado = $this->symbols_manager->get_symbol( $symbol_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'symbol'  => $simbolo_actualizado,
                    'message' => __( 'Símbolo actualizado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * DELETE /symbols/{id} - Elimina un símbolo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_delete_symbol( $request ) {
        $symbol_id = absint( $request->get_param( 'id' ) );
        $resultado = $this->symbols_manager->delete_symbol( $symbol_id );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'message' => __( 'Símbolo eliminado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * GET /symbols/categories - Lista las categorías
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function api_get_categories( $request ) {
        $categorias = $this->symbols_manager->get_categories();

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'categories' => $categorias,
                ),
            )
        );
    }

    /**
     * GET /symbols/{id}/instances - Obtiene instancias de un símbolo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_get_symbol_instances( $request ) {
        $symbol_id = absint( $request->get_param( 'id' ) );

        $simbolo = $this->symbols_manager->get_symbol( $symbol_id );
        if ( ! $simbolo ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        $instancias = $this->symbols_manager->get_symbol_instances( $symbol_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'symbol_id'      => $symbol_id,
                    'symbol_name'    => $simbolo['name'],
                    'symbol_version' => $simbolo['version'],
                    'instances'      => $instancias,
                    'total'          => count( $instancias ),
                ),
            )
        );
    }

    /**
     * POST /symbols/{id}/sync - Sincroniza todas las instancias
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_sync_symbol_instances( $request ) {
        $symbol_id = absint( $request->get_param( 'id' ) );
        $resultado = $this->symbols_manager->sync_instances( $symbol_id );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'synced_count' => $resultado,
                    'message'      => sprintf(
                        __( '%d instancias sincronizadas correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        $resultado
                    ),
                ),
            )
        );
    }

    // ========================
    // Callbacks de Instancias en Documentos
    // ========================

    /**
     * POST /documents/{doc_id}/instances - Registra una instancia
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_register_instance( $request ) {
        $document_id = absint( $request->get_param( 'doc_id' ) );
        $params      = $request->get_json_params();
        $symbol_id   = isset( $params['symbol_id'] ) ? absint( $params['symbol_id'] ) : 0;
        $element_id  = isset( $params['element_id'] ) ? sanitize_text_field( $params['element_id'] ) : '';
        $variant     = isset( $params['variant'] ) ? sanitize_key( $params['variant'] ) : '';

        if ( ! $symbol_id || ! $element_id ) {
            return new WP_Error(
                'invalid_data',
                __( 'symbol_id y element_id son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $resultado = $this->symbols_manager->register_instance( $symbol_id, $document_id, $element_id, $variant );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $instancia_creada = $this->symbols_manager->get_symbol_instance( $resultado );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'id'       => $resultado,
                    'instance' => $instancia_creada,
                    'message'  => __( 'Instancia registrada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * GET /documents/{doc_id}/instances - Obtiene instancias de un documento
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function api_get_document_instances( $request ) {
        $document_id = absint( $request->get_param( 'doc_id' ) );
        $instancias  = $this->symbols_manager->get_document_instances( $document_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'document_id' => $document_id,
                    'instances'   => $instancias,
                    'total'       => count( $instancias ),
                ),
            )
        );
    }

    // ========================
    // Callbacks de Instancias Individuales
    // ========================

    /**
     * GET /instances/{id} - Obtiene una instancia específica
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_get_instance( $request ) {
        $instance_id = absint( $request->get_param( 'id' ) );
        $instancia   = $this->symbols_manager->get_symbol_instance( $instance_id );

        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => $instancia,
            )
        );
    }

    /**
     * PUT /instances/{id} - Actualiza los overrides de una instancia
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_update_instance( $request ) {
        $instance_id = absint( $request->get_param( 'id' ) );
        $params      = $request->get_json_params();
        $overrides   = isset( $params['overrides'] ) ? $params['overrides'] : array();

        $resultado = $this->symbols_manager->update_instance_overrides( $instance_id, $overrides );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $instancia_actualizada = $this->symbols_manager->get_symbol_instance( $instance_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'instance' => $instancia_actualizada,
                    'message'  => __( 'Instancia actualizada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * DELETE /instances/{id} - Elimina una instancia
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_delete_instance( $request ) {
        $instance_id = absint( $request->get_param( 'id' ) );
        $resultado   = $this->symbols_manager->delete_instance( $instance_id );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'message' => __( 'Instancia eliminada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * POST /instances/{id}/detach - Desvincula una instancia del símbolo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_detach_instance( $request ) {
        $instance_id = absint( $request->get_param( 'id' ) );
        $resultado   = $this->symbols_manager->detach_instance( $instance_id );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'content'     => $resultado['content'],
                    'document_id' => $resultado['document_id'],
                    'element_id'  => $resultado['element_id'],
                    'message'     => __( 'Instancia desvinculada correctamente. El contenido ahora es independiente.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * POST /instances/{id}/sync - Sincroniza una instancia individual
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_sync_instance( $request ) {
        $instance_id = absint( $request->get_param( 'id' ) );
        $resultado   = $this->symbols_manager->sync_instance( $instance_id );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $instancia_sincronizada = $this->symbols_manager->get_symbol_instance( $instance_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'instance' => $instancia_sincronizada,
                    'message'  => __( 'Instancia sincronizada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * GET /instances/{id}/content - Obtiene el contenido renderizado
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_get_instance_content( $request ) {
        $instance_id = absint( $request->get_param( 'id' ) );
        $contenido   = $this->symbols_manager->get_instance_content( $instance_id );

        if ( is_wp_error( $contenido ) ) {
            return $contenido;
        }

        $instancia = $this->symbols_manager->get_symbol_instance( $instance_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'instance_id'    => $instance_id,
                    'symbol_id'      => $instancia['symbol_id'],
                    'variant'        => $instancia['variant'],
                    'synced_version' => $instancia['synced_version'],
                    'needs_sync'     => $instancia['needs_sync'],
                    'content'        => $contenido,
                ),
            )
        );
    }

    // ========================
    // Callbacks de Variantes
    // ========================

    /**
     * GET /symbols/{id}/variants - Lista las variantes de un símbolo
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_get_symbol_variants( $request ) {
        $symbol_id = absint( $request->get_param( 'id' ) );

        $simbolo = $this->symbols_manager->get_symbol( $symbol_id );
        if ( ! $simbolo ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        $variants = $this->symbols_manager->get_variants( $symbol_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'symbol_id'       => $symbol_id,
                    'symbol_name'     => $simbolo['name'],
                    'default_variant' => $simbolo['default_variant'],
                    'variants'        => $variants,
                    'total'           => count( $variants ),
                ),
            )
        );
    }

    /**
     * POST /symbols/{id}/variants - Crea una nueva variante
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_create_symbol_variant( $request ) {
        $symbol_id = absint( $request->get_param( 'id' ) );
        $params    = $request->get_json_params();

        $variant_key  = isset( $params['key'] ) ? sanitize_key( $params['key'] ) : '';
        $variant_name = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
        $overrides    = isset( $params['overrides'] ) ? $params['overrides'] : array();

        if ( empty( $variant_key ) || empty( $variant_name ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'key y name son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $resultado = $this->symbols_manager->set_variant( $symbol_id, $variant_key, array(
            'name'      => $variant_name,
            'overrides' => $overrides,
        ) );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $variants = $this->symbols_manager->get_variants( $symbol_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'variant_key' => $variant_key,
                    'variant'     => isset( $variants[ $variant_key ] ) ? $variants[ $variant_key ] : null,
                    'message'     => __( 'Variante creada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * PUT /symbols/{id}/variants/{key} - Actualiza una variante
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_update_symbol_variant( $request ) {
        $symbol_id   = absint( $request->get_param( 'id' ) );
        $variant_key = sanitize_key( $request->get_param( 'key' ) );
        $params      = $request->get_json_params();

        $variant_data = array();

        if ( isset( $params['name'] ) ) {
            $variant_data['name'] = sanitize_text_field( $params['name'] );
        }

        if ( isset( $params['overrides'] ) ) {
            $variant_data['overrides'] = $params['overrides'];
        }

        if ( empty( $variant_data ) ) {
            return new WP_Error(
                'no_data',
                __( 'No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Obtener variante existente para merge
        $variants = $this->symbols_manager->get_variants( $symbol_id );
        if ( ! isset( $variants[ $variant_key ] ) ) {
            return new WP_Error(
                'not_found',
                __( 'Variante no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        $merged_data = array_merge( $variants[ $variant_key ], $variant_data );

        $resultado = $this->symbols_manager->set_variant( $symbol_id, $variant_key, $merged_data );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $updated_variants = $this->symbols_manager->get_variants( $symbol_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'variant_key' => $variant_key,
                    'variant'     => $updated_variants[ $variant_key ],
                    'message'     => __( 'Variante actualizada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * DELETE /symbols/{id}/variants/{key} - Elimina una variante
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_delete_symbol_variant( $request ) {
        $symbol_id   = absint( $request->get_param( 'id' ) );
        $variant_key = sanitize_key( $request->get_param( 'key' ) );

        $resultado = $this->symbols_manager->delete_variant( $symbol_id, $variant_key );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'variant_key' => $variant_key,
                    'message'     => __( 'Variante eliminada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * POST /symbols/{id}/variants/{key}/duplicate - Duplica una variante
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_duplicate_symbol_variant( $request ) {
        $symbol_id   = absint( $request->get_param( 'id' ) );
        $source_key  = sanitize_key( $request->get_param( 'key' ) );
        $params      = $request->get_json_params();

        $new_key  = isset( $params['new_key'] ) ? sanitize_key( $params['new_key'] ) : '';
        $new_name = isset( $params['new_name'] ) ? sanitize_text_field( $params['new_name'] ) : '';

        $resultado = $this->symbols_manager->duplicate_variant( $symbol_id, $source_key, $new_key, $new_name );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $variants = $this->symbols_manager->get_variants( $symbol_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'source_key'  => $source_key,
                    'new_key'     => $resultado,
                    'variant'     => isset( $variants[ $resultado ] ) ? $variants[ $resultado ] : null,
                    'message'     => __( 'Variante duplicada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * PUT /symbols/{id}/default-variant - Establece la variante por defecto
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_set_default_variant( $request ) {
        $symbol_id   = absint( $request->get_param( 'id' ) );
        $params      = $request->get_json_params();
        $variant_key = isset( $params['variant'] ) ? sanitize_key( $params['variant'] ) : '';

        if ( empty( $variant_key ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'variant es requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $resultado = $this->symbols_manager->set_default_variant( $symbol_id, $variant_key );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'default_variant' => $variant_key,
                    'message'         => __( 'Variante por defecto actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * GET /symbols/{id}/variants/{key}/content - Obtiene contenido con variante aplicada
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_get_variant_content( $request ) {
        $symbol_id   = absint( $request->get_param( 'id' ) );
        $variant_key = sanitize_key( $request->get_param( 'key' ) );

        $contenido = $this->symbols_manager->get_variant_content( $symbol_id, $variant_key );

        if ( ! $contenido ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo o variante no encontrados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        $simbolo  = $this->symbols_manager->get_symbol( $symbol_id );
        $variants = $this->symbols_manager->get_variants( $symbol_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'symbol_id'    => $symbol_id,
                    'symbol_name'  => $simbolo['name'],
                    'variant_key'  => $variant_key,
                    'variant_name' => isset( $variants[ $variant_key ]['name'] ) ? $variants[ $variant_key ]['name'] : $variant_key,
                    'content'      => $contenido,
                ),
            )
        );
    }

    /**
     * PUT /instances/{id}/variant - Cambia la variante de una instancia
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_set_instance_variant( $request ) {
        $instance_id = absint( $request->get_param( 'id' ) );
        $params      = $request->get_json_params();
        $variant_key = isset( $params['variant'] ) ? sanitize_key( $params['variant'] ) : '';

        if ( empty( $variant_key ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'variant es requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $resultado = $this->symbols_manager->set_instance_variant( $instance_id, $variant_key );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $instancia = $this->symbols_manager->get_symbol_instance( $instance_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'instance'    => $instancia,
                    'variant'     => $variant_key,
                    'message'     => __( 'Variante de instancia actualizada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    /**
     * POST /instances/{id}/create-variant - Crea variante desde overrides de instancia
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_create_variant_from_instance( $request ) {
        $instance_id  = absint( $request->get_param( 'id' ) );
        $params       = $request->get_json_params();
        $variant_name = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
        $variant_key  = isset( $params['key'] ) ? sanitize_key( $params['key'] ) : '';

        if ( empty( $variant_name ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'name es requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $resultado = $this->symbols_manager->create_variant_from_instance( $instance_id, $variant_name, $variant_key );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        $instancia = $this->symbols_manager->get_symbol_instance( $instance_id );
        $variants  = $this->symbols_manager->get_variants( $instancia['symbol_id'] );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'variant_key' => $resultado,
                    'variant'     => isset( $variants[ $resultado ] ) ? $variants[ $resultado ] : null,
                    'message'     => __( 'Variante creada desde instancia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
            )
        );
    }

    // ========================
    // Callbacks de Swap Instance
    // ========================

    /**
     * POST /instances/{id}/swap - Cambiar símbolo de la instancia
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_swap_instance( $request ) {
        $instance_id         = absint( $request->get_param( 'id' ) );
        $params              = $request->get_json_params();
        $new_symbol_id       = isset( $params['new_symbol_id'] ) ? absint( $params['new_symbol_id'] ) : 0;
        $preserve_compatible = isset( $params['preserve_compatible'] ) ? (bool) $params['preserve_compatible'] : true;

        if ( $new_symbol_id <= 0 ) {
            return new WP_Error(
                'invalid_data',
                __( 'new_symbol_id es requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $resultado = $this->symbols_manager->swap_instance( $instance_id, $new_symbol_id, $preserve_compatible );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        // Obtener datos actualizados de la instancia
        $instancia = $this->symbols_manager->get_symbol_instance( $instance_id );
        $simbolo   = $this->symbols_manager->get_symbol( $new_symbol_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array_merge(
                    $resultado,
                    array(
                        'instance' => $instancia,
                        'symbol'   => $simbolo,
                        'message'  => sprintf(
                            /* translators: %s: symbol name */
                            __( 'Instancia cambiada a "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                            $simbolo['name']
                        ),
                    )
                ),
            )
        );
    }

    /**
     * GET /instances/{id}/swap-suggestions - Obtener sugerencias de swap
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_get_swap_suggestions( $request ) {
        $instance_id = absint( $request->get_param( 'id' ) );
        $limit       = absint( $request->get_param( 'limit' ) );

        if ( $limit <= 0 || $limit > 50 ) {
            $limit = 10;
        }

        $instancia = $this->symbols_manager->get_symbol_instance( $instance_id );

        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        $similar_symbols = $this->symbols_manager->get_similar_symbols( $instancia['symbol_id'], $limit );

        // Agregar info de compatibilidad para cada símbolo sugerido
        $current_overrides = is_array( $instancia['overrides'] ) ? $instancia['overrides'] : array();

        foreach ( $similar_symbols as &$symbol_item ) {
            if ( ! empty( $current_overrides ) ) {
                $compatibility = $this->symbols_manager->calculate_override_compatibility(
                    $instancia['symbol_id'],
                    $symbol_item['id'],
                    $current_overrides
                );
                $symbol_item['compatibility_score'] = $compatibility['compatibility_score'];
                $symbol_item['compatible_count']    = count( $compatibility['compatible'] );
                $symbol_item['incompatible_count']  = count( $compatibility['incompatible'] );
            } else {
                $symbol_item['compatibility_score'] = 100;
                $symbol_item['compatible_count']    = 0;
                $symbol_item['incompatible_count']  = 0;
            }
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'instance_id'    => $instance_id,
                    'current_symbol' => $this->symbols_manager->get_symbol( $instancia['symbol_id'] ),
                    'suggestions'    => $similar_symbols,
                    'overrides_count' => count( $current_overrides ),
                ),
            )
        );
    }

    /**
     * POST /instances/{id}/check-compatibility - Verificar compatibilidad antes de swap
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_check_swap_compatibility( $request ) {
        $instance_id      = absint( $request->get_param( 'id' ) );
        $params           = $request->get_json_params();
        $target_symbol_id = isset( $params['target_symbol_id'] ) ? absint( $params['target_symbol_id'] ) : 0;

        if ( $target_symbol_id <= 0 ) {
            return new WP_Error(
                'invalid_data',
                __( 'target_symbol_id es requerido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        $instancia = $this->symbols_manager->get_symbol_instance( $instance_id );

        if ( ! $instancia ) {
            return new WP_Error(
                'not_found',
                __( 'Instancia no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        $target_symbol = $this->symbols_manager->get_symbol( $target_symbol_id );

        if ( ! $target_symbol ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo destino no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        $current_overrides = is_array( $instancia['overrides'] ) ? $instancia['overrides'] : array();

        $compatibility = $this->symbols_manager->calculate_override_compatibility(
            $instancia['symbol_id'],
            $target_symbol_id,
            $current_overrides
        );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'instance_id'         => $instance_id,
                    'source_symbol_id'    => $instancia['symbol_id'],
                    'target_symbol_id'    => $target_symbol_id,
                    'target_symbol_name'  => $target_symbol['name'],
                    'compatibility_score' => $compatibility['compatibility_score'],
                    'compatible'          => $compatibility['compatible'],
                    'incompatible'        => $compatibility['incompatible'],
                    'compatible_count'    => count( $compatibility['compatible'] ),
                    'incompatible_count'  => count( $compatibility['incompatible'] ),
                    'total_overrides'     => count( $current_overrides ),
                    'can_preserve_all'    => empty( $compatibility['incompatible'] ),
                ),
            )
        );
    }

    /**
     * GET /symbols/{id}/similar - Obtener símbolos similares
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_get_similar_symbols( $request ) {
        $symbol_id = absint( $request->get_param( 'id' ) );
        $limit     = absint( $request->get_param( 'limit' ) );

        if ( $limit <= 0 || $limit > 50 ) {
            $limit = 10;
        }

        $simbolo = $this->symbols_manager->get_symbol( $symbol_id );

        if ( ! $simbolo ) {
            return new WP_Error(
                'not_found',
                __( 'Símbolo no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 404 )
            );
        }

        $similar_symbols = $this->symbols_manager->get_similar_symbols( $symbol_id, $limit );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'symbol_id'   => $symbol_id,
                    'symbol_name' => $simbolo['name'],
                    'similar'     => $similar_symbols,
                    'count'       => count( $similar_symbols ),
                ),
            )
        );
    }

    // ========================
    // Callbacks de Import/Export
    // ========================

    /**
     * GET /symbols/export - Exportar símbolos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function api_export_symbols( $request ) {
        $ids_string = $request->get_param( 'ids' );
        $symbol_ids = array();

        if ( ! empty( $ids_string ) ) {
            $ids_array = explode( ',', $ids_string );
            foreach ( $ids_array as $single_id ) {
                $single_id = absint( trim( $single_id ) );
                if ( $single_id > 0 ) {
                    $symbol_ids[] = $single_id;
                }
            }
        }

        $export_data = $this->symbols_manager->export_symbols( $symbol_ids );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => $export_data,
            )
        );
    }

    /**
     * POST /symbols/import - Importar símbolos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_import_symbols( $request ) {
        $params       = $request->get_json_params();
        $symbols_data = isset( $params['symbols'] ) ? $params['symbols'] : array();
        $import_mode  = isset( $params['mode'] ) ? sanitize_text_field( $params['mode'] ) : 'merge';

        if ( empty( $symbols_data ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'No hay símbolos para importar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Validar modo
        if ( ! in_array( $import_mode, array( 'merge', 'replace' ), true ) ) {
            $import_mode = 'merge';
        }

        $resultado = $this->symbols_manager->import_symbols( $symbols_data, $import_mode );

        if ( ! $resultado['success'] && empty( $resultado['imported'] ) && empty( $resultado['updated'] ) ) {
            return new WP_Error(
                'import_failed',
                isset( $resultado['error'] ) ? $resultado['error'] : __( 'Error en la importación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array(
                    'status' => 400,
                    'errors' => isset( $resultado['errors'] ) ? $resultado['errors'] : array(),
                )
            );
        }

        return rest_ensure_response(
            array(
                'success' => $resultado['success'],
                'data'    => array(
                    'imported'     => $resultado['imported'],
                    'updated'      => $resultado['updated'],
                    'skipped'      => $resultado['skipped'],
                    'errors'       => $resultado['errors'],
                    'imported_ids' => isset( $resultado['imported_ids'] ) ? $resultado['imported_ids'] : array(),
                    'message'      => $resultado['message'],
                ),
            )
        );
    }

    /**
     * POST /symbols/import/validate - Validar datos de importación
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function api_validate_import( $request ) {
        $params      = $request->get_json_params();
        $import_data = isset( $params['data'] ) ? $params['data'] : $params;

        $validation_result = $this->symbols_manager->validate_import_data( $import_data );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'valid'        => $validation_result['valid'],
                    'errors'       => $validation_result['errors'],
                    'symbol_count' => isset( $validation_result['symbol_count'] ) ? $validation_result['symbol_count'] : 0,
                    'version'      => isset( $validation_result['version'] ) ? $validation_result['version'] : 'unknown',
                ),
            )
        );
    }
}
