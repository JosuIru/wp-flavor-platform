<?php
/**
 * Fuente de colección para el módulo de socios.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Socios_Collection_Source implements Flavor_VBP_Collection_Source {

    use Flavor_VBP_Paginated_Collection_Trait;

    private const ESTADOS_VALIDOS = array( 'activo', 'inactivo', 'baja', 'pendiente' );

    private const TIPOS_SOCIO_VALIDOS = array( 'consumidor', 'productor', 'colaborador', 'voluntario', 'honorario' );

    public function get_identifier() {
        return 'socios';
    }

    public function get_label() {
        return __( 'Socios', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_description() {
        return __( 'Socios registrados en la organización', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    protected function get_default_limit() {
        return 12;
    }

    public function get_query_fields() {
        return array_merge(
            array(
                'estado' => array(
                    'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'type'    => 'enum',
                    'options' => self::ESTADOS_VALIDOS,
                    'default' => 'activo',
                ),
                'tipo_socio' => array(
                    'label'   => __( 'Tipo de socio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'type'    => 'enum',
                    'options' => array_merge( array( '' ), self::TIPOS_SOCIO_VALIDOS ),
                    'default' => '',
                ),
                'orden' => array(
                    'label'   => __( 'Orden', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'type'    => 'enum',
                    'options' => array( 'recientes', 'antiguos', 'numero' ),
                    'default' => 'recientes',
                ),
            ),
            $this->get_pagination_fields()
        );
    }

    public function query( array $query_args ) {
        global $wpdb;

        $tabla_socios = $this->get_table_name();

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_socios ) ) !== $tabla_socios ) {
            return array();
        }

        list( $clausula_where, $args_where ) = $this->build_where_and_args( $query_args );
        $clausula_order = $this->build_order_clause( isset( $query_args['orden'] ) ? (string) $query_args['orden'] : 'recientes' );
        $paginacion     = $this->extract_pagination( $query_args );

        $sql = "SELECT id, numero_socio, tipo_socio, fecha_alta, usuario_id
                FROM {$tabla_socios}
                WHERE {$clausula_where}
                {$clausula_order}
                LIMIT %d OFFSET %d";

        $args_where[] = $paginacion['limit'];
        $args_where[] = $paginacion['offset'];

        $filas = $wpdb->get_results( $wpdb->prepare( $sql, $args_where ), ARRAY_A );

        if ( ! is_array( $filas ) ) {
            return array();
        }

        return array_map( array( $this, 'normalize_socio_row' ), $filas );
    }

    protected function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'flavor_socios';
    }

    protected function build_where_and_args( array $query_args ) {
        $where_parts = array( 'estado = %s' );
        $where_args  = array( isset( $query_args['estado'] ) ? (string) $query_args['estado'] : 'activo' );

        if ( ! empty( $query_args['tipo_socio'] ) ) {
            $where_parts[] = 'tipo_socio = %s';
            $where_args[]  = (string) $query_args['tipo_socio'];
        }

        return array( implode( ' AND ', $where_parts ), $where_args );
    }

    private function build_order_clause( $opcion_orden ) {
        $mapa = array(
            'recientes' => 'ORDER BY fecha_alta DESC',
            'antiguos'  => 'ORDER BY fecha_alta ASC',
            'numero'    => 'ORDER BY numero_socio ASC',
        );
        return isset( $mapa[ $opcion_orden ] ) ? $mapa[ $opcion_orden ] : $mapa['recientes'];
    }

    private function normalize_socio_row( array $fila ) {
        $id_usuario     = isset( $fila['usuario_id'] ) ? (int) $fila['usuario_id'] : 0;
        $numero_socio   = isset( $fila['numero_socio'] ) ? (string) $fila['numero_socio'] : '';
        $nombre_mostrar = sprintf( __( 'Socio #%s', FLAVOR_PLATFORM_TEXT_DOMAIN ), $numero_socio );
        $imagen_avatar  = '';

        if ( $id_usuario > 0 ) {
            $datos_usuario = get_userdata( $id_usuario );
            if ( $datos_usuario ) {
                $nombre_mostrar = $datos_usuario->display_name;
                $imagen_avatar  = get_avatar_url( $id_usuario, array( 'size' => 200 ) );
            }
        }

        return array(
            'id'      => (int) $fila['id'],
            'title'   => $nombre_mostrar,
            'excerpt' => '',
            'image'   => (string) $imagen_avatar,
            'url'     => $this->build_socio_url( $fila ),
            'date'    => isset( $fila['fecha_alta'] ) ? (string) $fila['fecha_alta'] : '',
            'meta'    => array(
                'numero_socio' => $numero_socio,
                'tipo_socio'   => isset( $fila['tipo_socio'] ) ? (string) $fila['tipo_socio'] : '',
            ),
        );
    }

    private function build_socio_url( array $fila ) {
        if ( class_exists( 'Flavor_Platform_Helpers' ) && method_exists( 'Flavor_Platform_Helpers', 'get_action_url' ) ) {
            return (string) Flavor_Platform_Helpers::get_action_url( 'socios', 'detalle' ) . '?socio_id=' . (int) $fila['id'];
        }
        return home_url( '?p_socio=' . (int) $fila['id'] );
    }
}
