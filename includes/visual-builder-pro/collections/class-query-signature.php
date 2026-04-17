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
 * La firma excluye el campo 'page' (ese sí puede cambiar entre peticiones,
 * es lo que permite paginar). Usa wp_salt() como clave, así cada instalación
 * firma con su secret y no hay expiración (a diferencia de wp_create_nonce,
 * que caduca a las 24h y rompería sitios con cache largo).
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
     * Acción que se usa junto a wp_salt() para evitar reutilizar firmas
     * cruzadas entre features.
     */
    private const SIGNATURE_ACTION = 'vbp_collection_query_v1';

    /**
     * Genera la firma para un par (source, args).
     *
     * @param string               $source_identifier Identificador de la fuente.
     * @param array<string, mixed> $query_args        Args saneados, sin 'page'.
     * @return string Hash hex.
     */
    public static function sign( $source_identifier, array $query_args ) {
        $payload = self::canonical_payload( $source_identifier, $query_args );
        return hash_hmac( 'sha256', $payload, self::get_secret_key() );
    }

    /**
     * Verifica que la firma corresponde al par (source, args). Usa
     * hash_equals para evitar ataques de timing.
     *
     * @param string               $source_identifier
     * @param array<string, mixed> $query_args
     * @param string               $firma_a_verificar
     * @return bool
     */
    public static function verify( $source_identifier, array $query_args, $firma_a_verificar ) {
        if ( ! is_string( $firma_a_verificar ) || $firma_a_verificar === '' ) {
            return false;
        }
        $firma_esperada = self::sign( $source_identifier, $query_args );
        return hash_equals( $firma_esperada, $firma_a_verificar );
    }

    /**
     * Serializa source+args de forma estable (ordena claves) para que
     * firmante y verificador generen el mismo payload aunque los args
     * vengan en otro orden.
     *
     * @param string               $source_identifier
     * @param array<string, mixed> $query_args
     * @return string
     */
    private static function canonical_payload( $source_identifier, array $query_args ) {
        // Excluir 'page' del payload: la firma es estable entre páginas.
        unset( $query_args['page'] );

        // Ordenar claves para que {a:1,b:2} y {b:2,a:1} firmen igual.
        ksort( $query_args );

        return self::SIGNATURE_ACTION . '|' . $source_identifier . '|' . wp_json_encode( $query_args );
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
