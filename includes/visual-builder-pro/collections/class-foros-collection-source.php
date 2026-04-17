<?php
/**
 * Fuente de colección para el módulo de foros (hilos / threads).
 *
 * Expone los hilos de los foros con filtros por estado, foro específico,
 * hilos destacados y orden por actividad reciente / creación / respuestas.
 * Aprovecha el índice compuesto idx_estado_fecha de otros módulos si
 * existe; si no, cae a consulta sin cover.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Foros_Collection_Source implements Flavor_VBP_Collection_Source {

    private const ESTADOS_VALIDOS = array( 'abierto', 'cerrado', 'archivado', 'reportado' );

    public function get_identifier() {
        return 'foros_hilos';
    }

    public function get_label() {
        return __( 'Hilos de foros', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_description() {
        return __( 'Conversaciones abiertas en los foros de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN );
    }

    public function get_query_fields() {
        return array(
            'estado' => array(
                'label'   => __( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => self::ESTADOS_VALIDOS,
                'default' => 'abierto',
            ),
            'foro_id' => array(
                'label'   => __( 'Foro específico (ID, 0 = todos)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'int',
                'min'     => 0,
                'max'     => 999999,
                'default' => 0,
            ),
            'solo_destacados' => array(
                'label'   => __( 'Solo destacados', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'bool',
                'default' => false,
            ),
            'orden' => array(
                'label'   => __( 'Orden', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'enum',
                'options' => array( 'actividad', 'recientes', 'mas_respondidos', 'mas_vistos' ),
                'default' => 'actividad',
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

        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_hilos ) ) !== $tabla_hilos ) {
            return array();
        }

        $where_parts = array( 'estado = %s' );
        $where_args  = array( isset( $query_args['estado'] ) ? (string) $query_args['estado'] : 'abierto' );

        $foro_id_filtro = isset( $query_args['foro_id'] ) ? (int) $query_args['foro_id'] : 0;
        if ( $foro_id_filtro > 0 ) {
            $where_parts[] = 'foro_id = %d';
            $where_args[]  = $foro_id_filtro;
        }

        if ( ! empty( $query_args['solo_destacados'] ) ) {
            $where_parts[] = 'es_destacado = 1';
        }

        $clausula_where = implode( ' AND ', $where_parts );
        $clausula_order = $this->build_order_clause( isset( $query_args['orden'] ) ? (string) $query_args['orden'] : 'actividad' );
        $limite         = isset( $query_args['limit'] ) ? (int) $query_args['limit'] : 10;
        $limite         = max( 1, min( 50, $limite ) );

        $sql = "SELECT id, foro_id, autor_id, titulo, contenido, vistas,
                       respuestas_count, ultima_actividad, created_at, slug
                FROM {$tabla_hilos}
                WHERE {$clausula_where}
                {$clausula_order}
                LIMIT %d";

        $where_args[] = $limite;

        $filas = $wpdb->get_results( $wpdb->prepare( $sql, $where_args ), ARRAY_A );

        if ( ! is_array( $filas ) ) {
            return array();
        }

        return array_map( array( $this, 'normalize_hilo_row' ), $filas );
    }

    private function build_order_clause( $opcion_orden ) {
        $mapa = array(
            'actividad'       => 'ORDER BY ultima_actividad DESC',
            'recientes'       => 'ORDER BY created_at DESC',
            'mas_respondidos' => 'ORDER BY respuestas_count DESC',
            'mas_vistos'      => 'ORDER BY vistas DESC',
        );
        return isset( $mapa[ $opcion_orden ] ) ? $mapa[ $opcion_orden ] : $mapa['actividad'];
    }

    private function normalize_hilo_row( array $fila ) {
        $id_autor        = isset( $fila['autor_id'] ) ? (int) $fila['autor_id'] : 0;
        $avatar_url      = '';
        if ( $id_autor > 0 ) {
            $avatar_url = get_avatar_url( $id_autor, array( 'size' => 200 ) );
        }

        $extracto_hilo = '';
        if ( ! empty( $fila['contenido'] ) ) {
            $extracto_hilo = wp_trim_words( wp_strip_all_tags( (string) $fila['contenido'] ), 24 );
        }

        return array(
            'id'      => (int) $fila['id'],
            'title'   => isset( $fila['titulo'] ) ? (string) $fila['titulo'] : '',
            'excerpt' => $extracto_hilo,
            'image'   => (string) $avatar_url,
            'url'     => $this->build_hilo_url( $fila ),
            'date'    => isset( $fila['ultima_actividad'] ) ? (string) $fila['ultima_actividad'] : '',
            'meta'    => array(
                'foro_id'          => isset( $fila['foro_id'] ) ? (int) $fila['foro_id'] : 0,
                'vistas'           => isset( $fila['vistas'] ) ? (int) $fila['vistas'] : 0,
                'respuestas_count' => isset( $fila['respuestas_count'] ) ? (int) $fila['respuestas_count'] : 0,
                'created_at'       => isset( $fila['created_at'] ) ? (string) $fila['created_at'] : '',
            ),
        );
    }

    private function build_hilo_url( array $fila ) {
        if ( class_exists( 'Flavor_Platform_Helpers' ) && method_exists( 'Flavor_Platform_Helpers', 'get_action_url' ) ) {
            return (string) Flavor_Platform_Helpers::get_action_url( 'foros', 'hilo' ) . '?hilo_id=' . (int) $fila['id'];
        }
        return home_url( '?p_hilo=' . (int) $fila['id'] );
    }
}
