<?php
/**
 * Visual Builder Pro - Biblioteca de Componentes Reutilizables
 *
 * Permite guardar, gestionar y reutilizar componentes/bloques
 * en diferentes páginas del editor.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.21
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar la biblioteca de componentes
 */
class Flavor_VBP_Component_Library {

    private static $instancia = null;
    private $tabla_componentes;

    private $categorias_predefinidas = array(
        'headers'    => array( 'name' => 'Cabeceras', 'icon' => 'layout' ),
        'heroes'     => array( 'name' => 'Heroes', 'icon' => 'star' ),
        'sections'   => array( 'name' => 'Secciones', 'icon' => 'layers' ),
        'footers'    => array( 'name' => 'Footers', 'icon' => 'menu' ),
        'cards'      => array( 'name' => 'Tarjetas', 'icon' => 'grid' ),
        'forms'      => array( 'name' => 'Formularios', 'icon' => 'edit-3' ),
        'navigation' => array( 'name' => 'Navegación', 'icon' => 'compass' ),
        'custom'     => array( 'name' => 'Personalizados', 'icon' => 'folder' ),
    );

    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        global $wpdb;
        $this->tabla_componentes = $wpdb->prefix . 'vbp_components';
        $this->crear_tabla();
        add_action( 'rest_api_init', array( $this, 'registrar_endpoints' ) );
    }

    private function crear_tabla() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tabla = $this->tabla_componentes;

        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            category varchar(100) NOT NULL DEFAULT 'custom',
            description text,
            blocks longtext NOT NULL,
            thumbnail varchar(500),
            tags varchar(500),
            author_id bigint(20) unsigned NOT NULL,
            is_global tinyint(1) NOT NULL DEFAULT 0,
            usage_count int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY author_id (author_id),
            KEY slug (slug)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public function registrar_endpoints() {
        $ns = 'flavor-vbp/v1';

        register_rest_route( $ns, '/components', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'api_get_components' ),
            'permission_callback' => array( $this, 'verificar_permisos' ),
        ) );

        register_rest_route( $ns, '/components/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'api_get_component' ),
            'permission_callback' => array( $this, 'verificar_permisos' ),
        ) );

        register_rest_route( $ns, '/components', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'api_save_component' ),
            'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
        ) );

        register_rest_route( $ns, '/components/(?P<id>\d+)', array(
            'methods'             => 'PUT',
            'callback'            => array( $this, 'api_update_component' ),
            'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
        ) );

        register_rest_route( $ns, '/components/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'api_delete_component' ),
            'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
        ) );

        register_rest_route( $ns, '/components/categories', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'api_get_categories' ),
            'permission_callback' => array( $this, 'verificar_permisos' ),
        ) );

        register_rest_route( $ns, '/components/(?P<id>\d+)/export', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'api_export_component' ),
            'permission_callback' => array( $this, 'verificar_permisos' ),
        ) );

        register_rest_route( $ns, '/components/import', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'api_import_component' ),
            'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
        ) );
    }

    public function verificar_permisos() {
        return current_user_can( 'edit_posts' );
    }

    public function verificar_permisos_escritura() {
        return current_user_can( 'edit_posts' );
    }

    public function save_component( $name, $blocks, $category = 'custom', $opciones = array() ) {
        global $wpdb;

        if ( empty( $name ) || empty( $blocks ) ) {
            return new WP_Error( 'invalid_data', __( 'Nombre y bloques son requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        $slug = sanitize_title( $name );
        $descripcion = isset( $opciones['description'] ) ? sanitize_textarea_field( $opciones['description'] ) : '';
        $thumbnail = isset( $opciones['thumbnail'] ) ? esc_url_raw( $opciones['thumbnail'] ) : '';
        $tags = isset( $opciones['tags'] ) ? sanitize_text_field( $opciones['tags'] ) : '';
        $is_global = isset( $opciones['is_global'] ) && $opciones['is_global'] ? 1 : 0;

        if ( ! isset( $this->categorias_predefinidas[ $category ] ) ) {
            $category = 'custom';
        }

        $resultado = $wpdb->insert(
            $this->tabla_componentes,
            array(
                'name'        => sanitize_text_field( $name ),
                'slug'        => $slug,
                'category'    => $category,
                'description' => $descripcion,
                'blocks'      => wp_json_encode( $blocks ),
                'thumbnail'   => $thumbnail,
                'tags'        => $tags,
                'author_id'   => get_current_user_id(),
                'is_global'   => $is_global,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
        );

        if ( false === $resultado ) {
            return new WP_Error( 'db_error', __( 'Error al guardar el componente', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        return $wpdb->insert_id;
    }

    public function get_components( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'category'   => null,
            'author_id'  => null,
            'search'     => null,
            'is_global'  => null,
            'orderby'    => 'updated_at',
            'order'      => 'DESC',
            'limit'      => 50,
            'offset'     => 0,
        );

        $args = wp_parse_args( $args, $defaults );
        $where_clauses = array( '1=1' );
        $where_values = array();

        if ( ! empty( $args['category'] ) ) {
            $where_clauses[] = 'category = %s';
            $where_values[] = $args['category'];
        }

        if ( ! empty( $args['author_id'] ) ) {
            $where_clauses[] = '(author_id = %d OR is_global = 1)';
            $where_values[] = $args['author_id'];
        }

        if ( ! empty( $args['search'] ) ) {
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where_clauses[] = '(name LIKE %s OR description LIKE %s OR tags LIKE %s)';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        if ( $args['is_global'] !== null ) {
            $where_clauses[] = 'is_global = %d';
            $where_values[] = $args['is_global'] ? 1 : 0;
        }

        $where_sql = implode( ' AND ', $where_clauses );
        $allowed_orderby = array( 'name', 'category', 'created_at', 'updated_at', 'usage_count' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'updated_at';
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
        $tabla = $this->tabla_componentes;

        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];

        $query = "SELECT * FROM $tabla WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $resultados = $wpdb->get_results( $wpdb->prepare( $query, $where_values ), ARRAY_A );

        foreach ( $resultados as &$componente ) {
            $componente['blocks'] = json_decode( $componente['blocks'], true );
            $componente['author_name'] = get_the_author_meta( 'display_name', $componente['author_id'] );
        }

        return $resultados;
    }

    public function get_component( $component_id ) {
        global $wpdb;
        $tabla = $this->tabla_componentes;

        $componente = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $tabla WHERE id = %d", $component_id ),
            ARRAY_A
        );

        if ( ! $componente ) {
            return null;
        }

        $componente['blocks'] = json_decode( $componente['blocks'], true );
        $componente['author_name'] = get_the_author_meta( 'display_name', $componente['author_id'] );

        return $componente;
    }

    public function update_component( $component_id, $data ) {
        global $wpdb;

        $componente_actual = $this->get_component( $component_id );
        if ( ! $componente_actual ) {
            return new WP_Error( 'not_found', __( 'Componente no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        if ( $componente_actual['author_id'] != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'forbidden', __( 'No tienes permisos para editar este componente', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        $campos = array();
        $tipos = array();

        if ( isset( $data['name'] ) ) {
            $campos['name'] = sanitize_text_field( $data['name'] );
            $campos['slug'] = sanitize_title( $data['name'] );
            $tipos[] = '%s';
            $tipos[] = '%s';
        }

        if ( isset( $data['category'] ) ) {
            $cat = isset( $this->categorias_predefinidas[ $data['category'] ] ) ? $data['category'] : 'custom';
            $campos['category'] = $cat;
            $tipos[] = '%s';
        }

        if ( isset( $data['description'] ) ) {
            $campos['description'] = sanitize_textarea_field( $data['description'] );
            $tipos[] = '%s';
        }

        if ( isset( $data['blocks'] ) ) {
            $campos['blocks'] = wp_json_encode( $data['blocks'] );
            $tipos[] = '%s';
        }

        if ( isset( $data['thumbnail'] ) ) {
            $campos['thumbnail'] = esc_url_raw( $data['thumbnail'] );
            $tipos[] = '%s';
        }

        if ( isset( $data['tags'] ) ) {
            $campos['tags'] = sanitize_text_field( $data['tags'] );
            $tipos[] = '%s';
        }

        if ( isset( $data['is_global'] ) && current_user_can( 'manage_options' ) ) {
            $campos['is_global'] = $data['is_global'] ? 1 : 0;
            $tipos[] = '%d';
        }

        if ( empty( $campos ) ) {
            return new WP_Error( 'no_data', __( 'No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        $resultado = $wpdb->update( $this->tabla_componentes, $campos, array( 'id' => $component_id ), $tipos, array( '%d' ) );
        return $resultado !== false;
    }

    public function delete_component( $component_id ) {
        global $wpdb;

        $componente = $this->get_component( $component_id );
        if ( ! $componente ) {
            return new WP_Error( 'not_found', __( 'Componente no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        if ( $componente['author_id'] != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'forbidden', __( 'No tienes permisos para eliminar este componente', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        $resultado = $wpdb->delete( $this->tabla_componentes, array( 'id' => $component_id ), array( '%d' ) );
        return $resultado !== false;
    }

    public function incrementar_uso( $component_id ) {
        global $wpdb;
        $tabla = $this->tabla_componentes;
        $wpdb->query( $wpdb->prepare( "UPDATE $tabla SET usage_count = usage_count + 1 WHERE id = %d", $component_id ) );
    }

    public function export_component( $component_id ) {
        $componente = $this->get_component( $component_id );
        if ( ! $componente ) {
            return new WP_Error( 'not_found', __( 'Componente no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        return array(
            'version'     => '1.0',
            'type'        => 'vbp-component',
            'name'        => $componente['name'],
            'category'    => $componente['category'],
            'description' => $componente['description'],
            'blocks'      => $componente['blocks'],
            'tags'        => $componente['tags'],
            'exported_at' => current_time( 'mysql' ),
        );
    }

    public function import_component( $data ) {
        if ( ! isset( $data['type'] ) || $data['type'] !== 'vbp-component' ) {
            return new WP_Error( 'invalid_format', __( 'Formato de archivo inválido', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        if ( ! isset( $data['name'] ) || ! isset( $data['blocks'] ) ) {
            return new WP_Error( 'missing_data', __( 'Faltan datos requeridos', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
        }

        return $this->save_component(
            $data['name'],
            $data['blocks'],
            isset( $data['category'] ) ? $data['category'] : 'custom',
            array(
                'description' => isset( $data['description'] ) ? $data['description'] : '',
                'tags'        => isset( $data['tags'] ) ? $data['tags'] : '',
            )
        );
    }

    public function get_categories() {
        global $wpdb;
        $tabla = $this->tabla_componentes;
        $conteos = $wpdb->get_results( "SELECT category, COUNT(*) as count FROM $tabla GROUP BY category", OBJECT_K );

        $categorias = array();
        foreach ( $this->categorias_predefinidas as $id => $cat ) {
            $categorias[] = array(
                'id'    => $id,
                'name'  => $cat['name'],
                'icon'  => $cat['icon'],
                'count' => isset( $conteos[ $id ] ) ? (int) $conteos[ $id ]->count : 0,
            );
        }
        return $categorias;
    }

    // REST API Callbacks
    public function api_get_components( $request ) {
        $args = array(
            'category'  => $request->get_param( 'category' ),
            'search'    => $request->get_param( 'search' ),
            'author_id' => get_current_user_id(),
            'orderby'   => $request->get_param( 'orderby' ) ?: 'updated_at',
            'order'     => $request->get_param( 'order' ) ?: 'DESC',
            'limit'     => $request->get_param( 'limit' ) ?: 50,
            'offset'    => $request->get_param( 'offset' ) ?: 0,
        );

        return rest_ensure_response( array(
            'success'    => true,
            'components' => $this->get_components( $args ),
        ) );
    }

    public function api_get_component( $request ) {
        $id = $request->get_param( 'id' );
        $componente = $this->get_component( $id );

        if ( ! $componente ) {
            return new WP_Error( 'not_found', __( 'Componente no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ), array( 'status' => 404 ) );
        }

        $this->incrementar_uso( $id );
        return rest_ensure_response( array( 'success' => true, 'component' => $componente ) );
    }

    public function api_save_component( $request ) {
        $resultado = $this->save_component(
            $request->get_param( 'name' ),
            $request->get_param( 'blocks' ),
            $request->get_param( 'category' ) ?: 'custom',
            array(
                'description' => $request->get_param( 'description' ),
                'thumbnail'   => $request->get_param( 'thumbnail' ),
                'tags'        => $request->get_param( 'tags' ),
                'is_global'   => $request->get_param( 'is_global' ),
            )
        );

        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }

        return rest_ensure_response( array( 'success' => true, 'id' => $resultado, 'message' => __( 'Componente guardado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
    }

    public function api_update_component( $request ) {
        $resultado = $this->update_component( $request->get_param( 'id' ), $request->get_json_params() );
        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }
        return rest_ensure_response( array( 'success' => true, 'message' => __( 'Componente actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
    }

    public function api_delete_component( $request ) {
        $resultado = $this->delete_component( $request->get_param( 'id' ) );
        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }
        return rest_ensure_response( array( 'success' => true, 'message' => __( 'Componente eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
    }

    public function api_get_categories( $request ) {
        return rest_ensure_response( array( 'success' => true, 'categories' => $this->get_categories() ) );
    }

    public function api_export_component( $request ) {
        $resultado = $this->export_component( $request->get_param( 'id' ) );
        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }
        return rest_ensure_response( array( 'success' => true, 'data' => $resultado ) );
    }

    public function api_import_component( $request ) {
        $resultado = $this->import_component( $request->get_json_params() );
        if ( is_wp_error( $resultado ) ) {
            return $resultado;
        }
        return rest_ensure_response( array( 'success' => true, 'id' => $resultado, 'message' => __( 'Componente importado', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
    }
}

// Inicializar
add_action( 'init', function() {
    Flavor_VBP_Component_Library::get_instance();
} );
