<?php
/**
 * Fuente de colección para el módulo de eventos.
 *
 * Expone los eventos publicados al editor VBP con filtros por estado,
 * rango de fechas, tipo y búsqueda textual. La consulta se ejecuta
 * directamente sobre la tabla flavor_eventos usando los índices
 * compuestos añadidos en la migración 2024_04_17_000001.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Eventos_Collection_Source implements Flavor_VBP_Collection_Source {

    /**
     * Estados válidos en la tabla flavor_eventos. Se usan como whitelist para
     * evitar SQL injection vía parámetro `estado`.
     *
     * @var string[]
     */
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
        return array(
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
            'limit' => array(
                'label'   => __( 'Máximo de items', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'int',
                'min'     => 1,
                'max'     => 50,
                'default' => 10,
            ),
        );
    }

    public function query( array $query_args ) {
        global $wpdb;

        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        $where_parts = array();
        $where_args  = array();

        // Estado: ya validado por el registry contra la whitelist de enum.
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

        $clausula_where = implode( ' AND ', $where_parts );
        $clausula_order = $this->build_order_clause( isset( $query_args['orden'] ) ? (string) $query_args['orden'] : 'proximos' );
        $limite         = isset( $query_args['limit'] ) ? (int) $query_args['limit'] : 10;
        $limite         = max( 1, min( 50, $limite ) );

        $sql = "SELECT id, titulo, descripcion, fecha_inicio, fecha_fin, imagen, tipo
                FROM {$tabla_eventos}
                WHERE {$clausula_where}
                {$clausula_order}
                LIMIT %d";

        $where_args[] = $limite;

        $filas = $wpdb->get_results( $wpdb->prepare( $sql, $where_args ), ARRAY_A );

        if ( ! is_array( $filas ) ) {
            return array();
        }

        return array_map( array( $this, 'normalize_event_row' ), $filas );
    }

    /**
     * Construye la cláusula ORDER BY a partir de la opción de orden, que ya
     * viene validada por el registry contra el whitelist de enum. No hay
     * input libre aquí.
     *
     * @param string $opcion_orden Valor del enum `orden`.
     * @return string
     */
    private function build_order_clause( $opcion_orden ) {
        $mapa_orden = array(
            'proximos' => 'ORDER BY fecha_inicio ASC',
            'recientes' => 'ORDER BY fecha_inicio DESC',
            'antiguos' => 'ORDER BY fecha_inicio ASC',
        );
        return isset( $mapa_orden[ $opcion_orden ] ) ? $mapa_orden[ $opcion_orden ] : $mapa_orden['proximos'];
    }

    /**
     * Convierte una fila cruda de la tabla en el shape normalizado que los
     * bloques del canvas consumen.
     *
     * @param array<string, mixed> $fila_cruda Fila de la tabla flavor_eventos.
     * @return array<string, mixed>
     */
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

    /**
     * Construye la URL canónica del evento.
     *
     * @param array<string, mixed> $fila Fila del evento.
     * @return string
     */
    private function build_event_url( array $fila ) {
        if ( class_exists( 'Flavor_Platform_Helpers' ) && method_exists( 'Flavor_Platform_Helpers', 'get_action_url' ) ) {
            return (string) Flavor_Platform_Helpers::get_action_url( 'eventos', 'detalle' ) . '?evento_id=' . (int) $fila['id'];
        }
        return home_url( '?p_evento=' . (int) $fila['id'] );
    }
}
