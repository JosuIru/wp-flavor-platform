<?php
/**
 * Trait reutilizable para las Collections con soporte de paginación.
 *
 * Factoriza el patrón común SELECT ... WHERE ... ORDER BY ... LIMIT OFFSET
 * en todas las fuentes, y añade get_total_count() que ejecuta el mismo
 * filtro pero sólo cuenta. Evita que cada source duplique la construcción
 * del WHERE.
 *
 * Los sources que usan este trait deben implementar dos métodos protegidos:
 *   - get_table_name(): string con prefijo incluido.
 *   - build_where_and_args(array $query_args): [string, array] con la
 *     cláusula WHERE (sin el keyword WHERE) y los argumentos que pasarán
 *     por wpdb->prepare.
 *
 * Y opcionalmente:
 *   - build_order_clause(string $opcion): string con ORDER BY completo.
 *   - get_default_limit(): int.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait Flavor_VBP_Paginated_Collection_Trait {

    /**
     * Campos de paginación que el source puede mezclar con los suyos en
     * get_query_fields(). Así page queda expuesto en el inspector sin que
     * cada source lo declare manualmente.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function get_pagination_fields() {
        return array(
            'page' => array(
                'label'   => __( 'Página', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'int',
                'min'     => 1,
                'max'     => 1000,
                'default' => 1,
            ),
            'limit' => array(
                'label'   => __( 'Máximo por página', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'type'    => 'int',
                'min'     => 1,
                'max'     => 50,
                'default' => $this->get_default_limit(),
            ),
        );
    }

    /**
     * Normaliza los valores de paginación que vienen en $query_args al
     * triplete {page, limit, offset} listo para SQL.
     *
     * @param array<string, mixed> $query_args
     * @return array{page: int, limit: int, offset: int}
     */
    protected function extract_pagination( array $query_args ) {
        $pagina = isset( $query_args['page'] ) ? (int) $query_args['page'] : 1;
        if ( $pagina < 1 ) {
            $pagina = 1;
        }
        if ( $pagina > 1000 ) {
            $pagina = 1000;
        }

        $limite_default = $this->get_default_limit();
        $limite = isset( $query_args['limit'] ) ? (int) $query_args['limit'] : $limite_default;
        if ( $limite < 1 ) {
            $limite = 1;
        }
        if ( $limite > 50 ) {
            $limite = 50;
        }

        return array(
            'page'   => $pagina,
            'limit'  => $limite,
            'offset' => ( $pagina - 1 ) * $limite,
        );
    }

    /**
     * Valor por defecto del limit si el source no lo sobreescribe.
     *
     * @return int
     */
    protected function get_default_limit() {
        return 10;
    }

    /**
     * Implementa Flavor_VBP_Collection_Source::get_total_count reutilizando
     * el WHERE que declara cada source concreto. Si la tabla no existe,
     * devuelve 0 (coincide con lo que query() devuelve en ese caso).
     *
     * @param array<string, mixed> $query_args
     * @return int
     */
    public function get_total_count( array $query_args ) {
        global $wpdb;

        $nombre_tabla = $this->get_table_name();
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $nombre_tabla ) ) !== $nombre_tabla ) {
            return 0;
        }

        list( $clausula_where, $args_where ) = $this->build_where_and_args( $query_args );

        $sql_count = "SELECT COUNT(*) FROM {$nombre_tabla} WHERE {$clausula_where}";

        if ( ! empty( $args_where ) ) {
            $sql_count = $wpdb->prepare( $sql_count, $args_where );
        }

        return (int) $wpdb->get_var( $sql_count );
    }
}
