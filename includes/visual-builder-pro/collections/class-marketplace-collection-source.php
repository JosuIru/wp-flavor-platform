<?php
/**
 * Fuente de colección para el marketplace.
 *
 * Expone los anuncios activos con filtros por tipo (venta/compra/
 * intercambio/regalo), condición, precio gratuito y búsqueda textual.
 * Usa imagen_principal como portada y oculta datos de contacto (email,
 * teléfono) que son campos privados del anuncio.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Marketplace_Collection_Source implements Flavor_VBP_Collection_Source {

    private const ESTADOS_VALIDOS = array( 'publicado', 'pendiente', 'vendido', 'pausado', 'expirado', 'archivado' );

    private const TIPOS_VALIDOS = array( 'venta', 'compra', 'intercambio', 'regalo', 'servicio' );

    private const CONDICIONES_VALIDAS = array( 'nuevo', 'como_nuevo', 'buen_estado', 'aceptable', 'para_reparar' );

    public function get_identifier() {
        return 'marketplace';
    }

    public function get_label() {
        return __( 'Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_description() {
        return __( 'Anuncios publicados en el marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_query_fields() {
        return array(
            'estado' => array(
                'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => self::ESTADOS_VALIDOS,
                'default' => 'publicado',
            ),
            'tipo' => array(
                'label'   => __( 'Tipo de anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => array_merge( array( '' ), self::TIPOS_VALIDOS ),
                'default' => '',
            ),
            'condicion' => array(
                'label'   => __( 'Condición', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => array_merge( array( '' ), self::CONDICIONES_VALIDAS ),
                'default' => '',
            ),
            'solo_gratuitos' => array(
                'label'   => __( 'Solo gratuitos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'bool',
                'default' => false,
            ),
            'solo_destacados' => array(
                'label'   => __( 'Solo destacados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'bool',
                'default' => false,
            ),
            'busqueda' => array(
                'label'   => __( 'Búsqueda (título, descripción)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'string',
                'default' => '',
            ),
            'orden' => array(
                'label'   => __( 'Orden', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => array( 'recientes', 'precio_asc', 'precio_desc', 'mas_vistos', 'mas_favoritos' ),
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

        $tabla_anuncios = $wpdb->prefix . 'flavor_marketplace_anuncios';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_anuncios ) ) !== $tabla_anuncios ) {
            return array();
        }

        $where_parts = array( 'estado = %s' );
        $where_args  = array( isset( $query_args['estado'] ) ? (string) $query_args['estado'] : 'publicado' );

        if ( ! empty( $query_args['tipo'] ) ) {
            $where_parts[] = 'tipo = %s';
            $where_args[]  = (string) $query_args['tipo'];
        }

        if ( ! empty( $query_args['condicion'] ) ) {
            $where_parts[] = 'condicion = %s';
            $where_args[]  = (string) $query_args['condicion'];
        }

        if ( ! empty( $query_args['solo_gratuitos'] ) ) {
            $where_parts[] = 'es_gratuito = 1';
        }

        if ( ! empty( $query_args['solo_destacados'] ) ) {
            $where_parts[] = 'es_destacado = 1';
        }

        if ( ! empty( $query_args['busqueda'] ) ) {
            $where_parts[] = '( titulo LIKE %s OR descripcion LIKE %s )';
            $termino_like  = '%' . $wpdb->esc_like( $query_args['busqueda'] ) . '%';
            $where_args[]  = $termino_like;
            $where_args[]  = $termino_like;
        }

        $clausula_where = implode( ' AND ', $where_parts );
        $clausula_order = $this->build_order_clause( isset( $query_args['orden'] ) ? (string) $query_args['orden'] : 'recientes' );
        $limite         = isset( $query_args['limit'] ) ? (int) $query_args['limit'] : 12;
        $limite         = max( 1, min( 50, $limite ) );

        $sql = "SELECT id, titulo, slug, descripcion, tipo, precio, moneda, es_gratuito,
                       condicion, imagen_principal, ubicacion_texto, fecha_publicacion,
                       visualizaciones, favoritos_count
                FROM {$tabla_anuncios}
                WHERE {$clausula_where}
                {$clausula_order}
                LIMIT %d";

        $where_args[] = $limite;

        $filas = $wpdb->get_results( $wpdb->prepare( $sql, $where_args ), ARRAY_A );

        if ( ! is_array( $filas ) ) {
            return array();
        }

        return array_map( array( $this, 'normalize_anuncio_row' ), $filas );
    }

    private function build_order_clause( $opcion_orden ) {
        $mapa = array(
            'recientes'     => 'ORDER BY fecha_publicacion DESC',
            'precio_asc'    => 'ORDER BY CAST(precio AS DECIMAL(10,2)) ASC',
            'precio_desc'   => 'ORDER BY CAST(precio AS DECIMAL(10,2)) DESC',
            'mas_vistos'    => 'ORDER BY visualizaciones DESC',
            'mas_favoritos' => 'ORDER BY favoritos_count DESC',
        );
        return isset( $mapa[ $opcion_orden ] ) ? $mapa[ $opcion_orden ] : $mapa['recientes'];
    }

    private function normalize_anuncio_row( array $fila ) {
        $descripcion_raw = isset( $fila['descripcion'] ) ? (string) $fila['descripcion'] : '';
        $extracto        = wp_trim_words( wp_strip_all_tags( $descripcion_raw ), 24 );

        $es_gratuito = ! empty( $fila['es_gratuito'] );
        $precio_texto = '';
        if ( $es_gratuito ) {
            $precio_texto = __( 'Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN );
        } elseif ( ! empty( $fila['precio'] ) ) {
            $moneda_simbolo = isset( $fila['moneda'] ) && $fila['moneda'] ? (string) $fila['moneda'] : 'EUR';
            $precio_texto   = number_format( (float) $fila['precio'], 2 ) . ' ' . $moneda_simbolo;
        }

        return array(
            'id'      => (int) $fila['id'],
            'title'   => isset( $fila['titulo'] ) ? (string) $fila['titulo'] : '',
            'excerpt' => $extracto,
            'image'   => isset( $fila['imagen_principal'] ) ? (string) $fila['imagen_principal'] : '',
            'url'     => $this->build_anuncio_url( $fila ),
            'date'    => isset( $fila['fecha_publicacion'] ) ? (string) $fila['fecha_publicacion'] : '',
            'meta'    => array(
                'tipo'           => isset( $fila['tipo'] ) ? (string) $fila['tipo'] : '',
                'precio_texto'   => $precio_texto,
                'condicion'      => isset( $fila['condicion'] ) ? (string) $fila['condicion'] : '',
                'ubicacion'      => isset( $fila['ubicacion_texto'] ) ? (string) $fila['ubicacion_texto'] : '',
                'visualizaciones'=> isset( $fila['visualizaciones'] ) ? (int) $fila['visualizaciones'] : 0,
            ),
        );
    }

    private function build_anuncio_url( array $fila ) {
        if ( class_exists( 'Flavor_Platform_Helpers' ) && method_exists( 'Flavor_Platform_Helpers', 'get_action_url' ) ) {
            return (string) Flavor_Platform_Helpers::get_action_url( 'marketplace', 'anuncio' ) . '?anuncio_id=' . (int) $fila['id'];
        }
        return home_url( '?p_anuncio=' . (int) $fila['id'] );
    }
}
