<?php
/**
 * Fuente de colección para el módulo de eventos.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Eventos_Collection_Source implements Flavor_VBP_Collection_Source {

    use Flavor_VBP_Paginated_Collection_Trait;

    private const ESTADOS_VALIDOS = array( 'publicado', 'borrador', 'cancelado', 'archivado' );

    public function get_identifier() {
        return 'eventos';
    }

    public function get_label() {
        return __( 'Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_description() {
        return __( 'Eventos publicados en la plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_query_fields() {
        return array_merge(
            array(
                'estado' => array(
                    'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'type'    => 'enum',
                    'options' => self::ESTADOS_VALIDOS,
                    'default' => 'publicado',
                ),
                'fecha_desde' => array(
                    'label'   => __( 'Desde (incluido)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'type'    => 'date',
                    'default' => null,
                ),
                'fecha_hasta' => array(
                    'label'   => __( 'Hasta (incluido)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'type'    => 'date',
                    'default' => null,
                ),
                'busqueda' => array(
                    'label'   => __( 'Búsqueda en título y descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'type'    => 'string',
                    'default' => '',
                ),
                'orden' => array(
                    'label'   => __( 'Orden', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'type'    => 'enum',
                    'options' => array( 'proximos', 'recientes', 'antiguos' ),
                    'default' => 'proximos',
                ),
            ),
            $this->get_pagination_fields()
        );
    }

    public function query( array $query_args ) {
        global $wpdb;

        $tabla_eventos = $this->get_table_name();

        list( $clausula_where, $args_where ) = $this->build_where_and_args( $query_args );
        $clausula_order = $this->build_order_clause( isset( $query_args['orden'] ) ? (string) $query_args['orden'] : 'proximos' );
        $paginacion     = $this->extract_pagination( $query_args );

        $sql = "SELECT id, titulo, descripcion, fecha_inicio, fecha_fin, imagen, tipo
                FROM {$tabla_eventos}
                WHERE {$clausula_where}
                {$clausula_order}
                LIMIT %d OFFSET %d";

        $args_where[] = $paginacion['limit'];
        $args_where[] = $paginacion['offset'];

        $filas = $wpdb->get_results( $wpdb->prepare( $sql, $args_where ), ARRAY_A );

        if ( ! is_array( $filas ) ) {
            return array();
        }

        return array_map( array( $this, 'normalize_event_row' ), $filas );
    }

    protected function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'flavor_eventos';
    }

    protected function build_where_and_args( array $query_args ) {
        global $wpdb;

        $where_parts = array();
        $where_args  = array();

        $estado_filtro = isset( $query_args['estado'] ) ? (string) $query_args['estado'] : 'publicado';
        $where_parts[] = 'estado = %s';
        $where_args[]  = $estado_filtro;

        if ( ! empty( $query_args['fecha_desde'] ) ) {
            $where_parts[] = 'fecha_inicio >= %s';
            $where_args[]  = $query_args['fecha_desde'] . ' 00:00:00';
        }

        if ( ! empty( $query_args['fecha_hasta'] ) ) {
            $where_parts[] = 'fecha_inicio <= %s';
            $where_args[]  = $query_args['fecha_hasta'] . ' 23:59:59';
        }

        if ( ! empty( $query_args['busqueda'] ) ) {
            $where_parts[] = '( titulo LIKE %s OR descripcion LIKE %s )';
            $termino_like  = '%' . $wpdb->esc_like( $query_args['busqueda'] ) . '%';
            $where_args[]  = $termino_like;
            $where_args[]  = $termino_like;
        }

        return array( implode( ' AND ', $where_parts ), $where_args );
    }

    private function build_order_clause( $opcion_orden ) {
        $mapa_orden = array(
            'proximos'  => 'ORDER BY fecha_inicio ASC',
            'recientes' => 'ORDER BY fecha_inicio DESC',
            'antiguos'  => 'ORDER BY fecha_inicio ASC',
        );
        return isset( $mapa_orden[ $opcion_orden ] ) ? $mapa_orden[ $opcion_orden ] : $mapa_orden['proximos'];
    }

    private function normalize_event_row( array $fila_cruda ) {
        return array(
            'id'      => (int) $fila_cruda['id'],
            'title'   => (string) $fila_cruda['titulo'],
            'excerpt' => wp_trim_words( wp_strip_all_tags( (string) ( $fila_cruda['descripcion'] ?? '' ) ), 30 ),
            'image'   => (string) ( $fila_cruda['imagen'] ?? '' ),
            'url'     => $this->build_event_url( $fila_cruda ),
            'date'    => (string) ( $fila_cruda['fecha_inicio'] ?? '' ),
            'meta'    => array(
                'fecha_fin' => isset( $fila_cruda['fecha_fin'] ) ? (string) $fila_cruda['fecha_fin'] : '',
                'tipo'      => isset( $fila_cruda['tipo'] ) ? (string) $fila_cruda['tipo'] : '',
            ),
        );
    }

    private function build_event_url( array $fila ) {
        if ( class_exists( 'Flavor_Platform_Helpers' ) && method_exists( 'Flavor_Platform_Helpers', 'get_action_url' ) ) {
            return (string) Flavor_Platform_Helpers::get_action_url( 'eventos', 'detalle' ) . '?evento_id=' . (int) $fila['id'];
        }
        return home_url( '?p_evento=' . (int) $fila['id'] );
    }
}
