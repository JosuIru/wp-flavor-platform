<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wrapper para la API de Jupiter Aggregator v6 en Solana.
 *
 * Proporciona acceso a cotizaciones, swaps, precios y lista de tokens
 * a traves del agregador Jupiter para intercambios de tokens SPL.
 *
 * @package FlavorChatIA
 */
class Flavor_Dex_Solana_Jupiter_API {

    /**
     * URL base para la API de cotizaciones de Jupiter v6.
     */
    const URL_QUOTE = 'https://quote-api.jup.ag/v6/quote';

    /**
     * URL base para la API de swap de Jupiter v6.
     */
    const URL_SWAP = 'https://quote-api.jup.ag/v6/swap';

    /**
     * URL base para la API de precios de Jupiter v6.
     */
    const URL_PRICE = 'https://price.jup.ag/v6/price';

    /**
     * URL para la lista estricta de tokens de Jupiter.
     */
    const URL_TOKEN_LIST = 'https://token.jup.ag/strict';

    /**
     * Prefijo para las claves de transient en cache.
     */
    const TRANSIENT_PREFIX = 'flavor_dex_jupiter_';

    /**
     * Duracion del cache de cotizaciones y precios en segundos.
     */
    const CACHE_DURACION_CORTA = 30;

    /**
     * Duracion del cache de la lista de tokens en segundos (1 hora).
     */
    const CACHE_DURACION_TOKENS = 3600;

    /**
     * Tiempo maximo de espera para llamadas HTTP en segundos.
     */
    const TIMEOUT_SEGUNDOS = 15;

    /**
     * Obtiene una cotizacion de swap desde Jupiter Aggregator.
     *
     * Consulta la API de Jupiter para obtener la mejor ruta de intercambio
     * entre dos tokens, incluyendo impacto de precio y plan de ruta.
     *
     * @param string $mint_entrada       Direccion mint del token de entrada.
     * @param string $mint_salida        Direccion mint del token de salida.
     * @param int    $cantidad_base_units Cantidad en unidades base (lamports, etc.).
     * @param int    $slippage_bps       Slippage permitido en puntos basicos (por defecto 50 = 0.5%).
     * @return array Datos de la cotizacion o array de error.
     */
    public function obtener_cotizacion($mint_entrada, $mint_salida, $cantidad_base_units, $slippage_bps = 50) {
        $clave_cache = self::TRANSIENT_PREFIX . 'quote_' . md5($mint_entrada . $mint_salida . $cantidad_base_units . $slippage_bps);
        $datos_en_cache = get_transient($clave_cache);

        if (false !== $datos_en_cache) {
            return $datos_en_cache;
        }

        $url_cotizacion = add_query_arg(
            array(
                'inputMint'   => $mint_entrada,
                'outputMint'  => $mint_salida,
                'amount'      => $cantidad_base_units,
                'slippageBps' => $slippage_bps,
            ),
            self::URL_QUOTE
        );

        $respuesta_http = wp_remote_get($url_cotizacion, array(
            'timeout' => self::TIMEOUT_SEGUNDOS,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));

        if (is_wp_error($respuesta_http)) {
            $mensaje_error = $respuesta_http->get_error_message();
            flavor_chat_ia_log('Error Jupiter Quote API: ' . $mensaje_error, 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Error al conectar con Jupiter Quote API: ' . $mensaje_error,
            );
        }

        $codigo_respuesta = wp_remote_retrieve_response_code($respuesta_http);

        if (200 !== $codigo_respuesta) {
            flavor_chat_ia_log('Error Jupiter Quote HTTP ' . $codigo_respuesta, 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Jupiter Quote API respondio con codigo HTTP: ' . $codigo_respuesta,
            );
        }

        $cuerpo_respuesta = wp_remote_retrieve_body($respuesta_http);
        $datos_cotizacion = json_decode($cuerpo_respuesta, true);

        if (!is_array($datos_cotizacion)) {
            flavor_chat_ia_log('Error al decodificar respuesta de Jupiter Quote API', 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Respuesta invalida de Jupiter Quote API',
            );
        }

        if (isset($datos_cotizacion['error'])) {
            flavor_chat_ia_log('Jupiter Quote API error: ' . $datos_cotizacion['error'], 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Jupiter Quote API: ' . $datos_cotizacion['error'],
            );
        }

        $resultado_cotizacion = array(
            'inputMint'            => isset($datos_cotizacion['inputMint']) ? $datos_cotizacion['inputMint'] : '',
            'outputMint'           => isset($datos_cotizacion['outputMint']) ? $datos_cotizacion['outputMint'] : '',
            'inAmount'             => isset($datos_cotizacion['inAmount']) ? $datos_cotizacion['inAmount'] : '0',
            'outAmount'            => isset($datos_cotizacion['outAmount']) ? $datos_cotizacion['outAmount'] : '0',
            'priceImpactPct'       => isset($datos_cotizacion['priceImpactPct']) ? floatval($datos_cotizacion['priceImpactPct']) : 0,
            'routePlan'            => isset($datos_cotizacion['routePlan']) ? $datos_cotizacion['routePlan'] : array(),
            'otherAmountThreshold' => isset($datos_cotizacion['otherAmountThreshold']) ? $datos_cotizacion['otherAmountThreshold'] : '0',
        );

        set_transient($clave_cache, $resultado_cotizacion, self::CACHE_DURACION_CORTA);

        return $resultado_cotizacion;
    }

    /**
     * Prepara una transaccion de swap serializada desde Jupiter.
     *
     * Envia la cotizacion obtenida y la clave publica del usuario para
     * recibir una transaccion serializada en base64 lista para firmar.
     *
     * @param array  $quote_response Respuesta de cotizacion de Jupiter (de obtener_cotizacion).
     * @param string $wallet_publica Direccion publica de la wallet del usuario.
     * @return array Transaccion serializada o array de error.
     */
    public function preparar_transaccion_swap($quote_response, $wallet_publica) {
        $cuerpo_solicitud = wp_json_encode(array(
            'quoteResponse'  => $quote_response,
            'userPublicKey'  => $wallet_publica,
            'wrapAndUnwrapSol' => true,
        ));

        $respuesta_http = wp_remote_post(self::URL_SWAP, array(
            'timeout' => self::TIMEOUT_SEGUNDOS,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body'    => $cuerpo_solicitud,
        ));

        if (is_wp_error($respuesta_http)) {
            $mensaje_error = $respuesta_http->get_error_message();
            flavor_chat_ia_log('Error Jupiter Swap API: ' . $mensaje_error, 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Error al conectar con Jupiter Swap API: ' . $mensaje_error,
            );
        }

        $codigo_respuesta = wp_remote_retrieve_response_code($respuesta_http);

        if (200 !== $codigo_respuesta) {
            flavor_chat_ia_log('Error Jupiter Swap HTTP ' . $codigo_respuesta, 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Jupiter Swap API respondio con codigo HTTP: ' . $codigo_respuesta,
            );
        }

        $cuerpo_respuesta = wp_remote_retrieve_body($respuesta_http);
        $datos_swap = json_decode($cuerpo_respuesta, true);

        if (!is_array($datos_swap)) {
            flavor_chat_ia_log('Error al decodificar respuesta de Jupiter Swap API', 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Respuesta invalida de Jupiter Swap API',
            );
        }

        if (isset($datos_swap['error'])) {
            flavor_chat_ia_log('Jupiter Swap API error: ' . $datos_swap['error'], 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Jupiter Swap API: ' . $datos_swap['error'],
            );
        }

        $transaccion_serializada = isset($datos_swap['swapTransaction']) ? $datos_swap['swapTransaction'] : null;
        $ultimo_bloque_valido    = isset($datos_swap['lastValidBlockHeight']) ? $datos_swap['lastValidBlockHeight'] : 0;

        if (empty($transaccion_serializada)) {
            flavor_chat_ia_log('Jupiter Swap API no devolvio transaccion serializada', 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Jupiter no devolvio una transaccion de swap valida',
            );
        }

        return array(
            'swapTransaction'      => $transaccion_serializada,
            'lastValidBlockHeight' => $ultimo_bloque_valido,
        );
    }

    /**
     * Obtiene precios en USD para multiples tokens por direccion mint.
     *
     * Consulta la API de precios de Jupiter y devuelve un array asociativo
     * con la direccion mint como clave y el precio en USD como valor.
     *
     * @param array $mints_array Lista de direcciones mint de tokens.
     * @return array Precios indexados por direccion mint [mint => precio_usd] o array de error.
     */
    public function obtener_precios($mints_array) {
        if (empty($mints_array) || !is_array($mints_array)) {
            return array(
                'success' => false,
                'error'   => 'Se requiere un array de direcciones mint',
            );
        }

        $mints_ordenados = $mints_array;
        sort($mints_ordenados);
        $clave_cache = self::TRANSIENT_PREFIX . 'prices_' . md5(implode(',', $mints_ordenados));
        $datos_en_cache = get_transient($clave_cache);

        if (false !== $datos_en_cache) {
            return $datos_en_cache;
        }

        $mints_concatenados = implode(',', $mints_array);

        $url_precios = add_query_arg(
            array(
                'ids' => $mints_concatenados,
            ),
            self::URL_PRICE
        );

        $respuesta_http = wp_remote_get($url_precios, array(
            'timeout' => self::TIMEOUT_SEGUNDOS,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));

        if (is_wp_error($respuesta_http)) {
            $mensaje_error = $respuesta_http->get_error_message();
            flavor_chat_ia_log('Error Jupiter Price API: ' . $mensaje_error, 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Error al conectar con Jupiter Price API: ' . $mensaje_error,
            );
        }

        $codigo_respuesta = wp_remote_retrieve_response_code($respuesta_http);

        if (200 !== $codigo_respuesta) {
            flavor_chat_ia_log('Error Jupiter Price HTTP ' . $codigo_respuesta, 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Jupiter Price API respondio con codigo HTTP: ' . $codigo_respuesta,
            );
        }

        $cuerpo_respuesta = wp_remote_retrieve_body($respuesta_http);
        $datos_precios = json_decode($cuerpo_respuesta, true);

        if (!is_array($datos_precios) || !isset($datos_precios['data'])) {
            flavor_chat_ia_log('Error al decodificar respuesta de Jupiter Price API', 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Respuesta invalida de Jupiter Price API',
            );
        }

        $precios_por_mint = array();

        foreach ($datos_precios['data'] as $direccion_mint => $datos_token) {
            $precio_usd = isset($datos_token['price']) ? floatval($datos_token['price']) : 0;
            $precios_por_mint[$direccion_mint] = $precio_usd;
        }

        set_transient($clave_cache, $precios_por_mint, self::CACHE_DURACION_CORTA);

        return $precios_por_mint;
    }

    /**
     * Obtiene la lista estricta de tokens verificados de Jupiter.
     *
     * Descarga la lista completa de tokens verificados y la almacena
     * en cache por 1 hora para evitar llamadas excesivas.
     *
     * @return array Lista de objetos de token o array de error.
     */
    public function obtener_lista_tokens() {
        $clave_cache = self::TRANSIENT_PREFIX . 'token_list';
        $datos_en_cache = get_transient($clave_cache);

        if (false !== $datos_en_cache) {
            return $datos_en_cache;
        }

        $respuesta_http = wp_remote_get(self::URL_TOKEN_LIST, array(
            'timeout' => self::TIMEOUT_SEGUNDOS,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));

        if (is_wp_error($respuesta_http)) {
            $mensaje_error = $respuesta_http->get_error_message();
            flavor_chat_ia_log('Error Jupiter Token List API: ' . $mensaje_error, 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Error al conectar con Jupiter Token List: ' . $mensaje_error,
            );
        }

        $codigo_respuesta = wp_remote_retrieve_response_code($respuesta_http);

        if (200 !== $codigo_respuesta) {
            flavor_chat_ia_log('Error Jupiter Token List HTTP ' . $codigo_respuesta, 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Jupiter Token List respondio con codigo HTTP: ' . $codigo_respuesta,
            );
        }

        $cuerpo_respuesta = wp_remote_retrieve_body($respuesta_http);
        $lista_tokens = json_decode($cuerpo_respuesta, true);

        if (!is_array($lista_tokens)) {
            flavor_chat_ia_log('Error al decodificar respuesta de Jupiter Token List', 'dex_solana');
            return array(
                'success' => false,
                'error'   => 'Respuesta invalida de Jupiter Token List',
            );
        }

        set_transient($clave_cache, $lista_tokens, self::CACHE_DURACION_TOKENS);

        return $lista_tokens;
    }

    /**
     * Busca tokens por simbolo o nombre en la lista de tokens de Jupiter.
     *
     * Realiza una busqueda insensible a mayusculas/minusculas en la lista
     * cacheada de tokens, buscando coincidencias parciales en simbolo y nombre.
     *
     * @param string $busqueda Texto de busqueda (simbolo o nombre del token).
     * @return array Lista de tokens coincidentes (maximo 10 resultados).
     */
    public function buscar_token($busqueda) {
        if (empty($busqueda) || !is_string($busqueda)) {
            return array(
                'success' => false,
                'error'   => 'Se requiere un texto de busqueda',
            );
        }

        $lista_completa_tokens = $this->obtener_lista_tokens();

        if (!is_array($lista_completa_tokens) || isset($lista_completa_tokens['error'])) {
            return array(
                'success' => false,
                'error'   => 'No se pudo obtener la lista de tokens de Jupiter',
            );
        }

        $busqueda_minusculas = strtolower(trim($busqueda));
        $tokens_encontrados = array();
        $maximo_resultados = 10;

        foreach ($lista_completa_tokens as $token_actual) {
            if (count($tokens_encontrados) >= $maximo_resultados) {
                break;
            }

            $simbolo_token = isset($token_actual['symbol']) ? strtolower($token_actual['symbol']) : '';
            $nombre_token  = isset($token_actual['name']) ? strtolower($token_actual['name']) : '';

            $coincide_simbolo = (false !== strpos($simbolo_token, $busqueda_minusculas));
            $coincide_nombre  = (false !== strpos($nombre_token, $busqueda_minusculas));

            if ($coincide_simbolo || $coincide_nombre) {
                $tokens_encontrados[] = $token_actual;
            }
        }

        return $tokens_encontrados;
    }
}
