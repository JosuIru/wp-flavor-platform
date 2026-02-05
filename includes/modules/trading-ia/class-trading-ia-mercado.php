<?php
/**
 * Datos de mercado - Obtiene precios en tiempo real via CoinGecko
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trading_IA_Mercado {

    /**
     * Duracion del cache en segundos
     */
    const CACHE_DURACION = 30;

    /**
     * Clave base para transients
     */
    const TRANSIENT_PREFIX = 'flavor_trading_ia_mercado_';

    /**
     * Mapeo de simbolo a ID de CoinGecko
     */
    private $coingecko_ids = array(
        'SOL'  => 'solana',
        'BTC'  => 'bitcoin',
        'ETH'  => 'ethereum',
        'USDC' => 'usd-coin',
        'USDT' => 'tether',
        'BONK' => 'bonk',
        'JUP'  => 'jupiter-exchange-solana',
        'JTO'  => 'jito-governance-token',
        'RAY'  => 'raydium',
        'ORCA' => 'orca',
        'PYTH' => 'pyth-network',
        'WIF'  => 'dogwifcoin',
    );

    /**
     * Obtiene el precio de un solo token
     *
     * @param string $simbolo Simbolo del token (SOL, BONK, etc.)
     * @return array|null Datos del mercado o null si falla
     */
    public function obtener_precio($simbolo) {
        $datos = $this->obtener_precios_multiples(array($simbolo));
        return isset($datos[$simbolo]) ? $datos[$simbolo] : null;
    }

    /**
     * Obtiene precios de multiples tokens en una sola llamada
     *
     * @param array $simbolos Lista de simbolos
     * @return array Datos de mercado indexados por simbolo
     */
    public function obtener_precios_multiples($simbolos) {
        $clave_cache = self::TRANSIENT_PREFIX . md5(implode(',', $simbolos));
        $datos_cache = get_transient($clave_cache);

        if (false !== $datos_cache) {
            return $datos_cache;
        }

        $datos_frescos = $this->consultar_coingecko($simbolos);

        if (!empty($datos_frescos)) {
            set_transient($clave_cache, $datos_frescos, self::CACHE_DURACION);
        }

        return $datos_frescos;
    }

    /**
     * Consulta la API de CoinGecko
     *
     * @param array $simbolos Lista de simbolos
     * @return array Datos procesados
     */
    private function consultar_coingecko($simbolos) {
        $ids_coingecko = array();
        foreach ($simbolos as $simbolo) {
            $id_coingecko = isset($this->coingecko_ids[$simbolo])
                ? $this->coingecko_ids[$simbolo]
                : strtolower($simbolo);
            $ids_coingecko[] = $id_coingecko;
        }

        $url = add_query_arg(
            array(
                'ids'                 => implode(',', $ids_coingecko),
                'vs_currencies'       => 'usd',
                'include_24hr_change' => 'true',
                'include_7d_change'   => 'true',
                'include_24hr_vol'    => 'true',
                'include_market_cap'  => 'true',
                'include_1h_change'   => 'true',
            ),
            'https://api.coingecko.com/api/v3/simple/price'
        );

        $respuesta = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));

        if (is_wp_error($respuesta)) {
            flavor_chat_ia_log('Error CoinGecko: ' . $respuesta->get_error_message(), 'trading_ia');
            return array();
        }

        $codigo_respuesta = wp_remote_retrieve_response_code($respuesta);

        if (429 === $codigo_respuesta) {
            flavor_chat_ia_log('Rate limit de CoinGecko alcanzado', 'trading_ia');
            return array();
        }

        if (200 !== $codigo_respuesta) {
            flavor_chat_ia_log('Error CoinGecko HTTP: ' . $codigo_respuesta, 'trading_ia');
            return array();
        }

        $cuerpo = wp_remote_retrieve_body($respuesta);
        $datos_json = json_decode($cuerpo, true);

        if (!is_array($datos_json)) {
            return array();
        }

        return $this->procesar_respuesta_coingecko($datos_json, $simbolos);
    }

    /**
     * Procesa la respuesta de CoinGecko
     *
     * @param array $datos_json Respuesta JSON de CoinGecko
     * @param array $simbolos Simbolos solicitados
     * @return array Datos procesados indexados por simbolo
     */
    private function procesar_respuesta_coingecko($datos_json, $simbolos) {
        $resultado = array();

        foreach ($simbolos as $simbolo) {
            $id_coingecko = isset($this->coingecko_ids[$simbolo])
                ? $this->coingecko_ids[$simbolo]
                : strtolower($simbolo);

            if (!isset($datos_json[$id_coingecko])) {
                continue;
            }

            $datos_token = $datos_json[$id_coingecko];

            $resultado[$simbolo] = array(
                'simbolo'     => $simbolo,
                'precio_usd'  => floatval($datos_token['usd'] ?? 0),
                'cambio_1h'   => floatval($datos_token['usd_1h_change'] ?? 0),
                'cambio_24h'  => floatval($datos_token['usd_24h_change'] ?? 0),
                'cambio_7d'   => floatval($datos_token['usd_7d_change'] ?? 0),
                'volumen_24h' => floatval($datos_token['usd_24h_vol'] ?? 0),
                'market_cap'  => floatval($datos_token['usd_market_cap'] ?? 0),
                'timestamp'   => current_time('mysql'),
            );
        }

        return $resultado;
    }

    /**
     * Formatea datos de mercado para enviar a la IA
     *
     * @param array $simbolos Lista de simbolos
     * @return array Datos formateados para el prompt de IA
     */
    public function obtener_datos_para_ia($simbolos) {
        $datos_mercado = $this->obtener_precios_multiples($simbolos);

        $datos_formateados = array(
            'timestamp' => current_time('c'),
            'tokens'    => array(),
        );

        foreach ($datos_mercado as $simbolo => $datos) {
            $datos_formateados['tokens'][$simbolo] = array(
                'precio_usd'      => $datos['precio_usd'],
                'cambio_1h'       => sprintf('%+.2f%%', $datos['cambio_1h']),
                'cambio_24h'      => sprintf('%+.2f%%', $datos['cambio_24h']),
                'cambio_7d'       => sprintf('%+.2f%%', $datos['cambio_7d']),
                'volumen_24h_usd' => sprintf('$%s', number_format($datos['volumen_24h'], 0, '.', ',')),
                'market_cap_usd'  => sprintf('$%s', number_format($datos['market_cap'], 0, '.', ',')),
                'tendencia'       => $this->determinar_tendencia($datos),
            );
        }

        return $datos_formateados;
    }

    /**
     * Obtiene un resumen del mercado con sentimiento
     *
     * @return array Resumen del mercado
     */
    public function obtener_resumen_mercado() {
        $tokens_principales = array('SOL', 'BTC', 'ETH');
        $datos_mercado = $this->obtener_precios_multiples($tokens_principales);

        $resumen = array(
            'timestamp' => current_time('c'),
            'tokens'    => array(),
        );

        $cambios = array();
        foreach ($tokens_principales as $token) {
            if (isset($datos_mercado[$token])) {
                $datos = $datos_mercado[$token];
                $resumen['tokens'][$token] = array(
                    'precio'     => $datos['precio_usd'],
                    'cambio_24h' => $datos['cambio_24h'],
                    'volumen'    => $datos['volumen_24h'],
                );
                $cambios[] = $datos['cambio_24h'];
            }
        }

        if (!empty($cambios)) {
            $promedio_cambio = array_sum($cambios) / count($cambios);
            if ($promedio_cambio > 3) {
                $resumen['sentimiento'] = 'alcista';
            } elseif ($promedio_cambio < -3) {
                $resumen['sentimiento'] = 'bajista';
            } else {
                $resumen['sentimiento'] = 'neutral';
            }
        } else {
            $resumen['sentimiento'] = 'desconocido';
        }

        return $resumen;
    }

    /**
     * Determina la tendencia de un token basandose en sus cambios
     *
     * @param array $datos Datos del token
     * @return string Tendencia
     */
    private function determinar_tendencia($datos) {
        $cambio_1h  = $datos['cambio_1h'];
        $cambio_24h = $datos['cambio_24h'];

        if ($cambio_1h > 2 && $cambio_24h > 5) {
            return 'fuerte alcista';
        } elseif ($cambio_1h > 0 && $cambio_24h > 0) {
            return 'alcista';
        } elseif ($cambio_1h < -2 && $cambio_24h < -5) {
            return 'fuerte bajista';
        } elseif ($cambio_1h < 0 && $cambio_24h < 0) {
            return 'bajista';
        }

        return 'lateral';
    }

    /**
     * Obtiene solo los precios como array simple simbolo => precio
     *
     * @param array $simbolos Lista de simbolos
     * @return array Precios indexados por simbolo
     */
    public function obtener_precios_simples($simbolos) {
        $datos = $this->obtener_precios_multiples($simbolos);
        $precios = array();

        foreach ($datos as $simbolo => $datos_token) {
            $precios[$simbolo] = $datos_token['precio_usd'];
        }

        return $precios;
    }
}
