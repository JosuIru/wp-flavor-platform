<?php
/**
 * Fuente de colección para el módulo de socios.
 *
 * Expone socios activos al editor VBP con filtros por estado, tipo y
 * búsqueda sobre el número de socio. La consulta no expone datos
 * sensibles (datos bancarios, cuotas, notas) — solo lo necesario para
 * listarlos públicamente: número, tipo, fecha de alta y usuario asociado
 * para mostrar nombre/avatar.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Socios_Collection_Source implements Flavor_VBP_Collection_Source {

    /**
     * Whitelist de estados válidos para evitar input arbitrario.
     */
    private const ESTADOS_VALIDOS = array( 'activo', 'inactivo', 'baja', 'pendiente' );

    /**
     * Whitelist de tipos de socio más comunes en la plataforma.
     */
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

    public function get_query_fields() {
        return array(
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
            'limit' => array(
                'label'   => __( 'Máximo de items', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'int',
                'min'     => 1,
                'max'     => 50,
                'default' => 12,
            ),
        );
    }

    public function query( array $query_args ) {
        global $wpdb;

        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_socios ) ) !== $tabla_socios ) {
            return array();
        }

        $where_parts = array( 'estado = %s' );
        $where_args  = array( isset( $query_args['estado'] ) ? (string) $query_args['estado'] : 'activo' );

        if ( ! empty( $query_args['tipo_socio'] ) ) {
            $where_parts[] = 'tipo_socio = %s';
            $where_args[]  = (string) $query_args['tipo_socio'];
        }

        $clausula_where = implode( ' AND ', $where_parts );
        $clausula_order = $this->build_order_clause( isset( $query_args['orden'] ) ? (string) $query_args['orden'] : 'recientes' );
        $limite         = isset( $query_args['limit'] ) ? (int) $query_args['limit'] : 12;
        $limite         = max( 1, min( 50, $limite ) );

        $sql = "SELECT id, numero_socio, tipo_socio, fecha_alta, usuario_id
                FROM {$tabla_socios}
                WHERE {$clausula_where}
                {$clausula_order}
                LIMIT %d";

        $where_args[] = $limite;

        $filas = $wpdb->get_results( $wpdb->prepare( $sql, $where_args ), ARRAY_A );

        if ( ! is_array( $filas ) ) {
            return array();
        }

        return array_map( array( $this, 'normalize_socio_row' ), $filas );
    }

    /**
     * Construye la cláusula ORDER BY a partir de la opción de orden. El valor
     * viene validado contra el whitelist de enum por el registry.
     *
     * @param string $opcion_orden
     * @return string
     */
    private function build_order_clause( $opcion_orden ) {
        $mapa = array(
            'recientes' => 'ORDER BY fecha_alta DESC',
            'antiguos'  => 'ORDER BY fecha_alta ASC',
            'numero'    => 'ORDER BY numero_socio ASC',
        );
        return isset( $mapa[ $opcion_orden ] ) ? $mapa[ $opcion_orden ] : $mapa['recientes'];
    }

    /**
     * Normaliza una fila al shape estándar. Enriquecemos con display_name y
     * avatar a partir de usuario_id para que las tarjetas tengan imagen
     * (los socios no tienen columna imagen propia).
     *
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
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
