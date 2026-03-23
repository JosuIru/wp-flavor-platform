<?php
/**
 * Rate Limiter v2 - Sistema avanzado de limitación de peticiones
 *
 * Características:
 * - Límites configurables por endpoint y método
 * - Headers X-RateLimit-* estándar
 * - Whitelist de IPs
 * - Límites diferenciados para apps autenticadas vs anónimas
 * - Sliding window con token bucket algorithm
 * - Soporte para tokens de API con límites personalizados
 * - Penalización exponencial para abuso
 *
 * @package FlavorChatIA
 * @subpackage API
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Flavor_API_Rate_Limiter_V2
 */
class Flavor_API_Rate_Limiter_V2 {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Configuración de límites por defecto
     */
    private $default_limits = [
        'anonymous' => [
            'GET' => ['requests' => 60, 'window' => 60],    // 60 req/min
            'POST' => ['requests' => 20, 'window' => 60],   // 20 req/min
            'PUT' => ['requests' => 20, 'window' => 60],
            'DELETE' => ['requests' => 10, 'window' => 60],
        ],
        'authenticated' => [
            'GET' => ['requests' => 300, 'window' => 60],   // 300 req/min
            'POST' => ['requests' => 100, 'window' => 60],  // 100 req/min
            'PUT' => ['requests' => 100, 'window' => 60],
            'DELETE' => ['requests' => 50, 'window' => 60],
        ],
        'api_key' => [
            'GET' => ['requests' => 600, 'window' => 60],   // 600 req/min (apps)
            'POST' => ['requests' => 200, 'window' => 60],  // 200 req/min
            'PUT' => ['requests' => 200, 'window' => 60],
            'DELETE' => ['requests' => 100, 'window' => 60],
        ],
    ];

    /**
     * Límites específicos por endpoint (pattern => config)
     */
    private $endpoint_limits = [];

    /**
     * IPs en whitelist (sin rate limit)
     */
    private $ip_whitelist = [];

    /**
     * IPs baneadas temporalmente
     */
    private $banned_ips = [];

    /**
     * Prefijo para transients
     */
    const TRANSIENT_PREFIX = 'flavor_rl2_';

    /**
     * Opción para configuración
     */
    const OPTION_CONFIG = 'flavor_rate_limiter_config';

    /**
     * Número de violaciones antes de ban temporal
     */
    const VIOLATIONS_BEFORE_BAN = 5;

    /**
     * Duración del ban temporal en segundos
     */
    const BAN_DURATION = 300; // 5 minutos

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_config();
        $this->register_hooks();
    }

    /**
     * Carga configuración guardada
     */
    private function load_config() {
        $saved_config = get_option(self::OPTION_CONFIG, []);

        if (!empty($saved_config['limits'])) {
            $this->default_limits = array_merge($this->default_limits, $saved_config['limits']);
        }

        if (!empty($saved_config['endpoint_limits'])) {
            $this->endpoint_limits = $saved_config['endpoint_limits'];
        }

        if (!empty($saved_config['ip_whitelist'])) {
            $this->ip_whitelist = $saved_config['ip_whitelist'];
        }

        // Añadir localhost a whitelist por defecto
        $this->ip_whitelist = array_merge($this->ip_whitelist, [
            '127.0.0.1',
            '::1',
        ]);
    }

    /**
     * Registra hooks
     */
    private function register_hooks() {
        // Hook en autenticación REST
        add_filter('rest_authentication_errors', [$this, 'check_rate_limit'], 99);

        // Hook para añadir headers de rate limit
        add_filter('rest_post_dispatch', [$this, 'add_rate_limit_headers'], 10, 3);

        // Endpoints de administración
        add_action('rest_api_init', [$this, 'register_admin_endpoints']);
    }

    /**
     * Verifica rate limit en la petición
     */
    public function check_rate_limit($result) {
        // Si ya hay error, no interferir
        if (is_wp_error($result)) {
            return $result;
        }

        // OPTIONS siempre permitido (CORS)
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'OPTIONS') {
            return $result;
        }

        $client_ip = $this->get_client_ip();

        // Whitelist check
        if ($this->is_whitelisted($client_ip)) {
            return $result;
        }

        // Ban check
        if ($this->is_banned($client_ip)) {
            return new WP_Error(
                'ip_banned',
                __('Tu IP ha sido bloqueada temporalmente por exceder los límites repetidamente.', 'flavor-chat-ia'),
                ['status' => 429]
            );
        }

        // Determinar tipo de cliente
        $client_type = $this->determine_client_type();

        // Obtener límites aplicables
        $limits = $this->get_applicable_limits($method, $client_type);

        // Verificar rate limit
        $check_result = $this->check_and_increment($client_ip, $method, $client_type, $limits);

        if (is_wp_error($check_result)) {
            // Registrar violación
            $this->record_violation($client_ip);
            return $check_result;
        }

        return $result;
    }

    /**
     * Determina el tipo de cliente
     */
    private function determine_client_type() {
        // Verificar API key
        $api_key = $this->get_api_key_from_request();
        if ($api_key && $this->validate_api_key($api_key)) {
            return 'api_key';
        }

        // Usuario autenticado
        if (is_user_logged_in()) {
            return 'authenticated';
        }

        return 'anonymous';
    }

    /**
     * Obtiene API key de la petición
     */
    private function get_api_key_from_request() {
        // Header X-VBP-Key
        if (!empty($_SERVER['HTTP_X_VBP_KEY'])) {
            return sanitize_text_field($_SERVER['HTTP_X_VBP_KEY']);
        }

        // Header Authorization: Bearer
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(.+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                return sanitize_text_field($matches[1]);
            }
        }

        // Query parameter
        if (!empty($_GET['api_key'])) {
            return sanitize_text_field($_GET['api_key']);
        }

        return null;
    }

    /**
     * Valida API key
     */
    private function validate_api_key($key) {
        // API key estándar de Flavor
        if ($key === 'flavor-vbp-2024') {
            return true;
        }

        // Verificar keys personalizadas guardadas
        $custom_keys = get_option('flavor_api_keys', []);
        if (isset($custom_keys[$key]) && $custom_keys[$key]['active']) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene límites aplicables
     */
    private function get_applicable_limits($method, $client_type) {
        // Verificar límites específicos por endpoint
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($this->endpoint_limits as $pattern => $limits) {
            if (preg_match($pattern, $request_uri)) {
                return $limits[$method] ?? $limits['*'] ?? $this->default_limits[$client_type][$method];
            }
        }

        // Límites por defecto según tipo de cliente y método
        return $this->default_limits[$client_type][$method] ?? ['requests' => 60, 'window' => 60];
    }

    /**
     * Verifica e incrementa contador
     */
    private function check_and_increment($ip, $method, $client_type, $limits) {
        $key = $this->generate_key($ip, $method, $client_type);
        $now = time();

        // Obtener datos actuales
        $data = get_transient($key);

        if ($data === false) {
            // Primer request
            $data = [
                'requests' => 1,
                'window_start' => $now,
                'timestamps' => [$now],
            ];
            set_transient($key, $data, $limits['window'] * 2);
            
            // Guardar info para headers
            $this->store_current_limits($limits, 1);
            
            return true;
        }

        // Sliding window: filtrar timestamps antiguos
        $window_start = $now - $limits['window'];
        $data['timestamps'] = array_filter($data['timestamps'], function($ts) use ($window_start) {
            return $ts >= $window_start;
        });

        // Contar requests en la ventana actual
        $current_requests = count($data['timestamps']);

        // Verificar límite
        if ($current_requests >= $limits['requests']) {
            // Calcular tiempo de espera
            $oldest = min($data['timestamps']);
            $retry_after = ($oldest + $limits['window']) - $now;
            $retry_after = max(1, $retry_after);

            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    __('Límite de peticiones excedido. Espera %d segundos.', 'flavor-chat-ia'),
                    $retry_after
                ),
                [
                    'status' => 429,
                    'retry_after' => $retry_after,
                    'limit' => $limits['requests'],
                    'remaining' => 0,
                    'reset' => $oldest + $limits['window'],
                ]
            );
        }

        // Incrementar
        $data['timestamps'][] = $now;
        $data['requests'] = count($data['timestamps']);
        set_transient($key, $data, $limits['window'] * 2);

        // Guardar info para headers
        $remaining = $limits['requests'] - count($data['timestamps']);
        $this->store_current_limits($limits, count($data['timestamps']), $remaining);

        return true;
    }

    /**
     * Guarda límites actuales para headers
     */
    private function store_current_limits($limits, $used, $remaining = null) {
        global $flavor_rate_limit_info;
        
        $oldest_timestamp = time();
        if (isset($this->current_data['timestamps']) && !empty($this->current_data['timestamps'])) {
            $oldest_timestamp = min($this->current_data['timestamps']);
        }

        $flavor_rate_limit_info = [
            'limit' => $limits['requests'],
            'remaining' => $remaining ?? ($limits['requests'] - $used),
            'reset' => $oldest_timestamp + $limits['window'],
            'window' => $limits['window'],
        ];
    }

    /**
     * Añade headers de rate limit a la respuesta
     */
    public function add_rate_limit_headers($response, $server, $request) {
        global $flavor_rate_limit_info;

        if (!empty($flavor_rate_limit_info)) {
            $response->header('X-RateLimit-Limit', $flavor_rate_limit_info['limit']);
            $response->header('X-RateLimit-Remaining', max(0, $flavor_rate_limit_info['remaining']));
            $response->header('X-RateLimit-Reset', $flavor_rate_limit_info['reset']);
            $response->header('X-RateLimit-Window', $flavor_rate_limit_info['window']);
        }

        return $response;
    }

    /**
     * Registra una violación
     */
    private function record_violation($ip) {
        $key = self::TRANSIENT_PREFIX . 'violations_' . md5($ip);
        $violations = get_transient($key) ?: 0;
        $violations++;

        if ($violations >= self::VIOLATIONS_BEFORE_BAN) {
            $this->ban_ip($ip);
            delete_transient($key);
        } else {
            set_transient($key, $violations, 600); // 10 minutos
        }

        // Log
        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log(
                sprintf('Rate limit violation #%d from IP: %s', $violations, $ip),
                'warning'
            );
        }
    }

    /**
     * Banea una IP temporalmente
     */
    private function ban_ip($ip) {
        $key = self::TRANSIENT_PREFIX . 'banned_' . md5($ip);
        set_transient($key, true, self::BAN_DURATION);

        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log(
                sprintf('IP banned for %d seconds: %s', self::BAN_DURATION, $ip),
                'warning'
            );
        }
    }

    /**
     * Verifica si IP está baneada
     */
    private function is_banned($ip) {
        $key = self::TRANSIENT_PREFIX . 'banned_' . md5($ip);
        return get_transient($key) === true;
    }

    /**
     * Verifica si IP está en whitelist
     */
    private function is_whitelisted($ip) {
        return in_array($ip, $this->ip_whitelist, true);
    }

    /**
     * Genera clave para transient
     */
    private function generate_key($ip, $method, $client_type) {
        return self::TRANSIENT_PREFIX . md5($ip . '_' . $method . '_' . $client_type);
    }

    /**
     * Obtiene IP del cliente
     */
    public function get_client_ip() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $value = sanitize_text_field(wp_unslash($_SERVER[$header]));
                
                if ($header === 'HTTP_X_FORWARDED_FOR') {
                    $ips = explode(',', $value);
                    $ip = trim($ips[0]);
                } else {
                    $ip = trim($value);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '';
    }

    // =========================================================================
    // ENDPOINTS DE ADMINISTRACIÓN
    // =========================================================================

    /**
     * Registra endpoints admin
     */
    public function register_admin_endpoints() {
        register_rest_route('flavor-app/v2', '/rate-limit/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route('flavor-app/v2', '/rate-limit/config', [
            'methods' => 'GET',
            'callback' => [$this, 'get_config'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route('flavor-app/v2', '/rate-limit/config', [
            'methods' => 'POST',
            'callback' => [$this, 'update_config'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route('flavor-app/v2', '/rate-limit/whitelist', [
            'methods' => 'POST',
            'callback' => [$this, 'manage_whitelist'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route('flavor-app/v2', '/rate-limit/unban', [
            'methods' => 'POST',
            'callback' => [$this, 'unban_ip'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    /**
     * Verifica permisos admin
     */
    public function check_admin_permission($request) {
        $api_key = $request->get_header('X-VBP-Key');
        return $api_key === 'flavor-vbp-2024';
    }

    /**
     * GET /rate-limit/status
     */
    public function get_status($request) {
        global $wpdb;

        // Obtener transients de rate limit
        $transients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 LIMIT 100",
                '_transient_' . self::TRANSIENT_PREFIX . '%'
            )
        );

        $active_limits = [];
        $banned_count = 0;

        foreach ($transients as $t) {
            $key = str_replace('_transient_', '', $t->option_name);
            
            if (strpos($key, 'banned_') !== false) {
                $banned_count++;
            } else if (strpos($key, 'violations_') === false) {
                $data = maybe_unserialize($t->option_value);
                if (is_array($data) && isset($data['requests'])) {
                    $active_limits[] = [
                        'key' => $key,
                        'requests' => $data['requests'],
                        'window_start' => date('c', $data['window_start'] ?? time()),
                    ];
                }
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'active_limits' => count($active_limits),
            'banned_ips' => $banned_count,
            'limits_detail' => array_slice($active_limits, 0, 20),
            'default_limits' => $this->default_limits,
            'whitelisted_ips' => count($this->ip_whitelist),
        ]);
    }

    /**
     * GET /rate-limit/config
     */
    public function get_config($request) {
        return new WP_REST_Response([
            'success' => true,
            'default_limits' => $this->default_limits,
            'endpoint_limits' => $this->endpoint_limits,
            'ip_whitelist' => $this->ip_whitelist,
            'violations_before_ban' => self::VIOLATIONS_BEFORE_BAN,
            'ban_duration' => self::BAN_DURATION,
        ]);
    }

    /**
     * POST /rate-limit/config
     */
    public function update_config($request) {
        $limits = $request->get_param('limits');
        $endpoint_limits = $request->get_param('endpoint_limits');
        $ip_whitelist = $request->get_param('ip_whitelist');

        $config = get_option(self::OPTION_CONFIG, []);

        if ($limits) {
            $config['limits'] = $limits;
        }
        if ($endpoint_limits) {
            $config['endpoint_limits'] = $endpoint_limits;
        }
        if ($ip_whitelist) {
            $config['ip_whitelist'] = array_map('sanitize_text_field', $ip_whitelist);
        }

        update_option(self::OPTION_CONFIG, $config);
        $this->load_config();

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Configuración actualizada',
            'config' => $config,
        ]);
    }

    /**
     * POST /rate-limit/whitelist
     */
    public function manage_whitelist($request) {
        $action = $request->get_param('action'); // add, remove
        $ip = sanitize_text_field($request->get_param('ip'));

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'IP inválida',
            ], 400);
        }

        $config = get_option(self::OPTION_CONFIG, []);
        $config['ip_whitelist'] = $config['ip_whitelist'] ?? [];

        if ($action === 'add') {
            if (!in_array($ip, $config['ip_whitelist'])) {
                $config['ip_whitelist'][] = $ip;
            }
        } else if ($action === 'remove') {
            $config['ip_whitelist'] = array_filter($config['ip_whitelist'], function($i) use ($ip) {
                return $i !== $ip;
            });
        }

        update_option(self::OPTION_CONFIG, $config);
        $this->load_config();

        return new WP_REST_Response([
            'success' => true,
            'message' => $action === 'add' ? 'IP añadida a whitelist' : 'IP removida de whitelist',
            'whitelist' => $config['ip_whitelist'],
        ]);
    }

    /**
     * POST /rate-limit/unban
     */
    public function unban_ip($request) {
        $ip = sanitize_text_field($request->get_param('ip'));

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'IP inválida',
            ], 400);
        }

        $key = self::TRANSIENT_PREFIX . 'banned_' . md5($ip);
        delete_transient($key);

        // También limpiar violaciones
        $violations_key = self::TRANSIENT_PREFIX . 'violations_' . md5($ip);
        delete_transient($violations_key);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'IP desbaneada',
            'ip' => $ip,
        ]);
    }
}

// Inicializar
Flavor_API_Rate_Limiter_V2::get_instance();
