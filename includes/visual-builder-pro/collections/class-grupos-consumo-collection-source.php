<?php
/**
 * Fuente de colección para grupos de consumo.
 *
 * Expone los grupos de consumo activos con filtros por estado y búsqueda
 * textual sobre nombre/descripción/ubicación. Los items devuelven imagen,
 * nombre y ubicación para rellenar las tarjetas del canvas.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Grupos_Consumo_Collection_Source implements Flavor_VBP_Collection_Source {

    private const ESTADOS_VALIDOS = array( 'activo', 'inactivo', 'pausado', 'archivado' );

    public function get_identifier() {
        return 'grupos_consumo';
    }

    public function get_label() {
        return __( 'Grupos de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_description() {
        return __( 'Grupos de consumo locales para compra colectiva', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_query_fields() {
        return array(
            'estado' => array(
                'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => self::ESTADOS_VALIDOS,
                'default' => 'activo',
            ),
            'busqueda' => array(
                'label'   => __( 'Búsqueda (nombre, descripción, ubicación)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'string',
                'default' => '',
            ),
            'orden' => array(
                'label'   => __( 'Orden', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => array( 'recientes', 'alfabetico', 'antiguos' ),
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

        $tabla_grupos = $wpdb->prefix . 'flavor_gc_grupos';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_grupos ) ) !== $tabla_grupos ) {
            return array();
        }

        $where_parts = array( 'estado = %s' );
        $where_args  = array( isset( $query_args['estado'] ) ? (string) $query_args['estado'] : 'activo' );

        if ( ! empty( $query_args['busqueda'] ) ) {
            $where_parts[] = '( nombre LIKE %s OR descripcion LIKE %s OR ubicacion LIKE %s )';
            $termino_like  = '%' . $wpdb->esc_like( $query_args['busqueda'] ) . '%';
            $where_args[]  = $termino_like;
            $where_args[]  = $termino_like;
            $where_args[]  = $termino_like;
        }

        $clausula_where = implode( ' AND ', $where_parts );
        $clausula_order = $this->build_order_clause( isset( $query_args['orden'] ) ? (string) $query_args['orden'] : 'recientes' );
        $limite         = isset( $query_args['limit'] ) ? (int) $query_args['limit'] : 12;
        $limite         = max( 1, min( 50, $limite ) );

        $sql = "SELECT id, nombre, slug, descripcion, imagen_url, ubicacion, fecha_creacion
                FROM {$tabla_grupos}
                WHERE {$clausula_where}
                {$clausula_order}
                LIMIT %d";

        $where_args[] = $limite;

        $filas = $wpdb->get_results( $wpdb->prepare( $sql, $where_args ), ARRAY_A );

        if ( ! is_array( $filas ) ) {
            return array();
        }

        return array_map( array( $this, 'normalize_grupo_row' ), $filas );
    }

    private function build_order_clause( $opcion_orden ) {
        $mapa = array(
            'recientes'   => 'ORDER BY fecha_creacion DESC',
            'alfabetico'  => 'ORDER BY nombre ASC',
            'antiguos'    => 'ORDER BY fecha_creacion ASC',
        );
        return isset( $mapa[ $opcion_orden ] ) ? $mapa[ $opcion_orden ] : $mapa['recientes'];
    }

    private function normalize_grupo_row( array $fila ) {
        $descripcion_raw = isset( $fila['descripcion'] ) ? (string) $fila['descripcion'] : '';
        $ubicacion_grupo = isset( $fila['ubicacion'] ) ? (string) $fila['ubicacion'] : '';

        $extracto_grupo = $descripcion_raw !== ''
            ? wp_trim_words( wp_strip_all_tags( $descripcion_raw ), 24 )
            : $ubicacion_grupo;

        return array(
            'id'      => (int) $fila['id'],
            'title'   => isset( $fila['nombre'] ) ? (string) $fila['nombre'] : '',
            'excerpt' => $extracto_grupo,
            'image'   => isset( $fila['imagen_url'] ) ? (string) $fila['imagen_url'] : '',
            'url'     => $this->build_grupo_url( $fila ),
            'date'    => isset( $fila['fecha_creacion'] ) ? (string) $fila['fecha_creacion'] : '',
            'meta'    => array(
                'slug'       => isset( $fila['slug'] ) ? (string) $fila['slug'] : '',
                'ubicacion'  => $ubicacion_grupo,
            ),
        );
    }

    private function build_grupo_url( array $fila ) {
        if ( class_exists( 'Flavor_Platform_Helpers' ) && method_exists( 'Flavor_Platform_Helpers', 'get_action_url' ) ) {
            return (string) Flavor_Platform_Helpers::get_action_url( 'grupos-consumo', 'detalle' ) . '?grupo_id=' . (int) $fila['id'];
        }
        return home_url( '?p_grupo=' . (int) $fila['id'] );
    }
}
