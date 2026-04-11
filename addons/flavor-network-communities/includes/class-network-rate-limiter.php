<?php
/**
 * Rate Limiter para API de Red de Comunidades
 *
 * Implementa limitación de peticiones por IP/endpoint
 * usando transients de WordPress.
 *
 * @package FlavorPlatform\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Rate_Limiter {

    /**
     * Prefijo para transients
     */
    const TRANSIENT_PREFIX = 'flavor_network_rl_';

    /**
     * Configuración por defecto de límites
     */
    const DEFAULT_LIMITS = [
        'directory'   => ['requests' => 60,  'window' => 60],   // 60 req/min
        'map'         => ['requests' => 30,  'window' => 60],   // 30 req/min
        'nearby'      => ['requests' => 30,  'window' => 60],   // 30 req/min
        'board'       => ['requests' => 60,  'window' => 60],   // 60 req/min
        'content'     => ['requests' => 60,  'window' => 60],   // 60 req/min
        'events'      => ['requests' => 60,  'window' => 60],   // 60 req/min
        'default'     => ['requests' => 100, 'window' => 60],   // 100 req/min
        'write'       => ['requests' => 10,  'window' => 60],   // 10 writes/min
        'federation'  => ['requests' => 30,  'window' => 60],   // 30 req/min (entre nodos)
    ];

    /**
     * Headers de respuesta para rate limiting
     */
    const RATE_LIMIT_HEADERS = [
        'limit'     => 'X-RateLimit-Limit',
        'remaining' => 'X-RateLimit-Remaining',
        'reset'     => 'X-RateLimit-Reset',
    ];

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Límites personalizados (filtrable)
     */
    private $limits = [];

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->limits = apply_filters('flavor_network_rate_limits', self::DEFAULT_LIMITS);
    }

    /**
     * Obtiene la IP del cliente
     */
    public function get_client_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxies
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR',               // Default
        ];

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Para X-Forwarded-For, tomar la primera IP
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * Genera la clave de transient para rate limiting
     */
    private function get_rate_key($endpoint_type, $identifier = null) {
        $identifier = $identifier ?: $this->get_client_ip();
        $identifier_hash = substr(md5($identifier), 0, 12);
        return self::TRANSIENT_PREFIX . $endpoint_type . '_' . $identifier_hash;
    }

    /**
     * Obtiene los datos actuales de rate limiting
     */
    private function get_rate_data($key) {
        $data = get_transient($key);
        if ($data === false) {
            return null;
        }
        return $data;
    }

    /**
     * Verifica si la petición está dentro del límite
     *
     * @param string $endpoint_type Tipo de endpoint (directory, map, write, etc.)
     * @param string|null $identifier Identificador personalizado (por defecto IP)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset' => int, 'limit' => int]
     */
    public function check_rate_limit($endpoint_type, $identifier = null) {
        // Permitir bypass para admins si está configurado
        if (apply_filters('flavor_network_rate_limit_bypass_admin', true) && current_user_can('manage_options')) {
            return [
                'allowed'   => true,
                'remaining' => PHP_INT_MAX,
                'reset'     => 0,
                'limit'     => PHP_INT_MAX,
            ];
        }

        $limits = $this->limits[$endpoint_type] ?? $this->limits['default'];
        $max_requests = $limits['requests'];
        $window_seconds = $limits['window'];

        $key = $this->get_rate_key($endpoint_type, $identifier);
        $now = time();

        $data = $this->get_rate_data($key);

        if ($data === null) {
            // Primera petición en la ventana
            $new_data = [
                'count'      => 1,
                'window_start' => $now,
            ];
            set_transient($key, $new_data, $window_seconds);

            return [
                'allowed'   => true,
                'remaining' => $max_requests - 1,
                'reset'     => $now + $window_seconds,
                'limit'     => $max_requests,
            ];
        }

        $window_end = $data['window_start'] + $window_seconds;

        if ($now >= $window_end) {
            // La ventana anterior expiró, crear nueva
            $new_data = [
                'count'      => 1,
                'window_start' => $now,
            ];
            set_transient($key, $new_data, $window_seconds);

            return [
                'allowed'   => true,
                'remaining' => $max_requests - 1,
                'reset'     => $now + $window_seconds,
                'limit'     => $max_requests,
            ];
        }

        // Dentro de la ventana actual
        $current_count = $data['count'] + 1;
        $remaining = max(0, $max_requests - $current_count);
        $allowed = $current_count <= $max_requests;

        if ($allowed) {
            // Actualizar contador
            $data['count'] = $current_count;
            set_transient($key, $data, $window_end - $now);
        }

        return [
            'allowed'   => $allowed,
            'remaining' => $remaining,
            'reset'     => $window_end,
            'limit'     => $max_requests,
        ];
    }

    /**
     * Añade headers de rate limiting a la respuesta
     */
    public function add_rate_limit_headers($result) {
        if (!is_array($result)) {
            return;
        }

        header(self::RATE_LIMIT_HEADERS['limit'] . ': ' . $result['limit']);
        header(self::RATE_LIMIT_HEADERS['remaining'] . ': ' . $result['remaining']);
        header(self::RATE_LIMIT_HEADERS['reset'] . ': ' . $result['reset']);
    }

    /**
     * Genera respuesta de error por rate limit excedido
     */
    public function rate_limit_exceeded_response($result) {
        $this->add_rate_limit_headers($result);

        return new WP_Error(
            'rate_limit_exceeded',
            sprintf(
                __('Demasiadas peticiones. Límite: %d por minuto. Intenta de nuevo en %d segundos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $result['limit'],
                max(0, $result['reset'] - time())
            ),
            ['status' => 429]
        );
    }

    /**
     * Middleware para verificar rate limit en endpoints
     *
     * @param string $endpoint_type Tipo de endpoint
     * @return true|WP_Error
     */
    public function enforce_rate_limit($endpoint_type) {
        $result = $this->check_rate_limit($endpoint_type);

        if (!$result['allowed']) {
            return $this->rate_limit_exceeded_response($result);
        }

        $this->add_rate_limit_headers($result);
        return true;
    }

    /**
     * Limpia transients de rate limiting expirados
     * (Llamar periódicamente via cron)
     */
    public static function cleanup_expired() {
        global $wpdb;

        $prefix = '_transient_' . self::TRANSIENT_PREFIX;
        $timeout_prefix = '_transient_timeout_' . self::TRANSIENT_PREFIX;

        // Eliminar transients expirados
        $wpdb->query($wpdb->prepare(
            "DELETE a, b FROM {$wpdb->options} a
             LEFT JOIN {$wpdb->options} b ON b.option_name = REPLACE(a.option_name, '_timeout', '')
             WHERE a.option_name LIKE %s
             AND a.option_value < %d",
            $timeout_prefix . '%',
            time()
        ));
    }

    /**
     * Obtiene estadísticas de uso
     */
    public function get_usage_stats($endpoint_type = null, $identifier = null) {
        if ($endpoint_type) {
            $key = $this->get_rate_key($endpoint_type, $identifier);
            $data = $this->get_rate_data($key);
            $limits = $this->limits[$endpoint_type] ?? $this->limits['default'];

            return [
                'endpoint'  => $endpoint_type,
                'used'      => $data ? $data['count'] : 0,
                'limit'     => $limits['requests'],
                'window'    => $limits['window'],
                'remaining' => $data ? max(0, $limits['requests'] - $data['count']) : $limits['requests'],
            ];
        }

        return $this->limits;
    }
}
