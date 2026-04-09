<?php
/**
 * Sistema de versionado de API con deprecation
 *
 * @package Flavor_Chat_IA
 * @subpackage API
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar versiones de API y deprecation
 */
class Flavor_API_Versioning {

    /**
     * Versión actual de la API
     */
    const CURRENT_VERSION = '2.0';

    /**
     * Versiones soportadas
     */
    const SUPPORTED_VERSIONS = ['1.0', '1.1', '2.0'];

    /**
     * Versión mínima soportada
     */
    const MIN_SUPPORTED_VERSION = '1.0';

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Endpoints deprecados
     */
    private $deprecated_endpoints = [];

    /**
     * Parámetros deprecados
     */
    private $deprecated_params = [];

    /**
     * Campos deprecados en respuestas
     */
    private $deprecated_fields = [];

    /**
     * Headers de deprecation enviados
     */
    private $deprecation_headers_sent = [];

    /**
     * Obtener instancia
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
        $this->register_deprecated_items();
        add_action('rest_api_init', [$this, 'register_versioning_endpoints']);
        add_filter('rest_pre_dispatch', [$this, 'handle_versioning'], 10, 3);
        add_filter('rest_post_dispatch', [$this, 'add_deprecation_headers'], 10, 3);
    }

    /**
     * Registrar items deprecados
     */
    private function register_deprecated_items() {
        // Endpoints deprecados
        $this->deprecated_endpoints = [
            // v1.0 endpoints deprecados en v2.0
            '/flavor/v1/chat' => [
                'deprecated_since' => '2.0',
                'sunset_date' => '2026-12-01',
                'replacement' => '/flavor-app/v2/chat/send',
                'message' => 'Use /flavor-app/v2/chat/send en su lugar',
            ],
            '/flavor/v1/notifications/mark-read' => [
                'deprecated_since' => '2.0',
                'sunset_date' => '2026-06-01',
                'replacement' => '/flavor-app/v2/notifications/{id}/read',
                'message' => 'Use PUT /flavor-app/v2/notifications/{id}/read',
            ],
            '/flavor/v1/config' => [
                'deprecated_since' => '1.1',
                'sunset_date' => '2026-03-01',
                'replacement' => '/flavor-app/v2/config',
                'message' => 'Endpoint legacy, use v2',
            ],
        ];

        // Parámetros deprecados
        $this->deprecated_params = [
            'session_id' => [
                'deprecated_since' => '2.0',
                'replacement' => 'conversation_id',
                'endpoints' => ['/flavor-app/v2/chat/*'],
            ],
            'page' => [
                'deprecated_since' => '2.0',
                'replacement' => 'offset',
                'endpoints' => ['/flavor-app/v2/*/list'],
                'note' => 'Use offset-based pagination',
            ],
            'include_meta' => [
                'deprecated_since' => '1.1',
                'replacement' => 'fields',
                'endpoints' => ['*'],
            ],
        ];

        // Campos deprecados en respuestas
        $this->deprecated_fields = [
            'created_at' => [
                'deprecated_since' => '2.0',
                'replacement' => 'created_date',
                'note' => 'Formato ISO 8601',
            ],
            'updated_at' => [
                'deprecated_since' => '2.0',
                'replacement' => 'modified_date',
            ],
            'user_meta' => [
                'deprecated_since' => '2.0',
                'replacement' => 'metadata',
            ],
        ];

        // Permitir extensión via filtro
        $this->deprecated_endpoints = apply_filters(
            'flavor_api_deprecated_endpoints',
            $this->deprecated_endpoints
        );
        $this->deprecated_params = apply_filters(
            'flavor_api_deprecated_params',
            $this->deprecated_params
        );
        $this->deprecated_fields = apply_filters(
            'flavor_api_deprecated_fields',
            $this->deprecated_fields
        );
    }

    /**
     * Callback de permisos públicos con rate limiting.
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function public_permission_with_rate_limit($request) {
        $client_ip = $this->get_client_ip();
        $transient_key = 'api_versioning_rate_' . md5($client_ip);
        $request_count = (int) get_transient($transient_key);
        $rate_limit = 30; // peticiones por minuto

        if ($request_count >= $rate_limit) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Demasiadas peticiones. Intente de nuevo en un minuto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 429]
            );
        }

        set_transient($transient_key, $request_count + 1, MINUTE_IN_SECONDS);

        return true;
    }

    /**
     * Obtiene la IP del cliente.
     *
     * @return string
     */
    private function get_client_ip() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip_list = explode(',', sanitize_text_field(wp_unslash($_SERVER[$header])));
                return trim($ip_list[0]);
            }
        }

        return '127.0.0.1';
    }

    /**
     * Registrar endpoints de versionado
     */
    public function register_versioning_endpoints() {
        // Información de versiones
        register_rest_route('flavor-app/v2', '/versions', [
            'methods' => 'GET',
            'callback' => [$this, 'get_versions_info'],
            'permission_callback' => [$this, 'public_permission_with_rate_limit'],
        ]);

        // Deprecations activos
        register_rest_route('flavor-app/v2', '/deprecations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_deprecations'],
            'permission_callback' => [$this, 'public_permission_with_rate_limit'],
        ]);

        // Migración automática
        register_rest_route('flavor-app/v2', '/migrate', [
            'methods' => 'POST',
            'callback' => [$this, 'suggest_migration'],
            'permission_callback' => [$this, 'admin_permission_check'],
            'args' => [
                'from_version' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'to_version' => [
                    'type' => 'string',
                    'default' => self::CURRENT_VERSION,
                ],
            ],
        ]);
    }

    /**
     * Manejar versionado en requests
     */
    public function handle_versioning($result, $server, $request) {
        $route = $request->get_route();

        // Verificar si el endpoint está deprecado
        if ($this->is_endpoint_deprecated($route)) {
            $this->log_deprecated_usage($route, $request);
        }

        // Verificar parámetros deprecados
        $params = $request->get_params();
        foreach ($params as $param => $value) {
            if ($this->is_param_deprecated($param, $route)) {
                $this->log_deprecated_param_usage($param, $route, $request);
            }
        }

        // Verificar versión solicitada
        $requested_version = $this->get_requested_version($request);
        if ($requested_version && !$this->is_version_supported($requested_version)) {
            return new WP_Error(
                'unsupported_api_version',
                sprintf(
                    'La versión de API %s no es soportada. Versiones soportadas: %s',
                    $requested_version,
                    implode(', ', self::SUPPORTED_VERSIONS)
                ),
                ['status' => 400]
            );
        }

        return $result;
    }

    /**
     * Agregar headers de deprecation
     */
    public function add_deprecation_headers($result, $server, $request) {
        $route = $request->get_route();
        $headers = [];

        // Header de deprecación de endpoint
        if ($this->is_endpoint_deprecated($route)) {
            $info = $this->deprecated_endpoints[$route] ?? null;
            if ($info && !isset($this->deprecation_headers_sent[$route])) {
                $headers['Deprecation'] = $info['deprecated_since'];
                $headers['Sunset'] = $info['sunset_date'];

                if (!empty($info['replacement'])) {
                    $link = rest_url($info['replacement']);
                    $headers['Link'] = sprintf('<%s>; rel="successor-version"', $link);
                }

                $headers['X-Deprecation-Notice'] = $info['message'];
                $this->deprecation_headers_sent[$route] = true;
            }
        }

        // Agregar headers deprecados de parámetros
        $params = $request->get_params();
        $deprecated_params_used = [];

        foreach ($params as $param => $value) {
            if ($this->is_param_deprecated($param, $route)) {
                $param_info = $this->deprecated_params[$param];
                $deprecated_params_used[] = sprintf(
                    '%s (use %s)',
                    $param,
                    $param_info['replacement']
                );
            }
        }

        if (!empty($deprecated_params_used)) {
            $headers['X-Deprecated-Params'] = implode(', ', $deprecated_params_used);
        }

        // Agregar version header
        $headers['X-API-Version'] = self::CURRENT_VERSION;
        $headers['X-API-Min-Version'] = self::MIN_SUPPORTED_VERSION;

        // Aplicar headers
        if (!empty($headers) && $result instanceof WP_REST_Response) {
            foreach ($headers as $key => $value) {
                $result->header($key, $value);
            }
        }

        return $result;
    }

    /**
     * Verificar si endpoint está deprecado
     */
    public function is_endpoint_deprecated($route) {
        // Verificar match exacto
        if (isset($this->deprecated_endpoints[$route])) {
            return true;
        }

        // Verificar patrones con wildcard
        foreach ($this->deprecated_endpoints as $pattern => $info) {
            if (strpos($pattern, '*') !== false) {
                $regex = '/^' . str_replace(['/', '*'], ['\/', '.*'], $pattern) . '$/';
                if (preg_match($regex, $route)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verificar si parámetro está deprecado
     */
    public function is_param_deprecated($param, $route) {
        if (!isset($this->deprecated_params[$param])) {
            return false;
        }

        $info = $this->deprecated_params[$param];
        $endpoints = $info['endpoints'] ?? ['*'];

        foreach ($endpoints as $pattern) {
            if ($pattern === '*') {
                return true;
            }

            $regex = '/^' . str_replace(['/', '*'], ['\/', '.*'], $pattern) . '$/';
            if (preg_match($regex, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si versión es soportada
     */
    public function is_version_supported($version) {
        return in_array($version, self::SUPPORTED_VERSIONS);
    }

    /**
     * Obtener versión solicitada
     */
    private function get_requested_version($request) {
        // Desde header
        $version = $request->get_header('X-API-Version');
        if ($version) {
            return $version;
        }

        // Desde parámetro
        $version = $request->get_param('api_version');
        if ($version) {
            return $version;
        }

        // Desde URL
        $route = $request->get_route();
        if (preg_match('/\/v(\d+(?:\.\d+)?)\//i', $route, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Loguear uso de endpoint deprecado
     */
    private function log_deprecated_usage($route, $request) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $info = $this->get_endpoint_deprecation_info($route);
        $log_message = sprintf(
            '[API Deprecation] Endpoint: %s | Deprecated since: %s | Sunset: %s | Replacement: %s | IP: %s',
            $route,
            $info['deprecated_since'] ?? 'unknown',
            $info['sunset_date'] ?? 'unknown',
            $info['replacement'] ?? 'none',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );

        error_log($log_message);

        // Incrementar contador de uso
        $usage_key = 'flavor_deprecated_usage_' . md5($route);
        $current = get_transient($usage_key) ?: 0;
        set_transient($usage_key, $current + 1, DAY_IN_SECONDS);
    }

    /**
     * Loguear uso de parámetro deprecado
     */
    private function log_deprecated_param_usage($param, $route, $request) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $info = $this->deprecated_params[$param] ?? [];
        error_log(sprintf(
            '[API Deprecation] Param: %s | Route: %s | Replacement: %s',
            $param,
            $route,
            $info['replacement'] ?? 'none'
        ));
    }

    /**
     * Obtener información de deprecation de endpoint
     */
    public function get_endpoint_deprecation_info($route) {
        if (isset($this->deprecated_endpoints[$route])) {
            return $this->deprecated_endpoints[$route];
        }

        // Buscar en patrones
        foreach ($this->deprecated_endpoints as $pattern => $info) {
            if (strpos($pattern, '*') !== false) {
                $regex = '/^' . str_replace(['/', '*'], ['\/', '.*'], $pattern) . '$/';
                if (preg_match($regex, $route)) {
                    return $info;
                }
            }
        }

        return null;
    }

    /**
     * Endpoint: Información de versiones
     */
    public function get_versions_info() {
        return rest_ensure_response([
            'current_version' => self::CURRENT_VERSION,
            'supported_versions' => self::SUPPORTED_VERSIONS,
            'min_supported_version' => self::MIN_SUPPORTED_VERSION,
            'deprecation_policy' => [
                'notice_period' => '6 months',
                'sunset_period' => '12 months after deprecation',
            ],
            'versions' => [
                '2.0' => [
                    'release_date' => '2024-01-01',
                    'status' => 'current',
                    'changes' => [
                        'Nueva estructura de respuestas',
                        'Paginación basada en offset',
                        'Autenticación mejorada',
                    ],
                ],
                '1.1' => [
                    'release_date' => '2023-06-01',
                    'status' => 'supported',
                    'sunset_date' => '2025-06-01',
                ],
                '1.0' => [
                    'release_date' => '2023-01-01',
                    'status' => 'deprecated',
                    'sunset_date' => '2024-12-01',
                ],
            ],
        ]);
    }

    /**
     * Endpoint: Deprecations activos
     */
    public function get_deprecations() {
        $endpoints = [];
        foreach ($this->deprecated_endpoints as $route => $info) {
            $endpoints[] = array_merge(['route' => $route], $info);
        }

        $params = [];
        foreach ($this->deprecated_params as $param => $info) {
            $params[] = array_merge(['param' => $param], $info);
        }

        $fields = [];
        foreach ($this->deprecated_fields as $field => $info) {
            $fields[] = array_merge(['field' => $field], $info);
        }

        return rest_ensure_response([
            'endpoints' => $endpoints,
            'parameters' => $params,
            'response_fields' => $fields,
            'total_deprecated' => count($endpoints) + count($params) + count($fields),
        ]);
    }

    /**
     * Endpoint: Sugerir migración
     */
    public function suggest_migration($request) {
        $from_version = $request->get_param('from_version');
        $to_version = $request->get_param('to_version');

        $migrations = $this->get_migration_steps($from_version, $to_version);

        return rest_ensure_response([
            'from_version' => $from_version,
            'to_version' => $to_version,
            'migrations' => $migrations,
            'breaking_changes' => $this->get_breaking_changes($from_version, $to_version),
            'documentation_url' => home_url('/wp-json/flavor-app/v2/docs'),
        ]);
    }

    /**
     * Obtener pasos de migración
     */
    private function get_migration_steps($from, $to) {
        $steps = [];

        if (version_compare($from, '2.0', '<') && version_compare($to, '2.0', '>=')) {
            $steps[] = [
                'action' => 'update_endpoints',
                'description' => 'Actualizar URLs de endpoints de /flavor/v1/ a /flavor-app/v2/',
                'examples' => [
                    '/flavor/v1/chat' => '/flavor-app/v2/chat/send',
                    '/flavor/v1/notifications' => '/flavor-app/v2/notifications',
                ],
            ];

            $steps[] = [
                'action' => 'update_pagination',
                'description' => 'Cambiar paginación de page/per_page a offset/limit',
                'examples' => [
                    'before' => '?page=2&per_page=20',
                    'after' => '?offset=20&limit=20',
                ],
            ];

            $steps[] = [
                'action' => 'update_auth',
                'description' => 'Usar header X-VBP-Key para autenticación de API',
            ];

            $steps[] = [
                'action' => 'update_response_handling',
                'description' => 'Adaptar manejo de respuestas al nuevo formato',
                'examples' => [
                    'before' => '{"data": [...], "meta": {...}}',
                    'after' => '{"success": true, "data": [...], "pagination": {...}}',
                ],
            ];
        }

        return $steps;
    }

    /**
     * Obtener cambios breaking
     */
    private function get_breaking_changes($from, $to) {
        $changes = [];

        if (version_compare($from, '2.0', '<') && version_compare($to, '2.0', '>=')) {
            $changes = [
                [
                    'type' => 'endpoint_removed',
                    'endpoint' => '/flavor/v1/legacy/*',
                    'solution' => 'Usar endpoints equivalentes en v2',
                ],
                [
                    'type' => 'response_format_changed',
                    'description' => 'Formato de respuesta estandarizado',
                    'solution' => 'Actualizar parsers de respuesta',
                ],
                [
                    'type' => 'auth_method_changed',
                    'description' => 'Cookie auth deprecada para apps móviles',
                    'solution' => 'Usar JWT o X-VBP-Key header',
                ],
            ];
        }

        return $changes;
    }

    /**
     * Verificar permisos de admin
     */
    public function admin_permission_check() {
        return current_user_can('manage_options');
    }

    /**
     * Deprecar un endpoint programáticamente
     */
    public function deprecate_endpoint($route, $info) {
        $this->deprecated_endpoints[$route] = array_merge([
            'deprecated_since' => self::CURRENT_VERSION,
            'sunset_date' => date('Y-m-d', strtotime('+12 months')),
        ], $info);
    }

    /**
     * Deprecar un parámetro programáticamente
     */
    public function deprecate_param($param, $info) {
        $this->deprecated_params[$param] = array_merge([
            'deprecated_since' => self::CURRENT_VERSION,
        ], $info);
    }

    /**
     * Verificar si endpoint está próximo a sunset
     */
    public function is_approaching_sunset($route, $days_threshold = 30) {
        $info = $this->get_endpoint_deprecation_info($route);
        if (!$info || empty($info['sunset_date'])) {
            return false;
        }

        $sunset = strtotime($info['sunset_date']);
        $threshold = strtotime("+{$days_threshold} days");

        return $sunset <= $threshold;
    }

    /**
     * Obtener endpoints próximos a sunset
     */
    public function get_approaching_sunset_endpoints($days = 30) {
        $approaching = [];

        foreach ($this->deprecated_endpoints as $route => $info) {
            if ($this->is_approaching_sunset($route, $days)) {
                $approaching[] = array_merge(['route' => $route], $info);
            }
        }

        return $approaching;
    }
}

// Inicializar
Flavor_API_Versioning::get_instance();

/**
 * Función helper para deprecar endpoints
 */
function flavor_deprecate_endpoint($route, $replacement, $sunset_date = null) {
    Flavor_API_Versioning::get_instance()->deprecate_endpoint($route, [
        'replacement' => $replacement,
        'sunset_date' => $sunset_date ?? date('Y-m-d', strtotime('+12 months')),
        'message' => "Este endpoint está deprecado. Use {$replacement} en su lugar.",
    ]);
}

/**
 * Función helper para deprecar parámetros
 */
function flavor_deprecate_param($param, $replacement, $endpoints = ['*']) {
    Flavor_API_Versioning::get_instance()->deprecate_param($param, [
        'replacement' => $replacement,
        'endpoints' => $endpoints,
    ]);
}
