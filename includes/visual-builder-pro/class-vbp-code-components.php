<?php
/**
 * Visual Builder Pro - Code Components
 *
 * Sistema backend para gestionar Code Components (React/Vue/Svelte/Vanilla).
 * Permite crear, almacenar, validar y servir componentes de codigo personalizados.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar Code Components
 *
 * @since 2.4.0
 */
class Flavor_VBP_Code_Components {

    /**
     * Version del sistema de Code Components
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Nombre de la tabla de base de datos
     *
     * @var string
     */
    private $table_name;

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Code_Components|null
     */
    private static $instance = null;

    /**
     * Frameworks soportados
     *
     * @var array
     */
    private $supported_frameworks = array( 'react', 'vue', 'svelte', 'vanilla' );

    /**
     * Tipos de props soportados
     *
     * @var array
     */
    private $supported_prop_types = array(
        'string',
        'number',
        'boolean',
        'color',
        'select',
        'image',
        'icon',
        'array',
        'object',
        'children',
        'function',
        'richtext',
        'url',
    );

    /**
     * Categorias predefinidas
     *
     * @var array
     */
    private $categories = array(
        'interactive' => array(
            'name'        => 'Interactive',
            'icon'        => 'pointer',
            'description' => 'Components with user interactions',
        ),
        'display'     => array(
            'name'        => 'Display',
            'icon'        => 'eye',
            'description' => 'Components for displaying data',
        ),
        'form'        => array(
            'name'        => 'Form',
            'icon'        => 'edit-3',
            'description' => 'Data input components',
        ),
        'layout'      => array(
            'name'        => 'Layout',
            'icon'        => 'layout',
            'description' => 'Structure components',
        ),
        'media'       => array(
            'name'        => 'Media',
            'icon'        => 'image',
            'description' => 'Multimedia components',
        ),
        'data'        => array(
            'name'        => 'Data',
            'icon'        => 'database',
            'description' => 'Data visualization components',
        ),
        'navigation'  => array(
            'name'        => 'Navigation',
            'icon'        => 'navigation',
            'description' => 'Navigation components',
        ),
        'utility'     => array(
            'name'        => 'Utility',
            'icon'        => 'tool',
            'description' => 'Utility components',
        ),
        'custom'      => array(
            'name'        => 'Custom',
            'icon'        => 'code',
            'description' => 'Custom components',
        ),
    );

    /**
     * Dependencias NPM permitidas
     *
     * @var array
     */
    private $allowed_dependencies = array(
        'react'            => '^18.0.0',
        'react-dom'        => '^18.0.0',
        'framer-motion'    => '^10.0.0',
        '@headlessui/react' => '^1.7.0',
        '@heroicons/react' => '^2.0.0',
        'react-icons'      => '^4.0.0',
        'classnames'       => '^2.0.0',
        'clsx'             => '^2.0.0',
        'vue'              => '^3.0.0',
        '@vueuse/core'     => '^10.0.0',
        'gsap'             => '^3.0.0',
        'animejs'          => '^3.0.0',
        'lottie-web'       => '^5.0.0',
        'chart.js'         => '^4.0.0',
        'd3'               => '^7.0.0',
        'lodash'           => '^4.0.0',
        'date-fns'         => '^2.0.0',
        'dayjs'            => '^1.0.0',
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Code_Components
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vbp_code_components';

        $this->init_hooks();
        $this->maybe_create_table();
    }

    /**
     * Inicializa los hooks de WordPress
     */
    private function init_hooks() {
        // Registrar endpoints REST.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Hook para permitir que plugins registren componentes.
        add_action( 'vbp_code_components_init', array( $this, 'load_custom_components' ) );

        // Agregar al menu de admin.
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
    }

    /**
     * Crea la tabla de base de datos si no existe
     */
    private function maybe_create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table           = $this->table_name;

        // Verificar si la tabla ya existe.
        $table_exists = $wpdb->get_var(
            $wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
        );

        if ( $table_exists === $table ) {
            return;
        }

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            component_id varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            framework varchar(50) NOT NULL DEFAULT 'react',
            category varchar(100) NOT NULL DEFAULT 'custom',
            description text,
            code longtext NOT NULL,
            props longtext,
            styles longtext,
            dependencies longtext,
            default_size varchar(100),
            icon varchar(100) DEFAULT 'code',
            version varchar(50) DEFAULT '1.0.0',
            author_id bigint(20) unsigned NOT NULL,
            is_global tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            usage_count int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY component_id (component_id),
            KEY framework (framework),
            KEY category (category),
            KEY author_id (author_id),
            KEY is_active (is_active)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Registra las rutas de la API REST
     */
    public function register_rest_routes() {
        $namespace = 'flavor-vbp/v1';

        // Listar componentes.
        register_rest_route(
            $namespace,
            '/code-components',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_components' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Obtener componente por ID.
        register_rest_route(
            $namespace,
            '/code-components/(?P<id>[\w-]+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_component' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Crear componente.
        register_rest_route(
            $namespace,
            '/code-components',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_create_component' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
            )
        );

        // Actualizar componente.
        register_rest_route(
            $namespace,
            '/code-components/(?P<id>[\w-]+)',
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'api_update_component' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
            )
        );

        // Eliminar componente.
        register_rest_route(
            $namespace,
            '/code-components/(?P<id>[\w-]+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'api_delete_component' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
            )
        );

        // Obtener categorias.
        register_rest_route(
            $namespace,
            '/code-components/categories',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_categories' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Obtener frameworks.
        register_rest_route(
            $namespace,
            '/code-components/frameworks',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_frameworks' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Obtener templates.
        register_rest_route(
            $namespace,
            '/code-components/templates',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_templates' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Exportar componente.
        register_rest_route(
            $namespace,
            '/code-components/(?P<id>[\w-]+)/export',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_export_component' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Importar componente.
        register_rest_route(
            $namespace,
            '/code-components/import',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_import_component' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
            )
        );

        // Validar codigo.
        register_rest_route(
            $namespace,
            '/code-components/validate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'api_validate_code' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Obtener dependencias permitidas.
        register_rest_route(
            $namespace,
            '/code-components/dependencies',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'api_get_allowed_dependencies' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );
    }

    /**
     * Verifica permisos de lectura
     *
     * @return bool
     */
    public function check_read_permission() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verifica permisos de escritura
     *
     * @return bool
     */
    public function check_write_permission() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * API: Obtener todos los componentes
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function api_get_components( $request ) {
        $args = array(
            'framework' => $request->get_param( 'framework' ),
            'category'  => $request->get_param( 'category' ),
            'search'    => $request->get_param( 'search' ),
            'author_id' => $request->get_param( 'author_id' ),
            'is_global' => $request->get_param( 'is_global' ),
            'limit'     => $request->get_param( 'limit' ) ? intval( $request->get_param( 'limit' ) ) : 50,
            'offset'    => $request->get_param( 'offset' ) ? intval( $request->get_param( 'offset' ) ) : 0,
        );

        $components = $this->get_components( $args );

        return rest_ensure_response(
            array(
                'success'    => true,
                'components' => $components,
                'total'      => $this->count_components( $args ),
            )
        );
    }

    /**
     * API: Obtener un componente por ID
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function api_get_component( $request ) {
        $component_id = sanitize_key( $request->get_param( 'id' ) );
        $component    = $this->get_component( $component_id );

        if ( ! $component ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'Component not found', 'flavor-platform' ),
                )
            );
        }

        // Incrementar contador de uso.
        $this->increment_usage_count( $component_id );

        return rest_ensure_response(
            array(
                'success'   => true,
                'component' => $component,
            )
        );
    }

    /**
     * API: Crear componente
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function api_create_component( $request ) {
        $data = $request->get_json_params();

        // Validar datos requeridos.
        if ( empty( $data['name'] ) || empty( $data['code'] ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'Name and code are required', 'flavor-platform' ),
                )
            );
        }

        // Validar framework.
        $framework = isset( $data['framework'] ) ? sanitize_key( $data['framework'] ) : 'react';
        if ( ! in_array( $framework, $this->supported_frameworks, true ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'Invalid framework', 'flavor-platform' ),
                )
            );
        }

        // Validar codigo.
        $validation = $this->validate_code( $data['code'], $framework );
        if ( is_wp_error( $validation ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => $validation->get_error_message(),
                )
            );
        }

        // Generar ID unico si no se proporciona.
        $component_id = isset( $data['id'] ) ? sanitize_key( $data['id'] ) : 'cc-' . wp_generate_uuid4();

        // Verificar que no exista.
        if ( $this->get_component( $component_id ) ) {
            $component_id = 'cc-' . wp_generate_uuid4();
        }

        // Validar y filtrar dependencias.
        $dependencies = isset( $data['dependencies'] ) ? $this->validate_dependencies( $data['dependencies'] ) : array();

        // Preparar datos.
        $component_data = array(
            'component_id' => $component_id,
            'name'         => sanitize_text_field( $data['name'] ),
            'framework'    => $framework,
            'category'     => isset( $data['category'] ) ? sanitize_key( $data['category'] ) : 'custom',
            'description'  => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
            'code'         => $data['code'], // El codigo se sanitiza parcialmente para preservar la sintaxis.
            'props'        => isset( $data['props'] ) ? wp_json_encode( $data['props'] ) : '{}',
            'styles'       => isset( $data['styles'] ) ? $data['styles'] : '',
            'dependencies' => wp_json_encode( $dependencies ),
            'default_size' => isset( $data['defaultSize'] ) ? wp_json_encode( $data['defaultSize'] ) : '{"width":200,"height":100}',
            'icon'         => isset( $data['icon'] ) ? sanitize_key( $data['icon'] ) : 'code',
            'version'      => isset( $data['version'] ) ? sanitize_text_field( $data['version'] ) : '1.0.0',
            'author_id'    => get_current_user_id(),
            'is_global'    => isset( $data['is_global'] ) && $data['is_global'] ? 1 : 0,
        );

        // Insertar en base de datos.
        $result = $this->insert_component( $component_data );

        if ( is_wp_error( $result ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                )
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'id'      => $result,
                'message' => __( 'Component created successfully', 'flavor-platform' ),
            )
        );
    }

    /**
     * API: Actualizar componente
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function api_update_component( $request ) {
        $component_id = sanitize_key( $request->get_param( 'id' ) );
        $data         = $request->get_json_params();

        // Verificar que existe.
        $existing = $this->get_component( $component_id );
        if ( ! $existing ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'Component not found', 'flavor-platform' ),
                )
            );
        }

        // Verificar permisos (solo autor o admin).
        if ( $existing['author_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'You do not have permission to edit this component', 'flavor-platform' ),
                )
            );
        }

        // Preparar datos actualizados.
        $update_data = array();

        if ( isset( $data['name'] ) ) {
            $update_data['name'] = sanitize_text_field( $data['name'] );
        }
        if ( isset( $data['framework'] ) && in_array( $data['framework'], $this->supported_frameworks, true ) ) {
            $update_data['framework'] = sanitize_key( $data['framework'] );
        }
        if ( isset( $data['category'] ) ) {
            $update_data['category'] = sanitize_key( $data['category'] );
        }
        if ( isset( $data['description'] ) ) {
            $update_data['description'] = sanitize_textarea_field( $data['description'] );
        }
        if ( isset( $data['code'] ) ) {
            // Validar codigo si se actualiza.
            $framework  = isset( $data['framework'] ) ? $data['framework'] : $existing['framework'];
            $validation = $this->validate_code( $data['code'], $framework );
            if ( is_wp_error( $validation ) ) {
                return rest_ensure_response(
                    array(
                        'success' => false,
                        'message' => $validation->get_error_message(),
                    )
                );
            }
            $update_data['code'] = $data['code'];
        }
        if ( isset( $data['props'] ) ) {
            $update_data['props'] = wp_json_encode( $data['props'] );
        }
        if ( isset( $data['styles'] ) ) {
            $update_data['styles'] = $data['styles'];
        }
        if ( isset( $data['dependencies'] ) ) {
            $update_data['dependencies'] = wp_json_encode( $this->validate_dependencies( $data['dependencies'] ) );
        }
        if ( isset( $data['defaultSize'] ) ) {
            $update_data['default_size'] = wp_json_encode( $data['defaultSize'] );
        }
        if ( isset( $data['icon'] ) ) {
            $update_data['icon'] = sanitize_key( $data['icon'] );
        }
        if ( isset( $data['version'] ) ) {
            $update_data['version'] = sanitize_text_field( $data['version'] );
        }
        if ( isset( $data['is_global'] ) ) {
            $update_data['is_global'] = $data['is_global'] ? 1 : 0;
        }

        // Actualizar.
        $result = $this->update_component( $component_id, $update_data );

        if ( is_wp_error( $result ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                )
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Component updated successfully', 'flavor-platform' ),
            )
        );
    }

    /**
     * API: Eliminar componente
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function api_delete_component( $request ) {
        $component_id = sanitize_key( $request->get_param( 'id' ) );

        // Verificar que existe.
        $existing = $this->get_component( $component_id );
        if ( ! $existing ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'Component not found', 'flavor-platform' ),
                )
            );
        }

        // Verificar permisos.
        if ( $existing['author_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'You do not have permission to delete this component', 'flavor-platform' ),
                )
            );
        }

        // Eliminar.
        $result = $this->delete_component( $component_id );

        if ( is_wp_error( $result ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                )
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Component deleted successfully', 'flavor-platform' ),
            )
        );
    }

    /**
     * API: Obtener categorias
     *
     * @return WP_REST_Response
     */
    public function api_get_categories() {
        return rest_ensure_response(
            array(
                'success'    => true,
                'categories' => $this->categories,
            )
        );
    }

    /**
     * API: Obtener frameworks
     *
     * @return WP_REST_Response
     */
    public function api_get_frameworks() {
        $frameworks = array(
            'react'   => array(
                'name'      => 'React',
                'version'   => '18',
                'extension' => 'jsx',
            ),
            'vue'     => array(
                'name'      => 'Vue',
                'version'   => '3',
                'extension' => 'vue',
            ),
            'svelte'  => array(
                'name'      => 'Svelte',
                'version'   => '4',
                'extension' => 'svelte',
            ),
            'vanilla' => array(
                'name'      => 'Vanilla JS',
                'version'   => 'ES6',
                'extension' => 'js',
            ),
        );

        return rest_ensure_response(
            array(
                'success'    => true,
                'frameworks' => $frameworks,
            )
        );
    }

    /**
     * API: Obtener templates
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function api_get_templates( $request ) {
        $framework = $request->get_param( 'framework' );

        $templates = $this->get_templates( $framework );

        return rest_ensure_response(
            array(
                'success'   => true,
                'templates' => $templates,
            )
        );
    }

    /**
     * API: Exportar componente
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function api_export_component( $request ) {
        $component_id = sanitize_key( $request->get_param( 'id' ) );
        $format       = $request->get_param( 'format' ) ? sanitize_key( $request->get_param( 'format' ) ) : 'json';

        $component = $this->get_component( $component_id );
        if ( ! $component ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'Component not found', 'flavor-platform' ),
                )
            );
        }

        $export_data = $this->export_component( $component, $format );

        return rest_ensure_response(
            array(
                'success' => true,
                'export'  => $export_data,
            )
        );
    }

    /**
     * API: Importar componente
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function api_import_component( $request ) {
        $data = $request->get_json_params();

        if ( empty( $data['import'] ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'No import data provided', 'flavor-platform' ),
                )
            );
        }

        $import_data = $data['import'];

        // Validar estructura.
        if ( empty( $import_data['code'] ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'Invalid import data', 'flavor-platform' ),
                )
            );
        }

        // Crear nuevo request con los datos del import.
        $create_request = new WP_REST_Request( 'POST' );
        $create_request->set_body( wp_json_encode( $import_data ) );
        $create_request->set_header( 'Content-Type', 'application/json' );

        return $this->api_create_component( $create_request );
    }

    /**
     * API: Validar codigo
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function api_validate_code( $request ) {
        $data = $request->get_json_params();

        if ( empty( $data['code'] ) || empty( $data['framework'] ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'message' => __( 'Code and framework are required', 'flavor-platform' ),
                )
            );
        }

        $validation = $this->validate_code( $data['code'], $data['framework'] );

        if ( is_wp_error( $validation ) ) {
            return rest_ensure_response(
                array(
                    'success' => false,
                    'valid'   => false,
                    'errors'  => array( $validation->get_error_message() ),
                )
            );
        }

        return rest_ensure_response(
            array(
                'success'  => true,
                'valid'    => true,
                'warnings' => $validation['warnings'] ?? array(),
            )
        );
    }

    /**
     * API: Obtener dependencias permitidas
     *
     * @return WP_REST_Response
     */
    public function api_get_allowed_dependencies() {
        return rest_ensure_response(
            array(
                'success'      => true,
                'dependencies' => $this->allowed_dependencies,
            )
        );
    }

    /**
     * Obtener componentes de la base de datos
     *
     * @param array $args Argumentos de consulta.
     * @return array
     */
    public function get_components( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'framework' => null,
            'category'  => null,
            'search'    => null,
            'author_id' => null,
            'is_global' => null,
            'is_active' => true,
            'orderby'   => 'updated_at',
            'order'     => 'DESC',
            'limit'     => 50,
            'offset'    => 0,
        );

        $args          = wp_parse_args( $args, $defaults );
        $where_clauses = array( '1=1' );
        $where_values  = array();

        if ( ! empty( $args['framework'] ) && in_array( $args['framework'], $this->supported_frameworks, true ) ) {
            $where_clauses[] = 'framework = %s';
            $where_values[]  = $args['framework'];
        }

        if ( ! empty( $args['category'] ) ) {
            $where_clauses[] = 'category = %s';
            $where_values[]  = $args['category'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where_clauses[] = '(name LIKE %s OR description LIKE %s)';
            $search_term     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where_values[]  = $search_term;
            $where_values[]  = $search_term;
        }

        if ( ! empty( $args['author_id'] ) ) {
            $where_clauses[] = 'author_id = %d';
            $where_values[]  = intval( $args['author_id'] );
        }

        if ( null !== $args['is_global'] ) {
            $where_clauses[] = 'is_global = %d';
            $where_values[]  = $args['is_global'] ? 1 : 0;
        }

        if ( $args['is_active'] ) {
            $where_clauses[] = 'is_active = 1';
        }

        // Sanitizar orderby.
        $allowed_orderby = array( 'name', 'created_at', 'updated_at', 'usage_count' );
        $orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'updated_at';
        $order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $where_sql = implode( ' AND ', $where_clauses );

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE {$where_sql}
            ORDER BY {$orderby} {$order}
            LIMIT %d OFFSET %d",
            array_merge( $where_values, array( $args['limit'], $args['offset'] ) )
        );

        $results = $wpdb->get_results( $query, ARRAY_A );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // Decodificar JSON fields.
        foreach ( $results as &$row ) {
            $row['props']        = json_decode( $row['props'], true ) ?: array();
            $row['dependencies'] = json_decode( $row['dependencies'], true ) ?: array();
            $row['default_size'] = json_decode( $row['default_size'], true ) ?: array( 'width' => 200, 'height' => 100 );
            $row['author_id']    = intval( $row['author_id'] );
            $row['is_global']    = (bool) $row['is_global'];
            $row['is_active']    = (bool) $row['is_active'];
            $row['usage_count']  = intval( $row['usage_count'] );
        }

        return $results;
    }

    /**
     * Contar componentes
     *
     * @param array $args Argumentos de consulta.
     * @return int
     */
    public function count_components( $args = array() ) {
        global $wpdb;

        $where_clauses = array( '1=1' );
        $where_values  = array();

        if ( ! empty( $args['framework'] ) && in_array( $args['framework'], $this->supported_frameworks, true ) ) {
            $where_clauses[] = 'framework = %s';
            $where_values[]  = $args['framework'];
        }

        if ( ! empty( $args['category'] ) ) {
            $where_clauses[] = 'category = %s';
            $where_values[]  = $args['category'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where_clauses[] = '(name LIKE %s OR description LIKE %s)';
            $search_term     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where_values[]  = $search_term;
            $where_values[]  = $search_term;
        }

        if ( ! empty( $args['author_id'] ) ) {
            $where_clauses[] = 'author_id = %d';
            $where_values[]  = intval( $args['author_id'] );
        }

        $where_clauses[] = 'is_active = 1';

        $where_sql = implode( ' AND ', $where_clauses );

        if ( ! empty( $where_values ) ) {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}",
                    $where_values
                )
            );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        } else {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $count = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}"
            );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        return intval( $count );
    }

    /**
     * Obtener un componente por ID
     *
     * @param string $component_id ID del componente.
     * @return array|null
     */
    public function get_component( $component_id ) {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE component_id = %s AND is_active = 1",
                $component_id
            ),
            ARRAY_A
        );

        if ( ! $row ) {
            return null;
        }

        // Decodificar JSON fields.
        $row['props']        = json_decode( $row['props'], true ) ?: array();
        $row['dependencies'] = json_decode( $row['dependencies'], true ) ?: array();
        $row['default_size'] = json_decode( $row['default_size'], true ) ?: array( 'width' => 200, 'height' => 100 );
        $row['author_id']    = intval( $row['author_id'] );
        $row['is_global']    = (bool) $row['is_global'];
        $row['is_active']    = (bool) $row['is_active'];
        $row['usage_count']  = intval( $row['usage_count'] );

        return $row;
    }

    /**
     * Insertar componente
     *
     * @param array $data Datos del componente.
     * @return int|WP_Error
     */
    public function insert_component( $data ) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_error', __( 'Error inserting component', 'flavor-platform' ) );
        }

        return $wpdb->insert_id;
    }

    /**
     * Actualizar componente
     *
     * @param string $component_id ID del componente.
     * @param array  $data         Datos a actualizar.
     * @return bool|WP_Error
     */
    public function update_component( $component_id, $data ) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            $data,
            array( 'component_id' => $component_id )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_error', __( 'Error updating component', 'flavor-platform' ) );
        }

        return true;
    }

    /**
     * Eliminar componente (soft delete)
     *
     * @param string $component_id ID del componente.
     * @return bool|WP_Error
     */
    public function delete_component( $component_id ) {
        global $wpdb;

        $result = $wpdb->update(
            $this->table_name,
            array( 'is_active' => 0 ),
            array( 'component_id' => $component_id )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_error', __( 'Error deleting component', 'flavor-platform' ) );
        }

        return true;
    }

    /**
     * Incrementar contador de uso
     *
     * @param string $component_id ID del componente.
     */
    public function increment_usage_count( $component_id ) {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} SET usage_count = usage_count + 1 WHERE component_id = %s",
                $component_id
            )
        );
    }

    /**
     * Validar codigo del componente
     *
     * @param string $code      Codigo del componente.
     * @param string $framework Framework.
     * @return array|WP_Error
     */
    public function validate_code( $code, $framework ) {
        $warnings = array();

        // Validaciones basicas de seguridad.
        $dangerous_patterns = array(
            '/eval\s*\(/i',
            '/new\s+Function\s*\(/i',
            '/document\.write\s*\(/i',
            '/innerHTML\s*=/i',
            '/outerHTML\s*=/i',
            '/<script[^>]*>/i',
            '/document\.cookie/i',
            '/localStorage\[/i',
            '/sessionStorage\[/i',
            '/XMLHttpRequest/i',
            '/fetch\s*\(/i',
            '/import\s*\(/i', // Dynamic imports.
        );

        foreach ( $dangerous_patterns as $pattern ) {
            if ( preg_match( $pattern, $code ) ) {
                // Algunos patrones generan warnings, no errores.
                if ( preg_match( '/fetch|XMLHttpRequest/', $pattern ) ) {
                    $warnings[] = __( 'Network requests detected. Ensure proper CORS handling.', 'flavor-platform' );
                } elseif ( preg_match( '/localStorage|sessionStorage/', $pattern ) ) {
                    $warnings[] = __( 'Storage access detected. Verify user consent.', 'flavor-platform' );
                } else {
                    return new WP_Error( 'security_error', __( 'Potentially dangerous code detected', 'flavor-platform' ) );
                }
            }
        }

        // Validaciones por framework.
        switch ( $framework ) {
            case 'react':
                if ( ! preg_match( '/function\s+\w+|const\s+\w+\s*=|class\s+\w+/i', $code ) ) {
                    $warnings[] = __( 'No component function or class detected', 'flavor-platform' );
                }
                break;

            case 'vue':
                if ( ! preg_match( '/<template>/i', $code ) && ! preg_match( '/export\s+default/i', $code ) ) {
                    $warnings[] = __( 'No Vue template or export detected', 'flavor-platform' );
                }
                break;

            case 'svelte':
                if ( ! preg_match( '/<script>/i', $code ) && ! preg_match( '/export\s+let/i', $code ) ) {
                    $warnings[] = __( 'No Svelte script block detected', 'flavor-platform' );
                }
                break;

            case 'vanilla':
                if ( ! preg_match( '/class\s+\w+\s+extends\s+HTMLElement/i', $code ) ) {
                    $warnings[] = __( 'No Web Component class detected', 'flavor-platform' );
                }
                break;
        }

        return array( 'warnings' => $warnings );
    }

    /**
     * Validar y filtrar dependencias
     *
     * @param array $dependencies Dependencias solicitadas.
     * @return array Dependencias validadas.
     */
    public function validate_dependencies( $dependencies ) {
        $validated = array();

        if ( ! is_array( $dependencies ) ) {
            return $validated;
        }

        foreach ( $dependencies as $package => $version ) {
            if ( isset( $this->allowed_dependencies[ $package ] ) ) {
                $validated[ $package ] = $version ?: $this->allowed_dependencies[ $package ];
            }
        }

        return $validated;
    }

    /**
     * Obtener templates predefinidos
     *
     * @param string|null $framework Framework para filtrar.
     * @return array
     */
    public function get_templates( $framework = null ) {
        $templates = array(
            'react-basic'       => array(
                'name'        => 'React Basic',
                'framework'   => 'react',
                'description' => 'Simple React functional component',
                'code'        => "function Component({ text = \"Hello World\" }) {\n    return (\n        <div className=\"code-component\">\n            {text}\n        </div>\n    );\n}",
            ),
            'react-stateful'    => array(
                'name'        => 'React with State',
                'framework'   => 'react',
                'description' => 'React component with useState hook',
                'code'        => "function Component({ initialValue = 0, step = 1 }) {\n    const [value, setValue] = React.useState(initialValue);\n\n    return (\n        <div className=\"code-component counter\">\n            <span>{value}</span>\n            <button onClick={() => setValue(v => v - step)}>-</button>\n            <button onClick={() => setValue(v => v + step)}>+</button>\n        </div>\n    );\n}",
            ),
            'vue-basic'         => array(
                'name'        => 'Vue Basic',
                'framework'   => 'vue',
                'description' => 'Simple Vue component',
                'code'        => "<template>\n    <div class=\"code-component\">\n        {{ text }}\n    </div>\n</template>\n\n<script>\nexport default {\n    props: {\n        text: {\n            type: String,\n            default: 'Hello Vue'\n        }\n    }\n}\n</script>",
            ),
            'vue-composition'   => array(
                'name'        => 'Vue Composition API',
                'framework'   => 'vue',
                'description' => 'Vue 3 with Composition API',
                'code'        => "<template>\n    <div class=\"code-component\">\n        <input v-model=\"search\" placeholder=\"Search...\" />\n        <ul>\n            <li v-for=\"item in filteredItems\" :key=\"item\">{{ item }}</li>\n        </ul>\n    </div>\n</template>\n\n<script setup>\nimport { ref, computed } from 'vue';\n\nconst props = defineProps({\n    items: {\n        type: Array,\n        default: () => ['Apple', 'Banana', 'Cherry']\n    }\n});\n\nconst search = ref('');\n\nconst filteredItems = computed(() => {\n    return props.items.filter(item =>\n        item.toLowerCase().includes(search.value.toLowerCase())\n    );\n});\n</script>",
            ),
            'svelte-basic'      => array(
                'name'        => 'Svelte Basic',
                'framework'   => 'svelte',
                'description' => 'Simple Svelte component',
                'code'        => "<script>\n    export let text = \"Hello Svelte\";\n</script>\n\n<div class=\"code-component\">\n    {text}\n</div>",
            ),
            'vanilla-basic'     => array(
                'name'        => 'Web Component',
                'framework'   => 'vanilla',
                'description' => 'Native Web Component',
                'code'        => "class Component extends HTMLElement {\n    static get observedAttributes() {\n        return ['text'];\n    }\n\n    constructor() {\n        super();\n        this.attachShadow({ mode: 'open' });\n    }\n\n    connectedCallback() {\n        this.render();\n    }\n\n    attributeChangedCallback() {\n        this.render();\n    }\n\n    render() {\n        const text = this.getAttribute('text') || 'Hello World';\n        this.shadowRoot.innerHTML = `\n            <div class=\"code-component\">\n                \${text}\n            </div>\n        `;\n    }\n}\n\ncustomElements.define('vbp-component', Component);",
            ),
        );

        // Filtrar por framework si se especifica.
        if ( $framework && in_array( $framework, $this->supported_frameworks, true ) ) {
            $templates = array_filter(
                $templates,
                function ( $template ) use ( $framework ) {
                    return $template['framework'] === $framework;
                }
            );
        }

        return $templates;
    }

    /**
     * Exportar componente
     *
     * @param array  $component Datos del componente.
     * @param string $format    Formato de exportacion.
     * @return array
     */
    public function export_component( $component, $format = 'json' ) {
        $extension_map = array(
            'react'   => 'jsx',
            'vue'     => 'vue',
            'svelte'  => 'svelte',
            'vanilla' => 'js',
        );

        $extension = isset( $extension_map[ $component['framework'] ] )
            ? $extension_map[ $component['framework'] ]
            : 'js';

        $export_data = array(
            'format'   => $format,
            'metadata' => array(
                'id'          => $component['component_id'],
                'name'        => $component['name'],
                'version'     => $component['version'],
                'framework'   => $component['framework'],
                'exported_at' => current_time( 'c' ),
            ),
            'files'    => array(
                'component.' . $extension => $component['code'],
            ),
        );

        // Agregar estilos si existen.
        if ( ! empty( $component['styles'] ) ) {
            $export_data['files']['component.css'] = $component['styles'];
        }

        // package.json.
        $package_json = array(
            'name'         => $component['component_id'],
            'version'      => $component['version'],
            'dependencies' => $component['dependencies'],
        );
        $export_data['files']['package.json'] = wp_json_encode( $package_json, JSON_PRETTY_PRINT );

        // Metadatos VBP.
        $vbp_metadata = array(
            'id'          => $component['component_id'],
            'name'        => $component['name'],
            'framework'   => $component['framework'],
            'props'       => $component['props'],
            'category'    => $component['category'],
            'icon'        => $component['icon'],
            'defaultSize' => $component['default_size'],
        );
        $export_data['files']['vbp-component.json'] = wp_json_encode( $vbp_metadata, JSON_PRETTY_PRINT );

        return $export_data;
    }

    /**
     * Cargar componentes personalizados via hook
     */
    public function load_custom_components() {
        /**
         * Permite que plugins registren componentes personalizados.
         *
         * @param Flavor_VBP_Code_Components $instance Instancia del sistema.
         */
        do_action( 'vbp_register_code_components', $this );
    }

    /**
     * Agregar menu de admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-platform',
            __( 'Code Components', 'flavor-platform' ),
            __( 'Code Components', 'flavor-platform' ),
            'edit_posts',
            'vbp-code-components',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Renderizar pagina de administracion
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <div id="vbp-code-components-admin">
                <p><?php esc_html_e( 'Code Components management is integrated into the Visual Builder Pro editor.', 'flavor-platform' ); ?></p>
                <p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flavor-vbp-editor' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Open VBP Editor', 'flavor-platform' ); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener categorias
     *
     * @return array
     */
    public function get_categories() {
        return $this->categories;
    }

    /**
     * Obtener frameworks soportados
     *
     * @return array
     */
    public function get_supported_frameworks() {
        return $this->supported_frameworks;
    }

    /**
     * Obtener dependencias permitidas
     *
     * @return array
     */
    public function get_allowed_dependencies() {
        return $this->allowed_dependencies;
    }
}

// Inicializar.
add_action( 'plugins_loaded', function () {
    Flavor_VBP_Code_Components::get_instance();
}, 20 );
