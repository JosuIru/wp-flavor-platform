<?php
/**
 * Fuente de colección para el módulo de biblioteca.
 *
 * Expone los libros del catálogo con filtros por estado, género y
 * búsqueda textual sobre título/autor/ISBN. Los items devuelven portada,
 * título, autor y año para que las tarjetas del canvas queden completas.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Biblioteca_Collection_Source implements Flavor_VBP_Collection_Source {

    /**
     * Estados del libro respecto al préstamo / disponibilidad.
     */
    private const ESTADOS_VALIDOS = array( 'disponible', 'prestado', 'reservado', 'perdido', 'retirado' );

    public function get_identifier() {
        return 'biblioteca';
    }

    public function get_label() {
        return __( 'Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_description() {
        return __( 'Libros del catálogo de la biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_query_fields() {
        return array(
            'estado' => array(
                'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => self::ESTADOS_VALIDOS,
                'default' => 'disponible',
            ),
            'genero' => array(
                'label'   => __( 'Género exacto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'string',
                'default' => '',
            ),
            'busqueda' => array(
                'label'   => __( 'Búsqueda (título, autor, ISBN)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'string',
                'default' => '',
            ),
            'orden' => array(
                'label'   => __( 'Orden', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => array( 'recientes', 'titulo', 'mas_leidos', 'mejor_valorados' ),
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

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_libros ) ) !== $tabla_libros ) {
            return array();
        }

        $where_parts = array( 'estado = %s' );
        $where_args  = array( isset( $query_args['estado'] ) ? (string) $query_args['estado'] : 'disponible' );

        if ( ! empty( $query_args['genero'] ) ) {
            $where_parts[] = 'genero = %s';
            $where_args[]  = (string) $query_args['genero'];
        }

        if ( ! empty( $query_args['busqueda'] ) ) {
            $where_parts[] = '( titulo LIKE %s OR autor LIKE %s OR isbn LIKE %s )';
            $termino_like  = '%' . $wpdb->esc_like( $query_args['busqueda'] ) . '%';
            $where_args[]  = $termino_like;
            $where_args[]  = $termino_like;
            $where_args[]  = $termino_like;
        }

        $clausula_where = implode( ' AND ', $where_parts );
        $clausula_order = $this->build_order_clause( isset( $query_args['orden'] ) ? (string) $query_args['orden'] : 'recientes' );
        $limite         = isset( $query_args['limit'] ) ? (int) $query_args['limit'] : 12;
        $limite         = max( 1, min( 50, $limite ) );

        $sql = "SELECT id, titulo, autor, descripcion, portada_url, imagen_portada,
                       genero, ano_publicacion, fecha_registro, puntuacion_promedio
                FROM {$tabla_libros}
                WHERE {$clausula_where}
                {$clausula_order}
                LIMIT %d";

        $where_args[] = $limite;

        $filas = $wpdb->get_results( $wpdb->prepare( $sql, $where_args ), ARRAY_A );

        if ( ! is_array( $filas ) ) {
            return array();
        }

        return array_map( array( $this, 'normalize_libro_row' ), $filas );
    }

    private function build_order_clause( $opcion_orden ) {
        $mapa = array(
            'recientes'       => 'ORDER BY fecha_registro DESC',
            'titulo'          => 'ORDER BY titulo ASC',
            'mas_leidos'      => 'ORDER BY total_prestamos DESC',
            'mejor_valorados' => 'ORDER BY puntuacion_promedio DESC',
        );
        return isset( $mapa[ $opcion_orden ] ) ? $mapa[ $opcion_orden ] : $mapa['recientes'];
    }

    private function normalize_libro_row( array $fila ) {
        $imagen_portada = '';
        if ( ! empty( $fila['portada_url'] ) ) {
            $imagen_portada = (string) $fila['portada_url'];
        } elseif ( ! empty( $fila['imagen_portada'] ) ) {
            $imagen_portada = (string) $fila['imagen_portada'];
        }

        $titulo_libro = isset( $fila['titulo'] ) ? (string) $fila['titulo'] : '';
        $autor_libro  = isset( $fila['autor'] ) ? (string) $fila['autor'] : '';

        $extracto_libro = $autor_libro;
        if ( ! empty( $fila['descripcion'] ) ) {
            $extracto_libro = wp_trim_words( wp_strip_all_tags( (string) $fila['descripcion'] ), 24 );
        }

        return array(
            'id'      => (int) $fila['id'],
            'title'   => $titulo_libro,
            'excerpt' => $extracto_libro,
            'image'   => $imagen_portada,
            'url'     => $this->build_libro_url( $fila ),
            'date'    => isset( $fila['fecha_registro'] ) ? (string) $fila['fecha_registro'] : '',
            'meta'    => array(
                'autor'               => $autor_libro,
                'genero'              => isset( $fila['genero'] ) ? (string) $fila['genero'] : '',
                'ano_publicacion'     => isset( $fila['ano_publicacion'] ) ? (int) $fila['ano_publicacion'] : 0,
                'puntuacion_promedio' => isset( $fila['puntuacion_promedio'] ) ? (float) $fila['puntuacion_promedio'] : 0.0,
            ),
        );
    }

    private function build_libro_url( array $fila ) {
        if ( class_exists( 'Flavor_Platform_Helpers' ) && method_exists( 'Flavor_Platform_Helpers', 'get_action_url' ) ) {
            return (string) Flavor_Platform_Helpers::get_action_url( 'biblioteca', 'detalle' ) . '?libro_id=' . (int) $fila['id'];
        }
        return home_url( '?p_libro=' . (int) $fila['id'] );
    }
}
