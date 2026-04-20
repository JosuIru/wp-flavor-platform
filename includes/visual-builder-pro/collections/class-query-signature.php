<?php
/**
 * Firma HMAC sobre {source, args} de una query de Collections.
 *
 * La usan los bloques Lista Dinámica para permitir que el frontend pida
 * la siguiente página sin autenticación. El servidor firma source+args
 * en tiempo de render, el HTML lleva la firma en data-signature, y el
 * endpoint público verifica que la combinación source+args no se ha
 * manipulado antes de ejecutar la consulta.
 *
 * Hay dos esquemas:
 *
 * v1 (retro): firma sobre action | source | json(args_sin_page). Blocks
 *     guardados antes del feature de filtros visibles al visitante usan
 *     este formato; siguen funcionando.
 *
 * v2 (con filtros públicos): firma sobre action_v2 | source |
 *     json(fixed_args_sin_page_ni_public) | json(sorted(public_filter_names)).
 *     Permite que el visitante cambie los valores de los campos cuyos
 *     nombres están en public_filter_names sin invalidar la firma,
 *     porque los valores no son parte de la firma — solo los nombres.
 *     Los campos fuera de esa lista siguen fijos y cualquier cambio
 *     rompe la firma (protección contra manipulación de filtros no
 *     expuestos, ej: borrador → publicado).
 *
 * La firma excluye siempre el campo 'page' (ese sí puede cambiar entre
 * peticiones, es lo que permite paginar). Usa wp_salt() como clave, así
 * cada instalación firma con su secret y no hay expiración.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Query_Signature {

    /**
     * Acción para el esquema v1 (sin filtros públicos).
     */
    private const SIGNATURE_ACTION_V1 = 'vbp_collection_query_v1';

    /**
     * Acción para el esquema v2 (con filtros públicos editables).
     */
    private const SIGNATURE_ACTION_V2 = 'vbp_collection_query_v2';

    /**
     * Genera la firma. Si $public_filter_names es un array no vacío, usa
     * el esquema v2; si es null o array vacío, usa v1 (backward compat).
     *
     * @param string               $source_identifier
     * @param array<string, mixed> $query_args
     * @param array<int, string>|null $public_filter_names  Lista de filter
     *   identifiers que el visitante puede editar (se excluyen de la firma).
     *   Si es null o [], se usa el esquema v1 legacy.
     * @return string Hash hex.
     */
    public static function sign( $source_identifier, array $query_args, $public_filter_names = null ) {
        $payload = self::canonical_payload( $source_identifier, $query_args, $public_filter_names );
        return hash_hmac( 'sha256', $payload, self::get_secret_key() );
    }

    /**
     * Verifica firma. Los parámetros $query_args y $public_filter_names
     * tienen que ser los mismos con los que se firmó originalmente; el
     * caller es responsable de extraerlos del body de la request.
     *
     * @param string               $source_identifier
     * @param array<string, mixed> $query_args
     * @param string               $firma_a_verificar
     * @param array<int, string>|null $public_filter_names
     * @return bool
     */
    public static function verify( $source_identifier, array $query_args, $firma_a_verificar, $public_filter_names = null ) {
        if ( ! is_string( $firma_a_verificar ) || $firma_a_verificar === '' ) {
            return false;
        }
        $firma_esperada = self::sign( $source_identifier, $query_args, $public_filter_names );
        return hash_equals( $firma_esperada, $firma_a_verificar );
    }

    /**
     * Serializa {source, args} de forma estable. Si hay public_filter_names
     * se usa el esquema v2 que los añade al payload (ordenados y como
     * array JSON separado del args).
     *
     * @param string               $source_identifier
     * @param array<string, mixed> $query_args
     * @param array<int, string>|null $public_filter_names
     * @return string
     */
    private static function canonical_payload( $source_identifier, array $query_args, $public_filter_names = null ) {
        unset( $query_args['page'] );
        ksort( $query_args );

        $lista_public_filter_names = is_array( $public_filter_names ) ? array_values( $public_filter_names ) : array();

        if ( ! empty( $lista_public_filter_names ) ) {
            sort( $lista_public_filter_names );
            return self::SIGNATURE_ACTION_V2 . '|' . $source_identifier
                . '|' . wp_json_encode( $query_args )
                . '|' . wp_json_encode( $lista_public_filter_names );
        }

        return self::SIGNATURE_ACTION_V1 . '|' . $source_identifier . '|' . wp_json_encode( $query_args );
    }

    /**
     * Obtiene el secreto de HMAC. En runtime usa wp_salt. En tests
     * (donde wp_salt no existe) cae a un placeholder determinista para
     * que sign/verify sean consistentes dentro del test.
     *
     * @return string
     */
    private static function get_secret_key() {
        if ( function_exists( 'wp_salt' ) ) {
            return wp_salt( 'auth' );
        }
        return 'flavor-vbp-test-secret';
    }
}
