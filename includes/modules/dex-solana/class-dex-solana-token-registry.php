<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registro de tokens SPL de Solana.
 *
 * Gestiona un registro de tokens con sus metadatos (direcciones mint, decimales, simbolos).
 */
class Flavor_Dex_Solana_Token_Registry {

    /**
     * Array estatico de tokens conocidos de Solana con sus direcciones mint y decimales.
     *
     * @var array
     */
    private static $tokens_conocidos = array(
        'SOL' => array(
            'mint'     => 'So11111111111111111111111111111111111111112',
            'simbolo'  => 'SOL',
            'nombre'   => 'Solana',
            'decimales' => 9,
        ),
        'USDC' => array(
            'mint'     => 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v',
            'simbolo'  => 'USDC',
            'nombre'   => 'USD Coin',
            'decimales' => 6,
        ),
        'USDT' => array(
            'mint'     => 'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB',
            'simbolo'  => 'USDT',
            'nombre'   => 'Tether USD',
            'decimales' => 6,
        ),
        'JUP' => array(
            'mint'     => 'JUPyiwrYJFskUPiHa7hkeR8VUtAeFoSYbKedZNsDvCN',
            'simbolo'  => 'JUP',
            'nombre'   => 'Jupiter',
            'decimales' => 6,
        ),
        'RAY' => array(
            'mint'     => '4k3Dyjzvzp8eMZWUXbBCjEvwSkkk59S5iCNLY3QrkX6R',
            'simbolo'  => 'RAY',
            'nombre'   => 'Raydium',
            'decimales' => 6,
        ),
        'BONK' => array(
            'mint'     => 'DezXAZ8z7PnrnRJjz3wXBoRgixCa6xjnB7YaB1pPB263',
            'simbolo'  => 'BONK',
            'nombre'   => 'Bonk',
            'decimales' => 5,
        ),
        'WIF' => array(
            'mint'     => 'EKpQGSJtjMFqKZ9KQanSqYXRcF8fBopzLHYxdM65zcjm',
            'simbolo'  => 'WIF',
            'nombre'   => 'dogwifhat',
            'decimales' => 6,
        ),
        'JTO' => array(
            'mint'     => 'jtojtomepa8beP8AuQc6eXt5FriJwfFMwQx2v2f9mCL',
            'simbolo'  => 'JTO',
            'nombre'   => 'Jito',
            'decimales' => 9,
        ),
        'ORCA' => array(
            'mint'     => 'orcaEKTdK7LKz57vaAYr9QeNsVEPfiu6QeMU1kektZE',
            'simbolo'  => 'ORCA',
            'nombre'   => 'Orca',
            'decimales' => 6,
        ),
        'PYTH' => array(
            'mint'     => 'HZ1JovNiVvGrGNiiYvEozEVgZ58xaU3RKwX8eACQBCt3',
            'simbolo'  => 'PYTH',
            'nombre'   => 'Pyth Network',
            'decimales' => 6,
        ),
    );

    /**
     * Obtiene un token por su direccion mint.
     *
     * Busca primero en los tokens conocidos, luego en la base de datos.
     *
     * @param string $mint_address Direccion mint del token.
     * @return array|null Datos del token o null si no se encuentra.
     */
    public function obtener_token_por_mint($mint_address) {
        // Buscar en tokens conocidos
        foreach (self::$tokens_conocidos as $token_datos) {
            if ($token_datos['mint'] === $mint_address) {
                return array(
                    'mint'       => $token_datos['mint'],
                    'simbolo'    => $token_datos['simbolo'],
                    'nombre'     => $token_datos['nombre'],
                    'decimales'  => $token_datos['decimales'],
                    'logo_url'   => '',
                    'verificado' => 1,
                );
            }
        }

        // Buscar en la base de datos
        global $wpdb;
        $nombre_tabla = $wpdb->prefix . 'flavor_dex_tokens';

        $token_encontrado = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT mint, simbolo, nombre, decimales, logo_url, verificado FROM {$nombre_tabla} WHERE mint = %s LIMIT 1",
                $mint_address
            ),
            ARRAY_A
        );

        if ($token_encontrado) {
            return array(
                'mint'       => $token_encontrado['mint'],
                'simbolo'    => $token_encontrado['simbolo'],
                'nombre'     => $token_encontrado['nombre'],
                'decimales'  => (int) $token_encontrado['decimales'],
                'logo_url'   => $token_encontrado['logo_url'],
                'verificado' => (int) $token_encontrado['verificado'],
            );
        }

        return null;
    }

    /**
     * Obtiene un token por su simbolo (busqueda insensible a mayusculas/minusculas).
     *
     * Busca primero en los tokens conocidos, luego en la base de datos.
     *
     * @param string $simbolo Simbolo del token (ej: SOL, USDC).
     * @return array|null Datos del token o null si no se encuentra.
     */
    public function obtener_token_por_simbolo($simbolo) {
        $simbolo_mayusculas = strtoupper(trim($simbolo));

        // Buscar en tokens conocidos
        if (isset(self::$tokens_conocidos[$simbolo_mayusculas])) {
            $token_datos = self::$tokens_conocidos[$simbolo_mayusculas];
            return array(
                'mint'       => $token_datos['mint'],
                'simbolo'    => $token_datos['simbolo'],
                'nombre'     => $token_datos['nombre'],
                'decimales'  => $token_datos['decimales'],
                'logo_url'   => '',
                'verificado' => 1,
            );
        }

        // Buscar en la base de datos (insensible a mayusculas)
        global $wpdb;
        $nombre_tabla = $wpdb->prefix . 'flavor_dex_tokens';

        $token_encontrado = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT mint, simbolo, nombre, decimales, logo_url, verificado FROM {$nombre_tabla} WHERE UPPER(simbolo) = %s LIMIT 1",
                $simbolo_mayusculas
            ),
            ARRAY_A
        );

        if ($token_encontrado) {
            return array(
                'mint'       => $token_encontrado['mint'],
                'simbolo'    => $token_encontrado['simbolo'],
                'nombre'     => $token_encontrado['nombre'],
                'decimales'  => (int) $token_encontrado['decimales'],
                'logo_url'   => $token_encontrado['logo_url'],
                'verificado' => (int) $token_encontrado['verificado'],
            );
        }

        return null;
    }

    /**
     * Sincroniza tokens desde la API de Jupiter a la base de datos.
     *
     * @param Flavor_Dex_Solana_Jupiter_API $jupiter_api Instancia de la API de Jupiter.
     * @return int|false Numero de tokens sincronizados o false en caso de error.
     */
    public function sincronizar_desde_jupiter($jupiter_api) {
        $lista_tokens_jupiter = $jupiter_api->obtener_lista_tokens();

        if (empty($lista_tokens_jupiter) || !is_array($lista_tokens_jupiter)) {
            return false;
        }

        global $wpdb;
        $nombre_tabla = $wpdb->prefix . 'flavor_dex_tokens';
        $contador_sincronizados = 0;

        foreach ($lista_tokens_jupiter as $token_jupiter) {
            $direccion_mint = isset($token_jupiter['address']) ? sanitize_text_field($token_jupiter['address']) : '';
            $simbolo_token  = isset($token_jupiter['symbol']) ? sanitize_text_field($token_jupiter['symbol']) : '';
            $nombre_token   = isset($token_jupiter['name']) ? sanitize_text_field($token_jupiter['name']) : '';
            $decimales_token = isset($token_jupiter['decimals']) ? (int) $token_jupiter['decimals'] : 0;
            $logo_url_token  = isset($token_jupiter['logoURI']) ? esc_url_raw($token_jupiter['logoURI']) : '';
            $verificado_token = 1;

            if (empty($direccion_mint)) {
                continue;
            }

            $resultado_insercion = $wpdb->replace(
                $nombre_tabla,
                array(
                    'mint'       => $direccion_mint,
                    'simbolo'    => $simbolo_token,
                    'nombre'     => $nombre_token,
                    'decimales'  => $decimales_token,
                    'logo_url'   => $logo_url_token,
                    'verificado' => $verificado_token,
                ),
                array('%s', '%s', '%s', '%d', '%s', '%d')
            );

            if ($resultado_insercion !== false) {
                $contador_sincronizados++;
            }
        }

        return $contador_sincronizados;
    }

    /**
     * Convierte una cantidad legible a unidades base (lamports).
     *
     * Ejemplo: 1.5 SOL con 9 decimales = 1500000000.
     *
     * @param float|string $cantidad_legible Cantidad en formato legible (ej: 1.5).
     * @param int          $decimales        Numero de decimales del token.
     * @return string Cantidad en unidades base como string (para manejar enteros grandes).
     */
    public function cantidad_a_base_units($cantidad_legible, $decimales) {
        $decimales = (int) $decimales;

        if (function_exists('bcmul')) {
            $factor_multiplicador = bcpow('10', (string) $decimales, 0);
            $resultado_base_units = bcmul((string) $cantidad_legible, $factor_multiplicador, 0);
            return $resultado_base_units;
        }

        // Fallback sin bcmath: usar aritmetica de strings
        $cantidad_como_string = (string) $cantidad_legible;
        $posicion_punto = strpos($cantidad_como_string, '.');

        if ($posicion_punto === false) {
            // No hay parte decimal, solo agregar ceros
            return $cantidad_como_string . str_repeat('0', $decimales);
        }

        $parte_entera   = substr($cantidad_como_string, 0, $posicion_punto);
        $parte_decimal  = substr($cantidad_como_string, $posicion_punto + 1);
        $longitud_decimal = strlen($parte_decimal);

        if ($longitud_decimal >= $decimales) {
            // Truncar la parte decimal al numero de decimales
            $parte_decimal_ajustada = substr($parte_decimal, 0, $decimales);
        } else {
            // Rellenar con ceros a la derecha
            $parte_decimal_ajustada = str_pad($parte_decimal, $decimales, '0', STR_PAD_RIGHT);
        }

        $resultado_concatenado = $parte_entera . $parte_decimal_ajustada;

        // Eliminar ceros iniciales (pero mantener al menos un digito)
        $resultado_limpio = ltrim($resultado_concatenado, '0');
        if ($resultado_limpio === '') {
            $resultado_limpio = '0';
        }

        return $resultado_limpio;
    }

    /**
     * Convierte unidades base (lamports) a cantidad legible.
     *
     * Ejemplo: 1500000000 con 9 decimales = 1.5.
     *
     * @param string|int $base_units Cantidad en unidades base.
     * @param int        $decimales  Numero de decimales del token.
     * @return float Cantidad en formato legible.
     */
    public function base_units_a_cantidad($base_units, $decimales) {
        $decimales = (int) $decimales;

        if ($decimales === 0) {
            return (float) $base_units;
        }

        if (function_exists('bcdiv')) {
            $factor_divisor = bcpow('10', (string) $decimales, 0);
            $resultado_legible = bcdiv((string) $base_units, $factor_divisor, $decimales);
            return (float) $resultado_legible;
        }

        // Fallback sin bcmath
        $base_units_string = (string) $base_units;
        $longitud_base = strlen($base_units_string);

        if ($longitud_base <= $decimales) {
            // El numero es menor que el factor, hay que anteponer ceros
            $base_units_rellenado = str_pad($base_units_string, $decimales + 1, '0', STR_PAD_LEFT);
            $parte_entera_resultado  = substr($base_units_rellenado, 0, strlen($base_units_rellenado) - $decimales);
            $parte_decimal_resultado = substr($base_units_rellenado, -$decimales);
        } else {
            $parte_entera_resultado  = substr($base_units_string, 0, $longitud_base - $decimales);
            $parte_decimal_resultado = substr($base_units_string, -$decimales);
        }

        $cantidad_formateada = $parte_entera_resultado . '.' . $parte_decimal_resultado;

        return (float) $cantidad_formateada;
    }

    /**
     * Devuelve el array completo de tokens conocidos.
     *
     * @return array Array asociativo de tokens conocidos.
     */
    public function obtener_todos_conocidos() {
        return self::$tokens_conocidos;
    }

    /**
     * Registra o actualiza un token en la base de datos.
     *
     * @param string $mint      Direccion mint del token.
     * @param string $simbolo   Simbolo del token.
     * @param string $nombre    Nombre completo del token.
     * @param int    $decimales Numero de decimales del token.
     * @param string $logo_url  URL del logo del token (opcional).
     * @param int    $verificado Si el token esta verificado (opcional, por defecto 0).
     * @return int|false Numero de filas afectadas o false en caso de error.
     */
    public function registrar_token($mint, $simbolo, $nombre, $decimales, $logo_url = '', $verificado = 0) {
        global $wpdb;
        $nombre_tabla = $wpdb->prefix . 'flavor_dex_tokens';

        $resultado_registro = $wpdb->replace(
            $nombre_tabla,
            array(
                'mint'       => sanitize_text_field($mint),
                'simbolo'    => sanitize_text_field($simbolo),
                'nombre'     => sanitize_text_field($nombre),
                'decimales'  => (int) $decimales,
                'logo_url'   => esc_url_raw($logo_url),
                'verificado' => (int) $verificado,
            ),
            array('%s', '%s', '%s', '%d', '%s', '%d')
        );

        return $resultado_registro;
    }
}
