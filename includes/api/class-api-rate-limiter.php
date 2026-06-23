<?php
/**
 * Rate Limiter para endpoints REST publicos
 *
 * Controla la frecuencia de peticiones a los endpoints publicos
 * usando transients de WordPress por direccion IP del cliente.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para limitar la tasa de peticiones a la API REST
 */
class Flavor_API_Rate_Limiter {

    /**
     * Limites de peticiones por tipo de request (peticiones por minuto)
     * Valores aumentados para soportar apps móviles que hacen múltiples peticiones
     */
    const LIMITE_GET_POR_MINUTO  = 120;  // 2 por segundo
    const LIMITE_POST_POR_MINUTO = 30;   // 0.5 por segundo

    /**
     * Duracion de la ventana de tiempo en segundos
     */
    const VENTANA_TIEMPO_SEGUNDOS = 60;

    /**
     * Prefijo para las claves de transient
     */
    const PREFIJO_TRANSIENT = 'flavor_rate_limit_';

    /**
     * Registrar hooks globales de rate limit
     */
    public static function register_hooks() {
        add_filter('rest_authentication_errors', [__CLASS__, 'rest_auth_rate_limit'], 20);
    }

    /**
     * Rate limit global para peticiones REST no autenticadas
     *
     * @param mixed $result Resultado previo de autenticación
     * @return mixed WP_Error si excede el límite, o $result
     */
    public static function rest_auth_rate_limit($result) {
        if (!empty($result)) {
            return $result;
        }

        if (is_user_logged_in()) {
            return $result;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'OPTIONS') {
            return $result;
        }

        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        $rate = self::check_rate_limit($tipo);

        return is_wp_error($rate) ? $rate : $result;
    }

    /**
     * Verifica si la peticion actual esta dentro del limite de tasa permitido
     *
     * @param string $tipo_peticion Tipo de peticion: 'get' o 'post'
     * @return true|WP_Error True si esta dentro del limite, WP_Error si lo excede
     */
    public static function check_rate_limit($tipo_peticion = 'get') {
        $direccion_ip_cliente = self::obtener_ip_cliente();

        if (empty($direccion_ip_cliente)) {
            flavor_platform_log('Rate Limiter: No se pudo determinar la IP del cliente', 'warning');
            return true;
        }

        $tipo_peticion_normalizado = strtolower($tipo_peticion);
        $limite_maximo_peticiones  = self::obtener_limite_para_tipo($tipo_peticion_normalizado);
        $clave_transient           = self::generar_clave_transient($direccion_ip_cliente, $tipo_peticion_normalizado);

        $datos_contador = get_transient($clave_transient);

        if ($datos_contador === false) {
            $datos_contador = [
                'cantidad_peticiones'   => 1,
                'inicio_ventana_tiempo' => time(),
            ];
            set_transient($clave_transient, $datos_contador, self::VENTANA_TIEMPO_SEGUNDOS);

            return true;
        }

        // Verificar si la ventana de tiempo ha expirado (doble check por seguridad)
        $tiempo_transcurrido = time() - $datos_contador['inicio_ventana_tiempo'];
        if ($tiempo_transcurrido >= self::VENTANA_TIEMPO_SEGUNDOS) {
            $datos_contador = [
                'cantidad_peticiones'   => 1,
                'inicio_ventana_tiempo' => time(),
            ];
            set_transient($clave_transient, $datos_contador, self::VENTANA_TIEMPO_SEGUNDOS);

            return true;
        }

        // Incrementar contador de peticiones
        $datos_contador['cantidad_peticiones']++;

        if ($datos_contador['cantidad_peticiones'] > $limite_maximo_peticiones) {
            $segundos_restantes = self::VENTANA_TIEMPO_SEGUNDOS - $tiempo_transcurrido;

            flavor_platform_log(
                sprintf(
                    'Rate Limiter: IP %s excedio el limite de %d peticiones %s/min. Total: %d',
                    $direccion_ip_cliente,
                    $limite_maximo_peticiones,
                    strtoupper($tipo_peticion_normalizado),
                    $datos_contador['cantidad_peticiones']
                ),
                'warning'
            );

            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    __('Demasiadas peticiones. Por favor, espera %d segundos antes de intentarlo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $segundos_restantes
                ),
                [
                    'status'      => 429,
                    'retry_after' => $segundos_restantes,
                    'limit'       => $limite_maximo_peticiones,
                    'remaining'   => 0,
                    'reset_at'    => $datos_contador['inicio_ventana_tiempo'] + self::VENTANA_TIEMPO_SEGUNDOS,
                ]
            );
        }

        // Actualizar el transient con el nuevo contador
        set_transient($clave_transient, $datos_contador, self::VENTANA_TIEMPO_SEGUNDOS);

        return true;
    }

    /**
     * Obtiene la direccion IP real del cliente con soporte para proxies y CloudFlare
     *
     * Orden de prioridad:
     * 1. CF-Connecting-IP (CloudFlare)
     * 2. X-Forwarded-For (proxies genericos)
     * 3. X-Real-IP (Nginx proxy)
     * 4. REMOTE_ADDR (conexion directa)
     *
     * @return string Direccion IP del cliente o cadena vacia si no se puede determinar
     */
    public static function obtener_ip_cliente() {
        $remote_addr = isset($_SERVER['REMOTE_ADDR'])
            ? trim(sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])))
            : '';

        // Las cabeceras X-Forwarded-For / CF-Connecting-IP / X-Real-IP son
        // falsificables por el cliente. Solo se confían si la conexion viene
        // de un proxy de confianza declarado. Sin proxies configurados (por
        // defecto) se usa REMOTE_ADDR, que NO se puede falsificar.
        //
        // Configurar tras un proxy/CDN (Cloudflare, Nginx) con:
        //   add_filter('flavor_trusted_proxies', fn() => ['192.0.2.10', '10.0.0.0/8']);
        $proxies_confianza = array_filter((array) apply_filters('flavor_trusted_proxies', []));

        if (!empty($proxies_confianza) && self::ip_en_lista($remote_addr, $proxies_confianza)) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $nombre_cabecera) {
                if (empty($_SERVER[$nombre_cabecera])) {
                    continue;
                }
                $valor_cabecera = sanitize_text_field(wp_unslash($_SERVER[$nombre_cabecera]));

                // X-Forwarded-For puede contener multiples IPs separadas por coma;
                // la primera es la del cliente original.
                if ($nombre_cabecera === 'HTTP_X_FORWARDED_FOR') {
                    $lista_ips = explode(',', $valor_cabecera);
                    $direccion_ip_candidata = trim($lista_ips[0]);
                } else {
                    $direccion_ip_candidata = trim($valor_cabecera);
                }

                if (filter_var($direccion_ip_candidata, FILTER_VALIDATE_IP)) {
                    return $direccion_ip_candidata;
                }
            }
        }

        return filter_var($remote_addr, FILTER_VALIDATE_IP) ? $remote_addr : '';
    }

    /**
     * ¿Está $ip dentro de la lista de IPs/CIDRs de confianza?
     *
     * @param string $ip
     * @param array  $lista IPs exactas o rangos CIDR.
     * @return bool
     */
    private static function ip_en_lista($ip, array $lista) {
        if (empty($ip)) {
            return false;
        }
        foreach ($lista as $entrada) {
            $entrada = trim((string) $entrada);
            if ($entrada === '') {
                continue;
            }
            if (strpos($entrada, '/') === false) {
                if ($ip === $entrada) {
                    return true;
                }
                continue;
            }
            if (self::ip_en_cidr($ip, $entrada)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Comprueba si una IP (IPv4 o IPv6) pertenece a un rango CIDR.
     *
     * @param string $ip
     * @param string $cidr p.ej. "10.0.0.0/8" o "2001:db8::/32".
     * @return bool
     */
    private static function ip_en_cidr($ip, $cidr) {
        list($subred, $bits) = array_pad(explode('/', $cidr, 2), 2, null);
        $ip_bin = @inet_pton($ip);
        $subred_bin = @inet_pton((string) $subred);
        if ($ip_bin === false || $subred_bin === false || strlen($ip_bin) !== strlen($subred_bin)) {
            return false;
        }
        $bits = (int) $bits;
        $bytes_completos = intdiv($bits, 8);
        $bits_resto = $bits % 8;
        if ($bytes_completos > 0 && substr($ip_bin, 0, $bytes_completos) !== substr($subred_bin, 0, $bytes_completos)) {
            return false;
        }
        if ($bits_resto === 0) {
            return true;
        }
        $mascara = chr((0xff << (8 - $bits_resto)) & 0xff);
        return (ord($ip_bin[$bytes_completos]) & ord($mascara)) === (ord($subred_bin[$bytes_completos]) & ord($mascara));
    }

    /**
     * Obtiene el limite maximo de peticiones segun el tipo de request
     *
     * @param string $tipo_peticion 'get' o 'post'
     * @return int Numero maximo de peticiones permitidas por minuto
     */
    private static function obtener_limite_para_tipo($tipo_peticion) {
        $limites_por_tipo = [
            'get'  => self::LIMITE_GET_POR_MINUTO,
            'post' => self::LIMITE_POST_POR_MINUTO,
        ];

        $limite_base = $limites_por_tipo[$tipo_peticion] ?? self::LIMITE_GET_POR_MINUTO;

        /**
         * Permite al admin del site ajustar los límites por tipo de request.
         *
         * Los defaults (120 GET/min, 30 POST/min) son conservadores: evitan
         * abuso con margen holgado para uso legítimo. Si la telemetría en
         * Flavor_Performance_Metrics muestra 429 falsos positivos (operación
         * normal tocando el límite), subir el valor aquí.
         *
         * @param int    $limite_base   Máximo de requests por minuto.
         * @param string $tipo_peticion 'get' o 'post'.
         */
        return (int) apply_filters( 'flavor_api_rate_limit_max_requests', $limite_base, $tipo_peticion );
    }

    /**
     * Genera una clave unica de transient para la combinacion IP + tipo de peticion
     *
     * @param string $direccion_ip Direccion IP del cliente
     * @param string $tipo_peticion Tipo de peticion ('get' o 'post')
     * @return string Clave del transient
     */
    private static function generar_clave_transient($direccion_ip, $tipo_peticion) {
        $hash_ip = md5($direccion_ip);

        return self::PREFIJO_TRANSIENT . $tipo_peticion . '_' . $hash_ip;
    }
}

// Activar rate limit global en REST
Flavor_API_Rate_Limiter::register_hooks();
