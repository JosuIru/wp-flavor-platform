<?php
/**
 * Rate Limiter para endpoints REST publicos
 *
 * Controla la frecuencia de peticiones a los endpoints publicos
 * usando transients de WordPress por direccion IP del cliente.
 *
 * @package FlavorChatIA
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
            flavor_chat_ia_log('Rate Limiter: No se pudo determinar la IP del cliente', 'warning');
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

            flavor_chat_ia_log(
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
                    __('Demasiadas peticiones. Por favor, espera %d segundos antes de intentarlo de nuevo.', 'flavor-chat-ia'),
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
        $cabeceras_ip_prioritarias = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($cabeceras_ip_prioritarias as $nombre_cabecera) {
            if (!empty($_SERVER[$nombre_cabecera])) {
                $valor_cabecera = sanitize_text_field(wp_unslash($_SERVER[$nombre_cabecera]));

                // X-Forwarded-For puede contener multiples IPs separadas por coma
                // La primera IP es la del cliente original
                if ($nombre_cabecera === 'HTTP_X_FORWARDED_FOR') {
                    $lista_ips = explode(',', $valor_cabecera);
                    $direccion_ip_candidata = trim($lista_ips[0]);
                } else {
                    $direccion_ip_candidata = trim($valor_cabecera);
                }

                // Validar que sea una IP valida (IPv4 o IPv6)
                if (filter_var($direccion_ip_candidata, FILTER_VALIDATE_IP)) {
                    return $direccion_ip_candidata;
                }
            }
        }

        return '';
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

        return $limites_por_tipo[$tipo_peticion] ?? self::LIMITE_GET_POR_MINUTO;
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
