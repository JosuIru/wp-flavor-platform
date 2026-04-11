<?php
/**
 * API de Federación para Red Social
 *
 * Endpoints para enviar y recibir publicaciones entre nodos.
 *
 * @package FlavorPlatform
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Federation_API {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'flavor-integration/v1';

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
        add_action('rest_api_init', [$this, 'registrar_rutas']);

        // Registrar timestamp de inicio para uptime
        if (!get_option('flavor_network_started_at')) {
            update_option('flavor_network_started_at', time());
        }
    }

    // ═══════════════════════════════════════════════════════════
    // LOGGING DE EVENTOS DE FEDERACION
    // ═══════════════════════════════════════════════════════════

    /**
     * Registra un evento de federacion para observabilidad
     *
     * @param string $level   Nivel del log: 'info', 'warning', 'error', 'critical'
     * @param string $message Mensaje descriptivo del evento
     * @param array  $context Contexto adicional (nodo_id, endpoint, etc.)
     */
    private function log_federation_event($level, $message, $context = []) {
        if (!apply_filters('flavor_network_logging_enabled', true)) {
            return;
        }

        $log_entry = [
            'timestamp'  => current_time('c'),
            'level'      => $level,
            'message'    => $message,
            'context'    => $context,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ];

        // Guardar en option (ultimos 500 logs)
        $logs = get_option('flavor_network_federation_logs', []);
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 500);
        update_option('flavor_network_federation_logs', $logs);

        // Tambien a error_log si esta en modo debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[FederationAPI][{$level}] {$message} " . wp_json_encode($context));
        }

        // Hook para extensibilidad (alertas externas, etc.)
        do_action('flavor_federation_event_logged', $level, $message, $context, $log_entry);
    }

    /**
     * Obtiene los logs de federacion
     *
     * @param int    $limit Numero maximo de logs a devolver
     * @param string $level Filtrar por nivel (opcional)
     * @return array
     */
    public function get_federation_logs($limit = 100, $level = null) {
        $logs = get_option('flavor_network_federation_logs', []);

        if ($level) {
            $logs = array_filter($logs, fn($log) => $log['level'] === $level);
        }

        return array_slice($logs, 0, $limit);
    }

    /**
     * Limpia los logs de federacion
     */
    public function clear_federation_logs() {
        delete_option('flavor_network_federation_logs');
    }

    // ═══════════════════════════════════════════════════════════
    // SISTEMA DE CACHE PARA QUERIES FEDERADAS
    // ═══════════════════════════════════════════════════════════

    /**
     * TTL por defecto para cache de datos federados (15 minutos)
     */
    const CACHE_TTL_DEFAULT = 900;

    /**
     * TTL corto para datos que cambian frecuentemente (5 minutos)
     */
    const CACHE_TTL_SHORT = 300;

    /**
     * TTL largo para datos estáticos (1 hora)
     */
    const CACHE_TTL_LONG = 3600;

    /**
     * Obtiene datos desde cache o ejecuta el callback para obtenerlos
     *
     * @param string   $cache_key Clave única para el cache
     * @param callable $callback  Función que obtiene los datos si no están en cache
     * @param int      $ttl       Tiempo de vida del cache en segundos
     * @return mixed Datos cacheados o recién obtenidos
     */
    private function get_cached_federated_data($cache_key, $callback, $ttl = self::CACHE_TTL_DEFAULT) {
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            return $cached_data;
        }

        $fresh_data = $callback();
        set_transient($cache_key, $fresh_data, $ttl);

        return $fresh_data;
    }

    /**
     * Genera una clave de cache basada en los parámetros de búsqueda
     *
     * @param string $prefix     Prefijo identificador del tipo de consulta
     * @param array  $parameters Parámetros de la consulta
     * @return string Clave de cache única
     */
    private function generate_cache_key($prefix, $parameters = []) {
        // Filtrar solo parámetros relevantes y ordenarlos
        $filtered_params = array_filter($parameters, function($value) {
            return $value !== null && $value !== '' && $value !== 0;
        });
        ksort($filtered_params);

        $params_hash = md5(serialize($filtered_params));

        return 'flavor_fed_' . $prefix . '_' . $params_hash;
    }

    /**
     * Invalida cache de un tipo específico
     *
     * @param string $prefix Prefijo del tipo de cache a invalidar (vacío = todo)
     */
    public function invalidate_cache($prefix = '') {
        global $wpdb;

        if (empty($prefix)) {
            // Invalidar todo el cache de federación
            $wpdb->query(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE '_transient_flavor_fed_%'
                    OR option_name LIKE '_transient_timeout_flavor_fed_%'"
            );
        } else {
            // Invalidar cache específico
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                    OR option_name LIKE %s",
                '_transient_flavor_fed_' . $prefix . '_%',
                '_transient_timeout_flavor_fed_' . $prefix . '_%'
            ));
        }

        $this->log_federation_event('info', 'Cache invalidado', ['prefix' => $prefix ?: 'all']);
    }

    /**
     * Registra las rutas de la API
     */
    public function registrar_rutas() {
        // Recibir publicación federada
        register_rest_route(self::API_NAMESPACE, '/federation/receive', [
            'methods'             => 'POST',
            'callback'            => [$this, 'recibir_publicacion'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // Verificar estado del nodo
        register_rest_route(self::API_NAMESPACE, '/federation/ping', [
            'methods'             => 'GET',
            'callback'            => [$this, 'ping'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener publicaciones públicas para federar
        register_rest_route(self::API_NAMESPACE, '/federation/feed', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_feed_federado'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Productores Federados ===

        // Listar productores compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/producers', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_productores_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => [
                    'type'              => 'number',
                    'sanitize_callback' => 'floatval',
                ],
                'lng' => [
                    'type'              => 'number',
                    'sanitize_callback' => 'floatval',
                ],
                'mensajeria' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limite' => [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // Detalle de un productor
        register_rest_route(self::API_NAMESPACE, '/federation/producers/(?P<slug>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_productor_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_title',
                ],
                'nodo_id' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // Contactar a un productor
        register_rest_route(self::API_NAMESPACE, '/federation/producers/contact', [
            'methods'             => 'POST',
            'callback'            => [$this, 'contactar_productor'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Eventos Federados ===

        // Listar eventos compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/events', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_eventos_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'desde' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un evento
        register_rest_route(self::API_NAMESPACE, '/federation/events/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_evento_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Carpooling Federado ===

        // Listar viajes compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/carpooling', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_viajes_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'origen_lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'origen_lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'destino_lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'destino_lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'desde' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un viaje
        register_rest_route(self::API_NAMESPACE, '/federation/carpooling/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_viaje_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Talleres Federados ===

        // Listar talleres compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/workshops', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_talleres_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'categoria' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un taller
        register_rest_route(self::API_NAMESPACE, '/federation/workshops/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_taller_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Espacios Comunes Federados ===

        // Listar espacios compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/spaces', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_espacios_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'tipo' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'capacidad_min' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un espacio
        register_rest_route(self::API_NAMESPACE, '/federation/spaces/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_espacio_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Marketplace Federado ===

        // Listar anuncios compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/marketplace', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_anuncios_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'tipo' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'categoria' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un anuncio
        register_rest_route(self::API_NAMESPACE, '/federation/marketplace/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_anuncio_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Banco de Tiempo Federado ===

        // Listar servicios del banco de tiempo compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/timebank', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_servicios_tiempo_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'tipo' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'categoria' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'modalidad' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un servicio de tiempo
        register_rest_route(self::API_NAMESPACE, '/federation/timebank/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_servicio_tiempo_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Cursos Federados ===

        // Listar cursos compartidos en la red
        register_rest_route(self::API_NAMESPACE, '/federation/courses', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_cursos_federados'],
            'permission_callback' => [$this, 'verificar_nodo'],
            'args'                => [
                'lat' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'lng' => ['type' => 'number', 'sanitize_callback' => 'floatval'],
                'radio' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'categoria' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'nivel' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'modalidad' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'gratuitos' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'limite' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Detalle de un curso
        register_rest_route(self::API_NAMESPACE, '/federation/courses/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'obtener_curso_detalle'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Batch Sync (Sincronizacion masiva) ===
        register_rest_route(self::API_NAMESPACE, '/federation/batch/sync', [
            'methods'             => 'POST',
            'callback'            => [$this, 'batch_sync'],
            'permission_callback' => [$this, 'verificar_nodo'],
        ]);

        // === Health Check del Sistema de Federacion ===
        register_rest_route(self::API_NAMESPACE, '/federation/system/health', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_system_health'],
            'permission_callback' => '__return_true',
        ]);

        // === Logs de Federacion (solo admin) ===
        register_rest_route(self::API_NAMESPACE, '/federation/system/logs', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_logs_endpoint'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => [
                'limit' => ['type' => 'integer', 'default' => 100, 'sanitize_callback' => 'absint'],
                'level' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // HEALTH CHECK DEL SISTEMA
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Health check del sistema de federacion
     *
     * Devuelve estado general del nodo, tablas, conexiones y estadisticas.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_system_health($request) {
        global $wpdb;

        // Obtener nodo local
        $nodo_local = null;
        $nodo_local_data = null;
        if (class_exists('Flavor_Network_Node')) {
            $nodo_local = Flavor_Network_Node::get_local_node();
        }

        // Prefijo de tablas de red
        $prefix = $wpdb->prefix . 'flavor_network_';

        // Verificar tablas criticas
        $tablas_requeridas = ['nodes', 'connections', 'shared_content', 'events'];
        $tablas_estado = [];
        $todas_tablas_ok = true;

        foreach ($tablas_requeridas as $tabla) {
            $tabla_completa = $prefix . $tabla;
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $tabla_completa
            ));
            $tablas_estado[$tabla] = $existe ? 'ok' : 'missing';
            if (!$existe) {
                $todas_tablas_ok = false;
            }
        }

        // Estadisticas de la red
        $stats = [
            'nodes_count'       => 0,
            'connections_count' => 0,
            'pending_webhooks'  => count(get_option('flavor_network_webhook_queue', [])),
        ];

        // Contar nodos activos
        $tabla_nodos = $prefix . 'nodes';
        if ($tablas_estado['nodes'] === 'ok') {
            $stats['nodes_count'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_nodos} WHERE activo = 1"
            );
        }

        // Contar conexiones aprobadas
        $tabla_conexiones = $prefix . 'connections';
        if ($tablas_estado['connections'] === 'ok') {
            $stats['connections_count'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_conexiones} WHERE estado = 'aprobada'"
            );
        }

        // Calcular uptime
        $started_at = (int) get_option('flavor_network_started_at', time());
        $uptime_seconds = time() - $started_at;

        // Logs recientes (ultimas 24h con errores)
        $logs = get_option('flavor_network_federation_logs', []);
        $errores_recientes = 0;
        $hace_24h = strtotime('-24 hours');
        foreach (array_slice($logs, 0, 100) as $log) {
            $log_time = strtotime($log['timestamp'] ?? '');
            if ($log_time >= $hace_24h && in_array($log['level'] ?? '', ['error', 'critical'])) {
                $errores_recientes++;
            }
        }

        // Determinar estado general
        $status = 'healthy';
        $status_code = 200;

        if (!$todas_tablas_ok) {
            $status = 'degraded';
            $status_code = 503;
        } elseif ($errores_recientes > 10) {
            $status = 'warning';
        }

        $health = [
            'status'          => $status,
            'timestamp'       => current_time('c'),
            'version'         => defined('FLAVOR_NETWORK_VERSION') ? FLAVOR_NETWORK_VERSION : (defined('FLAVOR_PLATFORM_VERSION') ? FLAVOR_PLATFORM_VERSION : '1.0.0'),
            'node_id'         => $nodo_local ? $nodo_local->id : null,
            'node_name'       => $nodo_local ? $nodo_local->nombre : get_bloginfo('name'),
            'node_url'        => home_url(),
            'database'        => [
                'status' => $todas_tablas_ok ? 'ok' : 'missing_tables',
                'tables' => $tablas_estado,
            ],
            'stats'           => $stats,
            'uptime_seconds'  => $uptime_seconds,
            'uptime_human'    => $this->format_uptime($uptime_seconds),
            'errors_24h'      => $errores_recientes,
            'php_version'     => PHP_VERSION,
            'wp_version'      => get_bloginfo('version'),
        ];

        return new WP_REST_Response($health, $status_code);
    }

    /**
     * Formatea el uptime en formato legible
     *
     * @param int $seconds
     * @return string
     */
    private function format_uptime($seconds) {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";

        return implode(' ', $parts) ?: '< 1m';
    }

    /**
     * Endpoint: Obtener logs de federacion
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_logs_endpoint($request) {
        $limit = min(500, $request->get_param('limit') ?: 100);
        $level = $request->get_param('level');

        $logs = $this->get_federation_logs($limit, $level);

        return new WP_REST_Response([
            'logs'  => $logs,
            'total' => count($logs),
            'limit' => $limit,
            'level' => $level,
        ], 200);
    }

    /**
     * Verifica que la petición viene de un nodo autorizado
     */
    public function verificar_nodo($request) {
        global $wpdb;

        $origen = $request->get_header('X-Origin-Node');
        $token = $request->get_header('X-Node-Token');
        $endpoint = $request->get_route();

        if (empty($origen)) {
            $this->log_federation_event('warning', 'Peticion sin header X-Origin-Node', [
                'endpoint' => $endpoint,
            ]);
            return new WP_Error('sin_origen', 'Falta header X-Origin-Node', ['status' => 401]);
        }

        // Verificar si el nodo está registrado y activo
        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';
        $nodo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_nodos} WHERE site_url = %s AND activo = 1",
            $origen
        ));

        if (!$nodo) {
            // Permitir nodos no registrados pero marcar la petición
            $this->log_federation_event('info', 'Peticion de nodo no registrado', [
                'origen'   => $origen,
                'endpoint' => $endpoint,
            ]);
            $request->set_param('_nodo_no_registrado', true);
            return true;
        }

        // Verificar token si existe
        if (!empty($nodo->token) && $nodo->token !== $token) {
            $this->log_federation_event('error', 'Token de nodo invalido', [
                'nodo_id'  => $nodo->id,
                'origen'   => $origen,
                'endpoint' => $endpoint,
            ]);
            return new WP_Error('token_invalido', 'Token de nodo inválido', ['status' => 403]);
        }

        // Log de conexion exitosa (solo si debug esta activo)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log_federation_event('info', 'Nodo autenticado correctamente', [
                'nodo_id'  => $nodo->id,
                'origen'   => $origen,
                'endpoint' => $endpoint,
            ]);
        }

        $request->set_param('_nodo_id', $nodo->id);
        $request->set_param('_nodo_data', $nodo);

        return true;
    }

    // ═══════════════════════════════════════════════════════════
    // VALIDACIONES DE SEGURIDAD (P1, P5, P11)
    // ═══════════════════════════════════════════════════════════

    /**
     * P1 - Valida coordenadas geográficas para prevenir SQL injection en fórmulas Haversine
     *
     * @param mixed $lat Latitud a validar
     * @param mixed $lng Longitud a validar
     * @return array|false Array con coordenadas validadas o false si son inválidas
     */
    private function validate_coordinates($lat, $lng) {
        $lat = floatval($lat);
        $lng = floatval($lng);

        // Validar rangos geográficos válidos
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }

        // Validar que no sean NaN o infinitos
        if (!is_finite($lat) || !is_finite($lng)) {
            return false;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * P5 - Valida el payload de contenido federado antes de insertar
     *
     * @param string $tipo Tipo de contenido (evento, productor, producto, etc.)
     * @param array $payload Datos del contenido
     * @return true|WP_Error True si es válido, WP_Error si hay problemas
     */
    private function validate_federated_payload($tipo, $payload) {
        // Campos requeridos por tipo de contenido
        $campos_requeridos = [
            'evento'       => ['titulo', 'fecha_inicio'],
            'productor'    => ['nombre'],
            'producto'     => ['titulo'],
            'publicacion'  => ['contenido'],
            'taller'       => ['titulo'],
            'espacio'      => ['nombre'],
            'anuncio'      => ['titulo'],
            'servicio'     => ['titulo'],
            'curso'        => ['titulo'],
            'viaje'        => ['origen', 'destino'],
            'default'      => ['titulo'],
        ];

        $requeridos = $campos_requeridos[$tipo] ?? $campos_requeridos['default'];

        // Verificar campos requeridos
        foreach ($requeridos as $campo) {
            if (empty($payload[$campo])) {
                return new WP_Error(
                    'campo_requerido',
                    sprintf(__('Campo "%s" es requerido para tipo "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN), $campo, $tipo),
                    ['status' => 400]
                );
            }
        }

        // Límite de tamaño: máximo 64KB por campo de texto
        $max_field_size = 65536;
        foreach ($payload as $key => $value) {
            if (is_string($value) && strlen($value) > $max_field_size) {
                return new WP_Error(
                    'campo_muy_largo',
                    sprintf(__('Campo "%s" excede el límite de %d bytes', FLAVOR_PLATFORM_TEXT_DOMAIN), $key, $max_field_size),
                    ['status' => 400]
                );
            }

            // Validar arrays/objetos anidados también
            if (is_array($value)) {
                $serialized_size = strlen(wp_json_encode($value));
                if ($serialized_size > $max_field_size) {
                    return new WP_Error(
                        'campo_muy_largo',
                        sprintf(__('Campo "%s" serializado excede el límite de %d bytes', FLAVOR_PLATFORM_TEXT_DOMAIN), $key, $max_field_size),
                        ['status' => 400]
                    );
                }
            }
        }

        // Validar que no haya caracteres peligrosos en campos críticos
        $campos_criticos = ['titulo', 'nombre', 'slug'];
        foreach ($campos_criticos as $campo) {
            if (isset($payload[$campo]) && is_string($payload[$campo])) {
                // Detectar posibles inyecciones de scripts
                if (preg_match('/<script|javascript:|onclick|onerror|onload/i', $payload[$campo])) {
                    return new WP_Error(
                        'contenido_no_permitido',
                        sprintf(__('Campo "%s" contiene contenido no permitido', FLAVOR_PLATFORM_TEXT_DOMAIN), $campo),
                        ['status' => 400]
                    );
                }
            }
        }

        return true;
    }

    /**
     * P11 - Aplica rate limiting a endpoints federados
     *
     * @return true|WP_Error True si se permite, WP_Error si se excede el límite
     */
    private function enforce_federation_rate_limit() {
        // Usar rate limiter si está disponible
        if (class_exists('Flavor_Network_Rate_Limiter')) {
            $limiter = Flavor_Network_Rate_Limiter::get_instance();
            return $limiter->enforce_rate_limit('federation');
        }

        // Fallback: rate limiting básico con transients
        $client_ip = $this->get_client_ip();
        $transient_key = 'flavor_fed_rate_' . md5($client_ip);
        $current_count = (int) get_transient($transient_key);

        // Límite: 100 requests por minuto
        $max_requests = apply_filters('flavor_federation_rate_limit', 100);
        $window_seconds = 60;

        if ($current_count >= $max_requests) {
            $this->log_federation_event('warning', 'Rate limit excedido', [
                'ip' => $client_ip,
                'count' => $current_count,
            ]);
            return new WP_Error(
                'rate_limit_exceeded',
                __('Demasiadas solicitudes. Por favor, espera antes de reintentar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 429, 'retry_after' => $window_seconds]
            );
        }

        set_transient($transient_key, $current_count + 1, $window_seconds);
        return true;
    }

    /**
     * Obtiene la IP del cliente de forma segura
     *
     * @return string IP del cliente
     */
    private function get_client_ip() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip_value = $_SERVER[$header];
                // X-Forwarded-For puede contener múltiples IPs
                if (strpos($ip_value, ',') !== false) {
                    $ip_value = trim(explode(',', $ip_value)[0]);
                }
                if (filter_var($ip_value, FILTER_VALIDATE_IP)) {
                    return $ip_value;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * P1 - Genera la fórmula Haversine de forma segura con coordenadas validadas
     *
     * @param float $lat Latitud validada
     * @param float $lng Longitud validada
     * @param string $lat_column Nombre de la columna de latitud en la tabla
     * @param string $lng_column Nombre de la columna de longitud en la tabla
     * @return string Fórmula SQL Haversine
     */
    private function build_haversine_formula($lat, $lng, $lat_column = 'latitud', $lng_column = 'longitud') {
        // Las coordenadas ya deben estar validadas con validate_coordinates()
        return sprintf(
            "(6371 * acos(
                cos(radians(%f)) *
                cos(radians(%s)) *
                cos(radians(%s) - radians(%f)) +
                sin(radians(%f)) *
                sin(radians(%s))
            ))",
            $lat,
            esc_sql($lat_column),
            esc_sql($lng_column),
            $lng,
            $lat,
            esc_sql($lat_column)
        );
    }

    // ═══════════════════════════════════════════════════════════
    // ENDPOINTS PÚBLICOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Recibir publicación federada
     *
     * Usa transacciones para garantizar integridad en operaciones críticas.
     */
    public function recibir_publicacion($request) {
        global $wpdb;

        // P11 - Aplicar rate limiting
        $rate_check = $this->enforce_federation_rate_limit();
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }

        $body = $request->get_json_params();

        if (empty($body['tipo']) || $body['tipo'] !== 'publicacion_compartida') {
            $this->log_federation_event('warning', 'Tipo de contenido no soportado', ['tipo' => $body['tipo'] ?? 'null']);
            return new WP_Error('tipo_invalido', 'Tipo de contenido no soportado', ['status' => 400]);
        }

        if (empty($body['publicacion'])) {
            $this->log_federation_event('warning', 'Falta contenido de publicación');
            return new WP_Error('sin_contenido', 'Falta contenido de publicación', ['status' => 400]);
        }

        $publicacion_datos = $body['publicacion'];

        // P5 - Validar payload federado
        $payload_validation = $this->validate_federated_payload('publicacion', $publicacion_datos);
        if (is_wp_error($payload_validation)) {
            $this->log_federation_event('warning', 'Payload de publicación inválido', [
                'error' => $payload_validation->get_error_message(),
            ]);
            return $payload_validation;
        }
        $origen = $body['origen'] ?? '';
        $nodo_id = $request->get_param('_nodo_id');

        // Verificar si ya existe esta publicación (evitar duplicados)
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $enlace_url = esc_url_raw($publicacion_datos['enlace_url'] ?? '');

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_publicaciones} WHERE enlace_url = %s",
            $enlace_url
        ));

        if ($existe) {
            $this->log_federation_event('info', 'Publicación duplicada ignorada', ['origen' => $origen, 'url' => $enlace_url]);
            return [
                'success'   => true,
                'message'   => 'Publicación ya existente',
                'duplicado' => true,
            ];
        }

        // Obtener o crear usuario "nodo federado"
        $nombre_autor = $publicacion_datos['autor_nombre'] ?? 'Nodo Federado';
        $usuario_federado = $this->obtener_usuario_federado($origen, $nombre_autor);

        // Iniciar transacción para operación crítica
        $wpdb->query('START TRANSACTION');

        try {
            // Insertar publicación principal
            $datos_publicacion = [
                'usuario_id'         => $usuario_federado,
                'contenido'          => sanitize_textarea_field($publicacion_datos['contenido'] ?? ''),
                'tipo'               => 'enlace',
                'enlace_url'         => $enlace_url,
                'enlace_titulo'      => sanitize_text_field($publicacion_datos['enlace_titulo'] ?? ''),
                'enlace_descripcion' => sanitize_textarea_field($publicacion_datos['enlace_descripcion'] ?? ''),
                'enlace_imagen'      => esc_url_raw($publicacion_datos['enlace_imagen'] ?? ''),
                'privacidad'         => 'publico',
                'estado'             => 'publicado',
                'fecha_creacion'     => current_time('mysql'),
            ];

            $insertado = $wpdb->insert(
                $tabla_publicaciones,
                $datos_publicacion,
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            if ($insertado === false) {
                throw new Exception('Error al insertar datos de publicación: ' . $wpdb->last_error);
            }

            $publicacion_id = $wpdb->insert_id;

            // Registrar origen federado en tabla secundaria
            $tabla_federacion = $wpdb->prefix . 'flavor_social_federacion';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_federacion}'")) {
                $datos_federacion = [
                    'publicacion_id' => $publicacion_id,
                    'nodo_origen'    => $origen,
                    'nodo_id'        => $nodo_id,
                    'fecha_recibido' => current_time('mysql'),
                ];

                $insertado_federacion = $wpdb->insert($tabla_federacion, $datos_federacion);
                if ($insertado_federacion === false) {
                    throw new Exception('Error al insertar datos de federación: ' . $wpdb->last_error);
                }
            }

            // Confirmar transacción
            $wpdb->query('COMMIT');

            $this->log_federation_event('info', 'Publicación federada recibida', [
                'publicacion_id' => $publicacion_id,
                'origen'         => $origen,
                'nodo_id'        => $nodo_id,
            ]);

            do_action('flavor_publicacion_federada_recibida', $publicacion_id, $origen, $body);

            return new WP_REST_Response([
                'success'        => true,
                'message'        => 'Publicación recibida',
                'publicacion_id' => $publicacion_id,
            ], 201);

        } catch (Exception $excepcion) {
            // Revertir transacción en caso de error
            $wpdb->query('ROLLBACK');

            $this->log_federation_event('error', 'Error al recibir publicación federada', [
                'error'  => $excepcion->getMessage(),
                'origen' => $origen,
            ]);

            return new WP_Error(
                'db_error',
                $excepcion->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Obtiene o crea un usuario para representar contenido federado
     */
    private function obtener_usuario_federado($origen, $nombre_autor) {
        // Usar un usuario genérico para contenido federado
        $username = 'federado_' . md5($origen);

        $user = get_user_by('login', $username);
        if ($user) {
            return $user->ID;
        }

        // Crear usuario
        $user_id = wp_insert_user([
            'user_login'   => $username,
            'user_pass'    => wp_generate_password(24),
            'user_email'   => $username . '@federado.local',
            'display_name' => $nombre_autor . ' (Federado)',
            'role'         => 'subscriber',
        ]);

        if (is_wp_error($user_id)) {
            // Fallback a admin
            return 1;
        }

        // Marcar como usuario federado
        update_user_meta($user_id, '_flavor_usuario_federado', true);
        update_user_meta($user_id, '_flavor_nodo_origen', $origen);

        return $user_id;
    }

    // ═══════════════════════════════════════════════════════════
    // HELPER GENÉRICO PARA ITEMS FEDERADOS (P3 - Refactorización)
    // ═══════════════════════════════════════════════════════════

    /**
     * Obtiene items federados de forma genérica
     *
     * Este método centraliza la lógica común de todos los endpoints
     * que obtienen datos federados con filtros de geolocalización.
     *
     * @param array $config Configuración con las siguientes claves:
     *   - tabla: (string) Nombre completo de la tabla
     *   - campos: (string) Campos a seleccionar, default '*'
     *   - where_base: (array) Condiciones WHERE base sin preparar
     *   - where_prepared: (array) Condiciones WHERE con prepare [['sql' => 'col = %s', 'value' => $val], ...]
     *   - order_by: (string) ORDER BY clause, default 'actualizado_en DESC'
     *   - limite: (int) Límite de resultados, default 50, max 100
     *   - lat: (float|null) Latitud para filtro geográfico
     *   - lng: (float|null) Longitud para filtro geográfico
     *   - radio: (float) Radio en km para filtro geográfico, default 50
     *   - lat_col: (string) Nombre de columna de latitud, default 'latitud'
     *   - lng_col: (string) Nombre de columna de longitud, default 'longitud'
     *   - distancia_override: (string|null) Condición especial para filtro de distancia
     *   - error_no_tabla: (string) Mensaje de error si la tabla no existe
     *
     * @return array|WP_Error Array con resultados o error si la tabla no existe
     */
    private function get_federated_items($config) {
        global $wpdb;

        $tabla = $config['tabla'];
        $campos = $config['campos'] ?? '*';
        $where_base = $config['where_base'] ?? [];
        $where_prepared = $config['where_prepared'] ?? [];
        $order_by = $config['order_by'] ?? 'actualizado_en DESC';
        $limite = min(100, max(1, intval($config['limite'] ?? 50)));
        $error_no_tabla = $config['error_no_tabla'] ?? 'Sistema federado no disponible';

        // Columnas de geolocalización
        $lat_col = $config['lat_col'] ?? 'latitud';
        $lng_col = $config['lng_col'] ?? 'longitud';

        // Validar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return new WP_Error('no_tabla', $error_no_tabla, ['status' => 503]);
        }

        // Validar coordenadas si se proporcionan
        $lat = isset($config['lat']) ? floatval($config['lat']) : null;
        $lng = isset($config['lng']) ? floatval($config['lng']) : null;
        $radio = isset($config['radio']) ? floatval($config['radio']) : 50;

        $select_distancia = '';
        $having = '';
        $usar_distancia = false;

        if ($lat !== null && $lng !== null && $lat !== 0.0 && $lng !== 0.0) {
            $usar_distancia = true;

            // Fórmula Haversine para calcular distancia en km
            $haversine = sprintf(
                "(6371 * acos(cos(radians(%f)) * cos(radians(%s)) * cos(radians(%s) - radians(%f)) + sin(radians(%f)) * sin(radians(%s))))",
                $lat,
                $lat_col,
                $lng_col,
                $lng,
                $lat,
                $lat_col
            );

            $select_distancia = ", {$haversine} AS distancia";

            // Override de condición de distancia si se proporciona
            if (!empty($config['distancia_override'])) {
                $having = "HAVING {$config['distancia_override']}";
            } else {
                $having = "HAVING distancia <= {$radio}";
            }

            $order_by = 'distancia ASC';
        }

        // Construir WHERE
        $where_parts = $where_base;

        foreach ($where_prepared as $prepared_condition) {
            if (isset($prepared_condition['sql']) && isset($prepared_condition['value'])) {
                $where_parts[] = $wpdb->prepare($prepared_condition['sql'], $prepared_condition['value']);
            }
        }

        $where = !empty($where_parts) ? implode(' AND ', $where_parts) : '1=1';

        // Construir y ejecutar query
        $sql = "SELECT {$campos} {$select_distancia}
                FROM {$tabla}
                WHERE {$where}
                {$having}
                ORDER BY {$order_by}
                LIMIT {$limite}";

        $resultados = $wpdb->get_results($sql);

        return $resultados ?: [];
    }

    /**
     * Genera la fórmula Haversine para cálculo de distancia
     *
     * @param float  $lat      Latitud del punto de referencia
     * @param float  $lng      Longitud del punto de referencia
     * @param string $lat_col  Nombre de la columna de latitud en la tabla
     * @param string $lng_col  Nombre de la columna de longitud en la tabla
     * @return string SQL con la fórmula Haversine
     */
    private function build_haversine_formula($lat, $lng, $lat_col = 'latitud', $lng_col = 'longitud') {
        return sprintf(
            "(6371 * acos(cos(radians(%f)) * cos(radians(%s)) * cos(radians(%s) - radians(%f)) + sin(radians(%f)) * sin(radians(%s))))",
            $lat,
            $lat_col,
            $lng_col,
            $lng,
            $lat,
            $lat_col
        );
    }

    /**
     * Endpoint: Ping para verificar disponibilidad
     */
    public function ping($request) {
        return [
            'status'    => 'ok',
            'nodo'      => get_bloginfo('name'),
            'version'   => FLAVOR_PLATFORM_VERSION,
            'timestamp' => current_time('c'),
        ];
    }

    /**
     * Endpoint: Obtener feed de publicaciones públicas
     */
    public function obtener_feed_federado($request) {
        global $wpdb;

        $limite = min(50, intval($request->get_param('limite')) ?: 20);
        $desde = $request->get_param('desde');

        $tabla = $wpdb->prefix . 'flavor_social_publicaciones';

        $where = "privacidad = 'publico' AND estado = 'publicado'";
        if ($desde) {
            $where .= $wpdb->prepare(" AND fecha_creacion > %s", $desde);
        }

        $publicaciones = $wpdb->get_results(
            "SELECT p.*, u.display_name as autor_nombre
             FROM {$tabla} p
             LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             WHERE {$where}
             ORDER BY p.fecha_creacion DESC
             LIMIT {$limite}"
        );

        $resultado = [];
        foreach ($publicaciones as $pub) {
            $resultado[] = [
                'id'                 => $pub->id,
                'contenido'          => $pub->contenido,
                'tipo'               => $pub->tipo,
                'enlace_url'         => $pub->enlace_url,
                'enlace_titulo'      => $pub->enlace_titulo,
                'enlace_descripcion' => $pub->enlace_descripcion,
                'enlace_imagen'      => $pub->enlace_imagen,
                'autor_nombre'       => $pub->autor_nombre,
                'fecha'              => $pub->fecha_creacion,
                'likes'              => $pub->likes_count,
                'comentarios'        => $pub->comentarios_count,
            ];
        }

        return [
            'nodo'          => home_url(),
            'nombre'        => get_bloginfo('name'),
            'publicaciones' => $resultado,
            'total'         => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener productores compartidos en la red
     *
     * Parámetros opcionales:
     * - lat: Latitud del nodo solicitante
     * - lng: Longitud del nodo solicitante
     * - radio: Radio máximo en km (por defecto usa el del productor)
     * - mensajeria: 1 para incluir solo productores con mensajería
     *
     * P12 - Usa cache transient para optimizar consultas repetidas (15 min TTL)
     */
    public function obtener_productores_federados($request) {
        // P11 - Aplicar rate limiting
        $rate_check = $this->enforce_federation_rate_limit();
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }

        // P1 - Validar coordenadas antes de usar en Haversine
        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $coords_validadas = null;

        if ($lat_solicitante || $lng_solicitante) {
            $coords_validadas = $this->validate_coordinates($lat_solicitante, $lng_solicitante);
            if ($coords_validadas === false) {
                return new WP_Error(
                    'coordenadas_invalidas',
                    __('Las coordenadas proporcionadas no son válidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
            $lat_solicitante = $coords_validadas['lat'];
            $lng_solicitante = $coords_validadas['lng'];
        }

        $solo_mensajeria = $request->get_param('mensajeria') === '1';
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        // P12 - Generar clave de cache (redondear coords para agrupar consultas cercanas)
        $cache_key = $this->generate_cache_key('productores', [
            'lat'        => round($lat_solicitante, 2),
            'lng'        => round($lng_solicitante, 2),
            'mensajeria' => $solo_mensajeria,
            'limite'     => $limite,
        ]);

        return $this->get_cached_federated_data($cache_key, function() use ($lat_solicitante, $lng_solicitante, $coords_validadas, $solo_mensajeria, $limite) {
            global $wpdb;

            $tabla_productores = $wpdb->prefix . 'flavor_network_producers';

            // Verificar que la tabla existe
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productores'") !== $tabla_productores) {
                return new WP_Error('no_tabla', 'Sistema de productores federados no disponible', ['status' => 503]);
            }

            $where_clauses = ["visible_en_red = 1", "compartir_en_red = 1"];

            if ($solo_mensajeria) {
                $where_clauses[] = "acepta_mensajeria = 1";
            }

            $where = implode(' AND ', $where_clauses);

            // Si tenemos coordenadas validadas, calcular distancia
            if ($coords_validadas && !$solo_mensajeria) {
                // P1 - Fórmula Haversine segura con coordenadas validadas
                $haversine = $this->build_haversine_formula($lat_solicitante, $lng_solicitante);

                $productores = $wpdb->get_results("
                    SELECT *,
                        {$haversine} AS distancia_km
                    FROM {$tabla_productores}
                    WHERE {$where}
                        AND latitud IS NOT NULL
                        AND longitud IS NOT NULL
                        AND (
                            acepta_mensajeria = 1
                            OR {$haversine} <= radio_entrega_km
                        )
                    ORDER BY distancia_km ASC
                    LIMIT {$limite}
                ");
            } else {
                $productores = $wpdb->get_results("
                    SELECT *
                    FROM {$tabla_productores}
                    WHERE {$where}
                    ORDER BY actualizado_en DESC
                    LIMIT {$limite}
                ");
            }

            $resultado = [];
            foreach ($productores as $prod) {
                $item = [
                    'id'                => $prod->id,
                    'nodo_id'           => $prod->nodo_id,
                    'productor_id'      => $prod->productor_id,
                    'nombre'            => $prod->nombre,
                    'slug'              => $prod->slug,
                    'ubicacion'         => $prod->ubicacion,
                    'certificacion_eco' => (bool) $prod->certificacion_eco,
                    'acepta_mensajeria' => (bool) $prod->acepta_mensajeria,
                    'radio_entrega_km'  => $prod->radio_entrega_km,
                ];

                if (isset($prod->distancia_km)) {
                    $item['distancia_km'] = round($prod->distancia_km, 1);
                }

                $resultado[] = $item;
            }

            return [
                'nodo'        => home_url(),
                'nombre'      => get_bloginfo('name'),
                'productores' => $resultado,
                'total'       => count($resultado),
            ];
        }, self::CACHE_TTL_DEFAULT);
    }

    /**
     * Endpoint: Obtener detalle de un productor federado
     */
    public function obtener_productor_detalle($request) {
        global $wpdb;

        $slug = sanitize_title($request->get_param('slug'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        if (empty($slug)) {
            return new WP_Error('slug_requerido', 'Se requiere el slug del productor', ['status' => 400]);
        }

        $tabla_productores = $wpdb->prefix . 'flavor_network_producers';
        $tabla_productos = $wpdb->prefix . 'flavor_network_producer_products';

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productores'") !== $tabla_productores) {
            return new WP_Error('no_tabla', 'Sistema de productores federados no disponible', ['status' => 503]);
        }

        // Buscar productor
        $where = "slug = %s AND visible_en_red = 1";
        $params = [$slug];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $productor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_productores} WHERE {$where}",
            $params
        ));

        if (!$productor) {
            return new WP_Error('no_encontrado', 'Productor no encontrado', ['status' => 404]);
        }

        // Obtener productos del productor
        $productos = [];
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productos'") === $tabla_productos) {
            $productos_raw = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_productos}
                 WHERE nodo_id = %s AND productor_id = %d AND disponible = 1",
                $productor->nodo_id,
                $productor->productor_id
            ));

            foreach ($productos_raw as $prod) {
                $productos[] = [
                    'id'      => $prod->producto_id,
                    'nombre'  => $prod->nombre,
                    'precio'  => floatval($prod->precio),
                    'unidad'  => $prod->unidad,
                ];
            }
        }

        return [
            'productor' => [
                'id'                => $productor->id,
                'nodo_id'           => $productor->nodo_id,
                'productor_id'      => $productor->productor_id,
                'nombre'            => $productor->nombre,
                'slug'              => $productor->slug,
                'ubicacion'         => $productor->ubicacion,
                'latitud'           => $productor->latitud,
                'longitud'          => $productor->longitud,
                'radio_entrega_km'  => $productor->radio_entrega_km,
                'certificacion_eco' => (bool) $productor->certificacion_eco,
                'acepta_mensajeria' => (bool) $productor->acepta_mensajeria,
                'actualizado_en'    => $productor->actualizado_en,
            ],
            'productos' => $productos,
            'total_productos' => count($productos),
        ];
    }

    /**
     * Endpoint: Contactar a un productor federado
     */
    public function contactar_productor($request) {
        global $wpdb;

        $body = $request->get_json_params();

        $nodo_id = sanitize_text_field($body['nodo_id'] ?? '');
        $productor_id = absint($body['productor_id'] ?? 0);
        $mensaje = sanitize_textarea_field($body['mensaje'] ?? '');
        $email_contacto = sanitize_email($body['email'] ?? '');
        $nombre_contacto = sanitize_text_field($body['nombre'] ?? '');

        if (empty($nodo_id) || empty($productor_id) || empty($mensaje)) {
            return new WP_Error('datos_faltantes', 'Faltan datos requeridos', ['status' => 400]);
        }

        $tabla_productores = $wpdb->prefix . 'flavor_network_producers';

        // Verificar que el productor existe y acepta contacto
        $productor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_productores}
             WHERE nodo_id = %s AND productor_id = %d AND visible_en_red = 1",
            $nodo_id,
            $productor_id
        ));

        if (!$productor) {
            return new WP_Error('no_encontrado', 'Productor no encontrado', ['status' => 404]);
        }

        // Obtener email del productor desde el post original
        $email_productor = get_post_meta($productor_id, '_gc_contacto_email', true);

        if (empty($email_productor)) {
            // Intentar obtener del autor del post
            $post_productor = get_post($productor_id);
            if ($post_productor) {
                $autor = get_userdata($post_productor->post_author);
                if ($autor) {
                    $email_productor = $autor->user_email;
                }
            }
        }

        if (empty($email_productor)) {
            return new WP_Error('sin_email', 'El productor no tiene email de contacto configurado', ['status' => 400]);
        }

        // Enviar notificación por email
        $asunto = sprintf(
            __('[%s] Contacto desde la red federada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            get_bloginfo('name')
        );

        $cuerpo = sprintf(
            __("Has recibido un mensaje desde otro nodo de la red federada:\n\n" .
               "De: %s <%s>\n\n" .
               "Mensaje:\n%s\n\n" .
               "---\n" .
               "Este mensaje fue enviado a través de la red federada de Flavor.", FLAVOR_PLATFORM_TEXT_DOMAIN),
            $nombre_contacto ?: __('Usuario anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $email_contacto ?: __('Sin email', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $mensaje
        );

        $enviado = wp_mail($email_productor, $asunto, $cuerpo);

        // Registrar el contacto
        do_action('flavor_productor_contactado_federacion', $productor_id, $body);

        return [
            'success' => $enviado,
            'message' => $enviado
                ? __('Mensaje enviado al productor', FLAVOR_PLATFORM_TEXT_DOMAIN)
                : __('Error al enviar el mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // EVENTOS FEDERADOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener eventos compartidos en la red
     */
    public function obtener_eventos_federados($request) {
        global $wpdb;

        // P11 - Aplicar rate limiting
        $rate_check = $this->enforce_federation_rate_limit();
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }

        $tabla_eventos = $wpdb->prefix . 'flavor_network_events';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") !== $tabla_eventos) {
            return new WP_Error('no_tabla', 'Sistema de eventos federados no disponible', ['status' => 503]);
        }

        // P1 - Validar coordenadas antes de usar en Haversine
        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $coords_validadas = null;

        if ($lat_solicitante || $lng_solicitante) {
            $coords_validadas = $this->validate_coordinates($lat_solicitante, $lng_solicitante);
            if ($coords_validadas === false) {
                return new WP_Error(
                    'coordenadas_invalidas',
                    __('Las coordenadas proporcionadas no son válidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
            $lat_solicitante = $coords_validadas['lat'];
            $lng_solicitante = $coords_validadas['lng'];
        }

        $radio_km = absint($request->get_param('radio')) ?: 100;
        $desde = $request->get_param('desde') ?: date('Y-m-d H:i:s');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "fecha_inicio >= %s"];
        $params = [$desde];

        $where = implode(' AND ', $where_clauses);

        // Si tenemos coordenadas validadas, filtrar por distancia
        if ($coords_validadas) {
            // P1 - Fórmula Haversine segura con coordenadas validadas
            $haversine = $this->build_haversine_formula($lat_solicitante, $lng_solicitante);

            $eventos = $wpdb->get_results($wpdb->prepare("
                SELECT *,
                    {$haversine} AS distancia_km
                FROM {$tabla_eventos}
                WHERE {$where}
                    AND (
                        es_online = 1
                        OR latitud IS NULL
                        OR {$haversine} <= {$radio_km}
                    )
                ORDER BY fecha_inicio ASC
                LIMIT {$limite}
            ", ...$params));
        } else {
            $eventos = $wpdb->get_results($wpdb->prepare("
                SELECT *
                FROM {$tabla_eventos}
                WHERE {$where}
                ORDER BY fecha_inicio ASC
                LIMIT {$limite}
            ", ...$params));
        }

        $resultado = [];
        foreach ($eventos as $ev) {
            $item = [
                'id'                 => $ev->id,
                'nodo_id'            => $ev->nodo_id,
                'evento_id'          => $ev->evento_id,
                'titulo'             => $ev->titulo,
                'descripcion'        => wp_trim_words($ev->descripcion, 30),
                'tipo'               => $ev->tipo,
                'fecha_inicio'       => $ev->fecha_inicio,
                'fecha_fin'          => $ev->fecha_fin,
                'ubicacion'          => $ev->ubicacion,
                'es_online'          => (bool) $ev->es_online,
                'precio'             => floatval($ev->precio),
                'aforo_maximo'       => $ev->aforo_maximo,
                'inscritos_count'    => $ev->inscritos_count,
                'organizador_nombre' => $ev->organizador_nombre,
                'imagen_url'         => $ev->imagen_url,
            ];

            if (isset($ev->distancia_km)) {
                $item['distancia_km'] = round($ev->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'    => home_url(),
            'nombre'  => get_bloginfo('name'),
            'eventos' => $resultado,
            'total'   => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un evento federado
     */
    public function obtener_evento_detalle($request) {
        global $wpdb;

        $evento_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_eventos = $wpdb->prefix . 'flavor_network_events';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") !== $tabla_eventos) {
            return new WP_Error('no_tabla', 'Sistema de eventos federados no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$evento_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_eventos} WHERE {$where}",
            $params
        ));

        if (!$evento) {
            return new WP_Error('no_encontrado', 'Evento no encontrado', ['status' => 404]);
        }

        return [
            'evento' => [
                'id'                 => $evento->id,
                'nodo_id'            => $evento->nodo_id,
                'evento_id'          => $evento->evento_id,
                'titulo'             => $evento->titulo,
                'descripcion'        => $evento->descripcion,
                'tipo'               => $evento->tipo,
                'fecha_inicio'       => $evento->fecha_inicio,
                'fecha_fin'          => $evento->fecha_fin,
                'ubicacion'          => $evento->ubicacion,
                'direccion'          => $evento->direccion,
                'latitud'            => $evento->latitud,
                'longitud'           => $evento->longitud,
                'es_online'          => (bool) $evento->es_online,
                'url_online'         => $evento->url_online,
                'precio'             => floatval($evento->precio),
                'aforo_maximo'       => $evento->aforo_maximo,
                'inscritos_count'    => $evento->inscritos_count,
                'organizador_nombre' => $evento->organizador_nombre,
                'imagen_url'         => $evento->imagen_url,
                'actualizado_en'     => $evento->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CARPOOLING FEDERADO
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener viajes compartidos en la red
     */
    public function obtener_viajes_federados($request) {
        global $wpdb;

        // P11 - Aplicar rate limiting
        $rate_check = $this->enforce_federation_rate_limit();
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }

        $tabla_viajes = $wpdb->prefix . 'flavor_network_carpooling';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_viajes'") !== $tabla_viajes) {
            return new WP_Error('no_tabla', 'Sistema de carpooling federado no disponible', ['status' => 503]);
        }

        // P1 - Validar coordenadas de origen
        $origen_lat = floatval($request->get_param('origen_lat'));
        $origen_lng = floatval($request->get_param('origen_lng'));
        $coords_origen = null;

        if ($origen_lat || $origen_lng) {
            $coords_origen = $this->validate_coordinates($origen_lat, $origen_lng);
            if ($coords_origen === false) {
                return new WP_Error(
                    'coordenadas_invalidas',
                    __('Las coordenadas de origen no son válidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
            $origen_lat = $coords_origen['lat'];
            $origen_lng = $coords_origen['lng'];
        }

        // P1 - Validar coordenadas de destino
        $destino_lat = floatval($request->get_param('destino_lat'));
        $destino_lng = floatval($request->get_param('destino_lng'));
        $coords_destino = null;

        if ($destino_lat || $destino_lng) {
            $coords_destino = $this->validate_coordinates($destino_lat, $destino_lng);
            if ($coords_destino === false) {
                return new WP_Error(
                    'coordenadas_invalidas',
                    __('Las coordenadas de destino no son válidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
            $destino_lat = $coords_destino['lat'];
            $destino_lng = $coords_destino['lng'];
        }

        $radio_km = absint($request->get_param('radio')) ?: 50;
        $desde = $request->get_param('desde') ?: date('Y-m-d H:i:s');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'activo'", "fecha_salida >= %s", "plazas_disponibles > 0"];
        $params = [$desde];

        $where = implode(' AND ', $where_clauses);

        // Calcular distancia si tenemos coordenadas de origen validadas
        if ($coords_origen) {
            // P1 - Fórmula Haversine segura con coordenadas validadas
            $haversine_origen = $this->build_haversine_formula($origen_lat, $origen_lng, 'origen_lat', 'origen_lng');

            $select_extra = ", {$haversine_origen} AS distancia_origen_km";
            $where_extra = " AND {$haversine_origen} <= {$radio_km}";
            $order = "distancia_origen_km ASC";
        } else {
            $select_extra = "";
            $where_extra = "";
            $order = "fecha_salida ASC";
        }

        // Filtrar también por destino si tenemos coordenadas validadas
        if ($coords_destino) {
            // P1 - Fórmula Haversine segura con coordenadas validadas
            $haversine_destino = $this->build_haversine_formula($destino_lat, $destino_lng, 'destino_lat', 'destino_lng');
            $select_extra .= ", {$haversine_destino} AS distancia_destino_km";
            $where_extra .= " AND {$haversine_destino} <= {$radio_km}";
        }

        $viajes = $wpdb->get_results($wpdb->prepare("
            SELECT * {$select_extra}
            FROM {$tabla_viajes}
            WHERE {$where} {$where_extra}
            ORDER BY {$order}
            LIMIT {$limite}
        ", ...$params));

        $resultado = [];
        foreach ($viajes as $v) {
            $item = [
                'id'                  => $v->id,
                'nodo_id'             => $v->nodo_id,
                'viaje_id'            => $v->viaje_id,
                'origen'              => $v->origen,
                'destino'             => $v->destino,
                'fecha_salida'        => $v->fecha_salida,
                'hora_salida'         => $v->hora_salida,
                'conductor_nombre'    => $v->conductor_nombre,
                'plazas_disponibles'  => $v->plazas_disponibles,
                'precio_plaza'        => floatval($v->precio_plaza),
                'permite_equipaje'    => (bool) $v->permite_equipaje,
                'permite_mascotas'    => (bool) $v->permite_mascotas,
            ];

            if (isset($v->distancia_origen_km)) {
                $item['distancia_origen_km'] = round($v->distancia_origen_km, 1);
            }
            if (isset($v->distancia_destino_km)) {
                $item['distancia_destino_km'] = round($v->distancia_destino_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'   => home_url(),
            'nombre' => get_bloginfo('name'),
            'viajes' => $resultado,
            'total'  => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un viaje federado
     */
    public function obtener_viaje_detalle($request) {
        global $wpdb;

        $viaje_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_viajes = $wpdb->prefix . 'flavor_network_carpooling';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_viajes'") !== $tabla_viajes) {
            return new WP_Error('no_tabla', 'Sistema de carpooling federado no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$viaje_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $viaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_viajes} WHERE {$where}",
            $params
        ));

        if (!$viaje) {
            return new WP_Error('no_encontrado', 'Viaje no encontrado', ['status' => 404]);
        }

        return [
            'viaje' => [
                'id'                  => $viaje->id,
                'nodo_id'             => $viaje->nodo_id,
                'viaje_id'            => $viaje->viaje_id,
                'origen'              => $viaje->origen,
                'origen_lat'          => $viaje->origen_lat,
                'origen_lng'          => $viaje->origen_lng,
                'destino'             => $viaje->destino,
                'destino_lat'         => $viaje->destino_lat,
                'destino_lng'         => $viaje->destino_lng,
                'fecha_salida'        => $viaje->fecha_salida,
                'hora_salida'         => $viaje->hora_salida,
                'conductor_nombre'    => $viaje->conductor_nombre,
                'plazas_totales'      => $viaje->plazas_totales,
                'plazas_disponibles'  => $viaje->plazas_disponibles,
                'precio_plaza'        => floatval($viaje->precio_plaza),
                'permite_equipaje'    => (bool) $viaje->permite_equipaje,
                'permite_mascotas'    => (bool) $viaje->permite_mascotas,
                'notas'               => $viaje->notas,
                'estado'              => $viaje->estado,
                'actualizado_en'      => $viaje->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // TALLERES FEDERADOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener talleres compartidos en la red
     */
    public function obtener_talleres_federados($request) {
        global $wpdb;

        // P11 - Aplicar rate limiting
        $rate_check = $this->enforce_federation_rate_limit();
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }

        $tabla_talleres = $wpdb->prefix . 'flavor_network_workshops';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_talleres'") !== $tabla_talleres) {
            return new WP_Error('no_tabla', 'Sistema de talleres federados no disponible', ['status' => 503]);
        }

        // P1 - Validar coordenadas antes de usar en Haversine
        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $coords_validadas = null;

        if ($lat_solicitante || $lng_solicitante) {
            $coords_validadas = $this->validate_coordinates($lat_solicitante, $lng_solicitante);
            if ($coords_validadas === false) {
                return new WP_Error(
                    'coordenadas_invalidas',
                    __('Las coordenadas proporcionadas no son válidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
            $lat_solicitante = $coords_validadas['lat'];
            $lng_solicitante = $coords_validadas['lng'];
        }

        $radio_km = absint($request->get_param('radio')) ?: 100;
        $categoria = $request->get_param('categoria');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado IN ('publicado', 'confirmado')"];
        $params = [];

        if (!empty($categoria)) {
            $where_clauses[] = "categoria = %s";
            $params[] = $categoria;
        }

        $where = implode(' AND ', $where_clauses);

        // Filtrar por distancia si tenemos coordenadas validadas
        if ($coords_validadas) {
            // P1 - Fórmula Haversine segura con coordenadas validadas
            $haversine = $this->build_haversine_formula($lat_solicitante, $lng_solicitante);

            $sql = "SELECT *, {$haversine} AS distancia_km
                    FROM {$tabla_talleres}
                    WHERE {$where}
                      AND (latitud IS NULL OR {$haversine} <= {$radio_km})
                    ORDER BY fecha_primera_sesion ASC
                    LIMIT {$limite}";
        } else {
            $sql = "SELECT *
                    FROM {$tabla_talleres}
                    WHERE {$where}
                    ORDER BY fecha_primera_sesion ASC
                    LIMIT {$limite}";
        }

        if (!empty($params)) {
            $talleres = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            $talleres = $wpdb->get_results($sql);
        }

        $resultado = [];
        foreach ($talleres as $t) {
            $item = [
                'id'                   => $t->id,
                'nodo_id'              => $t->nodo_id,
                'taller_id'            => $t->taller_id,
                'titulo'               => $t->titulo,
                'slug'                 => $t->slug,
                'descripcion'          => wp_trim_words($t->descripcion, 30),
                'categoria'            => $t->categoria,
                'nivel'                => $t->nivel,
                'duracion_horas'       => floatval($t->duracion_horas),
                'numero_sesiones'      => $t->numero_sesiones,
                'max_participantes'    => $t->max_participantes,
                'inscritos_actuales'   => $t->inscritos_actuales,
                'plazas_disponibles'   => $t->max_participantes - $t->inscritos_actuales,
                'precio'               => floatval($t->precio),
                'es_gratuito'          => (bool) $t->es_gratuito,
                'ubicacion'            => $t->ubicacion,
                'organizador_nombre'   => $t->organizador_nombre,
                'imagen_url'           => $t->imagen_url,
                'fecha_primera_sesion' => $t->fecha_primera_sesion,
            ];

            if (isset($t->distancia_km)) {
                $item['distancia_km'] = round($t->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'     => home_url(),
            'nombre'   => get_bloginfo('name'),
            'talleres' => $resultado,
            'total'    => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un taller federado
     */
    public function obtener_taller_detalle($request) {
        global $wpdb;

        $taller_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_talleres = $wpdb->prefix . 'flavor_network_workshops';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_talleres'") !== $tabla_talleres) {
            return new WP_Error('no_tabla', 'Sistema de talleres federados no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$taller_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $taller = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_talleres} WHERE {$where}",
            $params
        ));

        if (!$taller) {
            return new WP_Error('no_encontrado', 'Taller no encontrado', ['status' => 404]);
        }

        return [
            'taller' => [
                'id'                       => $taller->id,
                'nodo_id'                  => $taller->nodo_id,
                'taller_id'                => $taller->taller_id,
                'titulo'                   => $taller->titulo,
                'slug'                     => $taller->slug,
                'descripcion'              => $taller->descripcion,
                'categoria'                => $taller->categoria,
                'nivel'                    => $taller->nivel,
                'duracion_horas'           => floatval($taller->duracion_horas),
                'numero_sesiones'          => $taller->numero_sesiones,
                'max_participantes'        => $taller->max_participantes,
                'inscritos_actuales'       => $taller->inscritos_actuales,
                'plazas_disponibles'       => $taller->max_participantes - $taller->inscritos_actuales,
                'precio'                   => floatval($taller->precio),
                'es_gratuito'              => (bool) $taller->es_gratuito,
                'ubicacion'                => $taller->ubicacion,
                'latitud'                  => $taller->latitud,
                'longitud'                 => $taller->longitud,
                'organizador_nombre'       => $taller->organizador_nombre,
                'imagen_url'               => $taller->imagen_url,
                'fecha_primera_sesion'     => $taller->fecha_primera_sesion,
                'fecha_limite_inscripcion' => $taller->fecha_limite_inscripcion,
                'estado'                   => $taller->estado,
                'actualizado_en'           => $taller->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // ESPACIOS COMUNES FEDERADOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener espacios compartidos en la red
     */
    public function obtener_espacios_federados($request) {
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_network_spaces';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_espacios'") !== $tabla_espacios) {
            return new WP_Error('no_tabla', 'Sistema de espacios federados no disponible', ['status' => 503]);
        }

        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $radio_km = absint($request->get_param('radio')) ?: 50;
        $tipo = $request->get_param('tipo');
        $capacidad_min = absint($request->get_param('capacidad_min'));
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'disponible'"];

        if (!empty($tipo)) {
            $where_clauses[] = $wpdb->prepare("tipo = %s", $tipo);
        }

        if ($capacidad_min > 0) {
            $where_clauses[] = $wpdb->prepare("capacidad_personas >= %d", $capacidad_min);
        }

        $where = implode(' AND ', $where_clauses);

        // P1 - Validar coordenadas antes de usar en Haversine
        $coords_validadas = null;
        if ($lat_solicitante || $lng_solicitante) {
            $coords_validadas = $this->validate_coordinates($lat_solicitante, $lng_solicitante);
            if ($coords_validadas === false) {
                return new WP_Error(
                    'coordenadas_invalidas',
                    __('Las coordenadas proporcionadas no son válidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
            $lat_solicitante = $coords_validadas['lat'];
            $lng_solicitante = $coords_validadas['lng'];
        }

        // Filtrar por distancia si tenemos coordenadas validadas
        if ($coords_validadas) {
            // P1 - Fórmula Haversine segura con coordenadas validadas
            $haversine = $this->build_haversine_formula($lat_solicitante, $lng_solicitante);

            $espacios = $wpdb->get_results("
                SELECT *, {$haversine} AS distancia_km
                FROM {$tabla_espacios}
                WHERE {$where}
                  AND (latitud IS NULL OR {$haversine} <= {$radio_km})
                ORDER BY distancia_km ASC
                LIMIT {$limite}
            ");
        } else {
            $espacios = $wpdb->get_results("
                SELECT *
                FROM {$tabla_espacios}
                WHERE {$where}
                ORDER BY nombre ASC
                LIMIT {$limite}
            ");
        }

        $resultado = [];
        foreach ($espacios as $e) {
            $item = [
                'id'                 => $e->id,
                'nodo_id'            => $e->nodo_id,
                'espacio_id'         => $e->espacio_id,
                'nombre'             => $e->nombre,
                'descripcion'        => wp_trim_words($e->descripcion, 25),
                'tipo'               => $e->tipo,
                'ubicacion'          => $e->ubicacion,
                'capacidad_personas' => $e->capacidad_personas,
                'precio_hora'        => floatval($e->precio_hora),
                'precio_dia'         => floatval($e->precio_dia),
                'horario'            => $e->horario_apertura . ' - ' . $e->horario_cierre,
                'dias_disponibles'   => $e->dias_disponibles,
                'foto_principal'     => $e->foto_principal,
            ];

            if (isset($e->distancia_km)) {
                $item['distancia_km'] = round($e->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'     => home_url(),
            'nombre'   => get_bloginfo('name'),
            'espacios' => $resultado,
            'total'    => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un espacio federado
     */
    public function obtener_espacio_detalle($request) {
        global $wpdb;

        $espacio_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_espacios = $wpdb->prefix . 'flavor_network_spaces';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_espacios'") !== $tabla_espacios) {
            return new WP_Error('no_tabla', 'Sistema de espacios federados no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$espacio_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_espacios} WHERE {$where}",
            $params
        ));

        if (!$espacio) {
            return new WP_Error('no_encontrado', 'Espacio no encontrado', ['status' => 404]);
        }

        return [
            'espacio' => [
                'id'                 => $espacio->id,
                'nodo_id'            => $espacio->nodo_id,
                'espacio_id'         => $espacio->espacio_id,
                'nombre'             => $espacio->nombre,
                'descripcion'        => $espacio->descripcion,
                'tipo'               => $espacio->tipo,
                'ubicacion'          => $espacio->ubicacion,
                'latitud'            => $espacio->latitud,
                'longitud'           => $espacio->longitud,
                'capacidad_personas' => $espacio->capacidad_personas,
                'superficie_m2'      => $espacio->superficie_m2,
                'precio_hora'        => floatval($espacio->precio_hora),
                'precio_dia'         => floatval($espacio->precio_dia),
                'horario_apertura'   => $espacio->horario_apertura,
                'horario_cierre'     => $espacio->horario_cierre,
                'dias_disponibles'   => $espacio->dias_disponibles,
                'foto_principal'     => $espacio->foto_principal,
                'estado'             => $espacio->estado,
                'actualizado_en'     => $espacio->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // MARKETPLACE FEDERADO
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener anuncios del marketplace compartidos en la red
     */
    public function obtener_anuncios_federados($request) {
        global $wpdb;

        // P11 - Aplicar rate limiting
        $rate_check = $this->enforce_federation_rate_limit();
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }

        $tabla_anuncios = $wpdb->prefix . 'flavor_network_marketplace';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_anuncios'") !== $tabla_anuncios) {
            return new WP_Error('no_tabla', 'Sistema de marketplace federado no disponible', ['status' => 503]);
        }

        // P1 - Validar coordenadas antes de usar en Haversine
        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $coords_validadas = null;

        if ($lat_solicitante || $lng_solicitante) {
            $coords_validadas = $this->validate_coordinates($lat_solicitante, $lng_solicitante);
            if ($coords_validadas === false) {
                return new WP_Error(
                    'coordenadas_invalidas',
                    __('Las coordenadas proporcionadas no son válidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
            $lat_solicitante = $coords_validadas['lat'];
            $lng_solicitante = $coords_validadas['lng'];
        }

        $radio_km = absint($request->get_param('radio')) ?: 100;
        $tipo = $request->get_param('tipo');
        $categoria = $request->get_param('categoria');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'publicado'"];

        if (!empty($tipo)) {
            $where_clauses[] = $wpdb->prepare("tipo = %s", $tipo);
        }

        if (!empty($categoria)) {
            $where_clauses[] = $wpdb->prepare("categoria = %s", $categoria);
        }

        $where = implode(' AND ', $where_clauses);

        // Filtrar por distancia si tenemos coordenadas validadas
        if ($coords_validadas) {
            // P1 - Fórmula Haversine segura con coordenadas validadas
            $haversine = $this->build_haversine_formula($lat_solicitante, $lng_solicitante);

            $anuncios = $wpdb->get_results("
                SELECT *, {$haversine} AS distancia_km
                FROM {$tabla_anuncios}
                WHERE {$where}
                  AND (
                      envio_disponible = 1
                      OR latitud IS NULL
                      OR {$haversine} <= {$radio_km}
                  )
                ORDER BY actualizado_en DESC
                LIMIT {$limite}
            ");
        } else {
            $anuncios = $wpdb->get_results("
                SELECT *
                FROM {$tabla_anuncios}
                WHERE {$where}
                ORDER BY actualizado_en DESC
                LIMIT {$limite}
            ");
        }

        $resultado = [];
        foreach ($anuncios as $a) {
            $item = [
                'id'               => $a->id,
                'nodo_id'          => $a->nodo_id,
                'anuncio_id'       => $a->anuncio_id,
                'titulo'           => $a->titulo,
                'slug'             => $a->slug,
                'descripcion'      => wp_trim_words($a->descripcion, 30),
                'tipo'             => $a->tipo,
                'categoria'        => $a->categoria,
                'precio'           => $a->precio !== null ? floatval($a->precio) : null,
                'es_gratuito'      => (bool) $a->es_gratuito,
                'condicion'        => $a->condicion,
                'imagen_principal' => $a->imagen_principal,
                'ubicacion'        => $a->ubicacion,
                'envio_disponible' => (bool) $a->envio_disponible,
                'usuario_nombre'   => $a->usuario_nombre,
            ];

            if (isset($a->distancia_km)) {
                $item['distancia_km'] = round($a->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'     => home_url(),
            'nombre'   => get_bloginfo('name'),
            'anuncios' => $resultado,
            'total'    => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un anuncio federado
     */
    public function obtener_anuncio_detalle($request) {
        global $wpdb;

        $anuncio_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_anuncios = $wpdb->prefix . 'flavor_network_marketplace';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_anuncios'") !== $tabla_anuncios) {
            return new WP_Error('no_tabla', 'Sistema de marketplace federado no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$anuncio_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $anuncio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_anuncios} WHERE {$where}",
            $params
        ));

        if (!$anuncio) {
            return new WP_Error('no_encontrado', 'Anuncio no encontrado', ['status' => 404]);
        }

        return [
            'anuncio' => [
                'id'               => $anuncio->id,
                'nodo_id'          => $anuncio->nodo_id,
                'anuncio_id'       => $anuncio->anuncio_id,
                'titulo'           => $anuncio->titulo,
                'slug'             => $anuncio->slug,
                'descripcion'      => $anuncio->descripcion,
                'tipo'             => $anuncio->tipo,
                'categoria'        => $anuncio->categoria,
                'precio'           => $anuncio->precio !== null ? floatval($anuncio->precio) : null,
                'es_gratuito'      => (bool) $anuncio->es_gratuito,
                'condicion'        => $anuncio->condicion,
                'imagen_principal' => $anuncio->imagen_principal,
                'ubicacion'        => $anuncio->ubicacion,
                'latitud'          => $anuncio->latitud,
                'longitud'         => $anuncio->longitud,
                'envio_disponible' => (bool) $anuncio->envio_disponible,
                'usuario_nombre'   => $anuncio->usuario_nombre,
                'estado'           => $anuncio->estado,
                'actualizado_en'   => $anuncio->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // BANCO DE TIEMPO FEDERADO
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener servicios del banco de tiempo compartidos en la red
     */
    public function obtener_servicios_tiempo_federados($request) {
        global $wpdb;

        $tabla_servicios = $wpdb->prefix . 'flavor_network_time_bank';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_servicios'") !== $tabla_servicios) {
            return new WP_Error('no_tabla', 'Sistema de banco de tiempo federado no disponible', ['status' => 503]);
        }

        // P1 - Validar coordenadas antes de usar en Haversine
        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $coords_validadas = null;

        if ($lat_solicitante || $lng_solicitante) {
            $coords_validadas = $this->validate_coordinates($lat_solicitante, $lng_solicitante);
            if ($coords_validadas === false) {
                return new WP_Error(
                    'coordenadas_invalidas',
                    __('Las coordenadas proporcionadas no son válidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
            $lat_solicitante = $coords_validadas['lat'];
            $lng_solicitante = $coords_validadas['lng'];
        }

        $radio_km = absint($request->get_param('radio')) ?: 50;
        $tipo = $request->get_param('tipo');
        $categoria = $request->get_param('categoria');
        $modalidad = $request->get_param('modalidad');
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'activo'"];

        if (!empty($tipo)) {
            $where_clauses[] = $wpdb->prepare("tipo = %s", $tipo);
        }

        if (!empty($categoria)) {
            $where_clauses[] = $wpdb->prepare("categoria = %s", $categoria);
        }

        if (!empty($modalidad)) {
            $where_clauses[] = $wpdb->prepare("modalidad = %s", $modalidad);
        }

        $where = implode(' AND ', $where_clauses);

        // Filtrar por distancia si tenemos coordenadas validadas
        if ($coords_validadas) {
            // P1 - Fórmula Haversine segura con coordenadas validadas
            $haversine = $this->build_haversine_formula($lat_solicitante, $lng_solicitante);

            $servicios = $wpdb->get_results("
                SELECT *, {$haversine} AS distancia_km
                FROM {$tabla_servicios}
                WHERE {$where}
                  AND (
                      modalidad = 'online'
                      OR latitud IS NULL
                      OR {$haversine} <= {$radio_km}
                  )
                ORDER BY actualizado_en DESC
                LIMIT {$limite}
            ");
        } else {
            $servicios = $wpdb->get_results("
                SELECT *
                FROM {$tabla_servicios}
                WHERE {$where}
                ORDER BY actualizado_en DESC
                LIMIT {$limite}
            ");
        }

        $resultado = [];
        foreach ($servicios as $s) {
            $item = [
                'id'                       => $s->id,
                'nodo_id'                  => $s->nodo_id,
                'servicio_id'              => $s->servicio_id,
                'titulo'                   => $s->titulo,
                'descripcion'              => wp_trim_words($s->descripcion, 30),
                'tipo'                     => $s->tipo,
                'categoria'                => $s->categoria,
                'horas_estimadas'          => floatval($s->horas_estimadas),
                'modalidad'                => $s->modalidad,
                'disponibilidad'           => $s->disponibilidad,
                'ubicacion'                => $s->ubicacion,
                'usuario_nombre'           => $s->usuario_nombre,
                'valoracion_promedio'      => floatval($s->valoracion_promedio),
                'intercambios_completados' => (int) $s->intercambios_completados,
            ];

            if (isset($s->distancia_km)) {
                $item['distancia_km'] = round($s->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'      => home_url(),
            'nombre'    => get_bloginfo('name'),
            'servicios' => $resultado,
            'total'     => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un servicio de tiempo federado
     */
    public function obtener_servicio_tiempo_detalle($request) {
        global $wpdb;

        $servicio_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_servicios = $wpdb->prefix . 'flavor_network_time_bank';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_servicios'") !== $tabla_servicios) {
            return new WP_Error('no_tabla', 'Sistema de banco de tiempo federado no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$servicio_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_servicios} WHERE {$where}",
            $params
        ));

        if (!$servicio) {
            return new WP_Error('no_encontrado', 'Servicio no encontrado', ['status' => 404]);
        }

        return [
            'servicio' => [
                'id'                       => $servicio->id,
                'nodo_id'                  => $servicio->nodo_id,
                'servicio_id'              => $servicio->servicio_id,
                'titulo'                   => $servicio->titulo,
                'descripcion'              => $servicio->descripcion,
                'tipo'                     => $servicio->tipo,
                'categoria'                => $servicio->categoria,
                'horas_estimadas'          => floatval($servicio->horas_estimadas),
                'modalidad'                => $servicio->modalidad,
                'disponibilidad'           => $servicio->disponibilidad,
                'ubicacion'                => $servicio->ubicacion,
                'latitud'                  => $servicio->latitud,
                'longitud'                 => $servicio->longitud,
                'usuario_nombre'           => $servicio->usuario_nombre,
                'valoracion_promedio'      => floatval($servicio->valoracion_promedio),
                'intercambios_completados' => (int) $servicio->intercambios_completados,
                'estado'                   => $servicio->estado,
                'actualizado_en'           => $servicio->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CURSOS FEDERADOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Obtener cursos compartidos en la red
     */
    public function obtener_cursos_federados($request) {
        global $wpdb;

        // P11 - Aplicar rate limiting
        $rate_check = $this->enforce_federation_rate_limit();
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }

        $tabla_cursos = $wpdb->prefix . 'flavor_network_courses';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_cursos'") !== $tabla_cursos) {
            return new WP_Error('no_tabla', 'Sistema de cursos federados no disponible', ['status' => 503]);
        }

        // P1 - Validar coordenadas antes de usar en Haversine
        $lat_solicitante = floatval($request->get_param('lat'));
        $lng_solicitante = floatval($request->get_param('lng'));
        $coords_validadas = null;

        if ($lat_solicitante || $lng_solicitante) {
            $coords_validadas = $this->validate_coordinates($lat_solicitante, $lng_solicitante);
            if ($coords_validadas === false) {
                return new WP_Error(
                    'coordenadas_invalidas',
                    __('Las coordenadas proporcionadas no son válidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
            $lat_solicitante = $coords_validadas['lat'];
            $lng_solicitante = $coords_validadas['lng'];
        }

        $radio_km = absint($request->get_param('radio')) ?: 100;
        $categoria = $request->get_param('categoria');
        $nivel = $request->get_param('nivel');
        $modalidad = $request->get_param('modalidad');
        $solo_gratuitos = $request->get_param('gratuitos') === '1';
        $limite = min(100, intval($request->get_param('limite')) ?: 50);

        $where_clauses = ["visible_en_red = 1", "estado = 'publicado'"];

        if (!empty($categoria)) {
            $where_clauses[] = $wpdb->prepare("categoria = %s", $categoria);
        }

        if (!empty($nivel)) {
            $where_clauses[] = $wpdb->prepare("nivel = %s", $nivel);
        }

        if (!empty($modalidad)) {
            $where_clauses[] = $wpdb->prepare("modalidad = %s", $modalidad);
        }

        if ($solo_gratuitos) {
            $where_clauses[] = "es_gratuito = 1";
        }

        $where = implode(' AND ', $where_clauses);

        // Filtrar por distancia si tenemos coordenadas validadas (solo cursos presenciales o mixtos)
        if ($coords_validadas) {
            // P1 - Fórmula Haversine segura con coordenadas validadas
            $haversine = $this->build_haversine_formula($lat_solicitante, $lng_solicitante);

            $cursos = $wpdb->get_results("
                SELECT *, {$haversine} AS distancia_km
                FROM {$tabla_cursos}
                WHERE {$where}
                  AND (
                      modalidad = 'online'
                      OR latitud IS NULL
                      OR {$haversine} <= {$radio_km}
                  )
                ORDER BY fecha_inicio ASC, actualizado_en DESC
                LIMIT {$limite}
            ");
        } else {
            $cursos = $wpdb->get_results("
                SELECT *
                FROM {$tabla_cursos}
                WHERE {$where}
                ORDER BY fecha_inicio ASC, actualizado_en DESC
                LIMIT {$limite}
            ");
        }

        $resultado = [];
        foreach ($cursos as $c) {
            $item = [
                'id'                  => $c->id,
                'nodo_id'             => $c->nodo_id,
                'curso_id'            => $c->curso_id,
                'titulo'              => $c->titulo,
                'slug'                => $c->slug,
                'descripcion'         => wp_trim_words($c->descripcion, 30),
                'categoria'           => $c->categoria,
                'nivel'               => $c->nivel,
                'modalidad'           => $c->modalidad,
                'duracion_horas'      => floatval($c->duracion_horas),
                'numero_lecciones'    => (int) $c->numero_lecciones,
                'max_alumnos'         => (int) $c->max_alumnos,
                'inscritos_actuales'  => (int) $c->inscritos_actuales,
                'plazas_disponibles'  => (int) $c->max_alumnos - (int) $c->inscritos_actuales,
                'precio'              => floatval($c->precio),
                'es_gratuito'         => (bool) $c->es_gratuito,
                'ubicacion'           => $c->ubicacion,
                'instructor_nombre'   => $c->instructor_nombre,
                'valoracion_promedio' => floatval($c->valoracion_promedio),
                'imagen_url'          => $c->imagen_url,
                'fecha_inicio'        => $c->fecha_inicio,
                'fecha_fin'           => $c->fecha_fin,
            ];

            if (isset($c->distancia_km)) {
                $item['distancia_km'] = round($c->distancia_km, 1);
            }

            $resultado[] = $item;
        }

        return [
            'nodo'   => home_url(),
            'nombre' => get_bloginfo('name'),
            'cursos' => $resultado,
            'total'  => count($resultado),
        ];
    }

    /**
     * Endpoint: Obtener detalle de un curso federado
     */
    public function obtener_curso_detalle($request) {
        global $wpdb;

        $curso_id = absint($request->get_param('id'));
        $nodo_id = sanitize_text_field($request->get_param('nodo_id'));

        $tabla_cursos = $wpdb->prefix . 'flavor_network_courses';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_cursos'") !== $tabla_cursos) {
            return new WP_Error('no_tabla', 'Sistema de cursos federados no disponible', ['status' => 503]);
        }

        $where = "id = %d AND visible_en_red = 1";
        $params = [$curso_id];

        if (!empty($nodo_id)) {
            $where .= " AND nodo_id = %s";
            $params[] = $nodo_id;
        }

        $curso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_cursos} WHERE {$where}",
            $params
        ));

        if (!$curso) {
            return new WP_Error('no_encontrado', 'Curso no encontrado', ['status' => 404]);
        }

        return [
            'curso' => [
                'id'                  => $curso->id,
                'nodo_id'             => $curso->nodo_id,
                'curso_id'            => $curso->curso_id,
                'titulo'              => $curso->titulo,
                'slug'                => $curso->slug,
                'descripcion'         => $curso->descripcion,
                'categoria'           => $curso->categoria,
                'nivel'               => $curso->nivel,
                'modalidad'           => $curso->modalidad,
                'duracion_horas'      => floatval($curso->duracion_horas),
                'numero_lecciones'    => (int) $curso->numero_lecciones,
                'max_alumnos'         => (int) $curso->max_alumnos,
                'inscritos_actuales'  => (int) $curso->inscritos_actuales,
                'plazas_disponibles'  => (int) $curso->max_alumnos - (int) $curso->inscritos_actuales,
                'precio'              => floatval($curso->precio),
                'es_gratuito'         => (bool) $curso->es_gratuito,
                'ubicacion'           => $curso->ubicacion,
                'latitud'             => $curso->latitud,
                'longitud'            => $curso->longitud,
                'instructor_nombre'   => $curso->instructor_nombre,
                'valoracion_promedio' => floatval($curso->valoracion_promedio),
                'imagen_url'          => $curso->imagen_url,
                'fecha_inicio'        => $curso->fecha_inicio,
                'fecha_fin'           => $curso->fecha_fin,
                'estado'              => $curso->estado,
                'actualizado_en'      => $curso->actualizado_en,
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // BATCH SYNC (SINCRONIZACION MASIVA)
    // ═══════════════════════════════════════════════════════════

    /**
     * Endpoint: Sincronizacion masiva de items federados
     *
     * Permite sincronizar hasta 100 items en una sola peticion.
     * Soporta tipos: evento, producto, productor, servicio, espacio, anuncio, curso, taller
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function batch_sync($request) {
        global $wpdb;

        $items = $request->get_param('items');
        $nodo_id = $request->get_param('_nodo_id');

        if (!is_array($items) || count($items) > 100) {
            $this->log_federation_event('warning', 'Batch sync rechazado: items invalidos o excede limite', [
                'items_count' => is_array($items) ? count($items) : 'no_array',
                'nodo_id'     => $nodo_id,
            ]);
            return new WP_Error(
                'invalid_batch',
                __('Maximo 100 items por batch', 'flavor-platform'),
                ['status' => 400]
            );
        }

        if (empty($items)) {
            return new WP_Error(
                'empty_batch',
                __('El batch no puede estar vacio', 'flavor-platform'),
                ['status' => 400]
            );
        }

        // Verificar permisos ACL si el nodo esta registrado
        if ($nodo_id && class_exists('Flavor_Network_Node')) {
            $primer_tipo = sanitize_text_field($items[0]['tipo'] ?? '');
            if ($primer_tipo && !Flavor_Network_Node::check_permission($nodo_id, $primer_tipo, 'escribir')) {
                $this->log_federation_event('warning', 'Batch sync: permisos denegados', [
                    'nodo_id' => $nodo_id,
                    'tipo'    => $primer_tipo,
                ]);
                return new WP_Error(
                    'permission_denied',
                    __('El nodo no tiene permisos de escritura para este tipo de contenido', 'flavor-platform'),
                    ['status' => 403]
                );
            }
        }

        $resultados = [
            'procesados' => 0,
            'errores'    => [],
            'ids'        => [],
            'omitidos'   => 0,
        ];

        $wpdb->query('START TRANSACTION');

        try {
            foreach ($items as $indice => $item) {
                $tipo = sanitize_text_field($item['tipo'] ?? '');
                $datos = $item['datos'] ?? [];

                // Validar que el tipo existe
                if (empty($tipo)) {
                    $resultados['errores'][] = [
                        'index' => $indice,
                        'error' => __('Tipo de contenido no especificado', 'flavor-platform'),
                    ];
                    continue;
                }

                // Validar payload
                $validacion = $this->validate_federated_payload($tipo, $datos);
                if (is_wp_error($validacion)) {
                    $resultados['errores'][] = [
                        'index' => $indice,
                        'error' => $validacion->get_error_message(),
                    ];
                    continue;
                }

                // Verificar ACL por tipo si el nodo esta registrado
                if ($nodo_id && class_exists('Flavor_Network_Node')) {
                    if (!Flavor_Network_Node::check_permission($nodo_id, $tipo, 'escribir')) {
                        $resultados['errores'][] = [
                            'index' => $indice,
                            'error' => sprintf(
                                __('Sin permisos de escritura para tipo: %s', 'flavor-platform'),
                                $tipo
                            ),
                        ];
                        $resultados['omitidos']++;
                        continue;
                    }
                }

                // Procesar segun tipo
                $id_insertado = $this->process_batch_item($tipo, $datos, $nodo_id);
                if ($id_insertado) {
                    $resultados['procesados']++;
                    $resultados['ids'][] = [
                        'index' => $indice,
                        'tipo'  => $tipo,
                        'id'    => $id_insertado,
                    ];
                } else {
                    $resultados['errores'][] = [
                        'index' => $indice,
                        'error' => __('Error al insertar item', 'flavor-platform'),
                    ];
                }
            }

            $wpdb->query('COMMIT');

            $this->log_federation_event('info', 'Batch sync completado', [
                'nodo_id'    => $nodo_id,
                'procesados' => $resultados['procesados'],
                'errores'    => count($resultados['errores']),
                'omitidos'   => $resultados['omitidos'],
            ]);

        } catch (Exception $excepcion) {
            $wpdb->query('ROLLBACK');
            $this->log_federation_event('error', 'Batch sync fallido: ' . $excepcion->getMessage(), [
                'nodo_id' => $nodo_id,
            ]);
            return new WP_Error(
                'batch_error',
                $excepcion->getMessage(),
                ['status' => 500]
            );
        }

        // Hook para extensibilidad
        do_action('flavor_batch_sync_completed', $resultados, $nodo_id);

        return new WP_REST_Response($resultados, 200);
    }

    /**
     * Valida el payload de un item federado
     *
     * @param string $tipo Tipo de contenido
     * @param array $datos Datos del item
     * @return true|WP_Error
     */
    private function validate_federated_payload($tipo, $datos) {
        if (!is_array($datos) || empty($datos)) {
            return new WP_Error(
                'datos_invalidos',
                __('Los datos del item deben ser un array no vacio', 'flavor-platform')
            );
        }

        // Campos requeridos por tipo
        $campos_requeridos = [
            'evento'    => ['titulo', 'fecha_inicio'],
            'producto'  => ['nombre', 'precio'],
            'productor' => ['nombre'],
            'servicio'  => ['titulo'],
            'espacio'   => ['nombre'],
            'anuncio'   => ['titulo'],
            'curso'     => ['titulo'],
            'taller'    => ['titulo'],
            'viaje'     => ['origen', 'destino', 'fecha_salida'],
        ];

        $requeridos = $campos_requeridos[$tipo] ?? ['titulo'];

        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return new WP_Error(
                    'campo_faltante',
                    sprintf(
                        __('Campo requerido faltante para %s: %s', 'flavor-platform'),
                        $tipo,
                        $campo
                    )
                );
            }
        }

        return true;
    }

    /**
     * Procesa un item individual del batch
     *
     * @param string $tipo Tipo de contenido
     * @param array $datos Datos del item
     * @param int|null $nodo_id ID del nodo origen
     * @return int|null ID insertado o null si fallo
     */
    private function process_batch_item($tipo, $datos, $nodo_id = null) {
        global $wpdb;

        // Mapeo de tipos a tablas
        $mapeo_tablas = [
            'evento'    => 'events',
            'producto'  => 'products',
            'productor' => 'producers',
            'servicio'  => 'time_bank',
            'espacio'   => 'spaces',
            'anuncio'   => 'marketplace',
            'curso'     => 'courses',
            'taller'    => 'workshops',
            'viaje'     => 'carpooling',
        ];

        $nombre_tabla = $mapeo_tablas[$tipo] ?? null;
        if (!$nombre_tabla) {
            return null;
        }

        // Usar el instalador del addon si existe, si no usar prefijo generico
        if (class_exists('Flavor_Network_Installer')) {
            $tabla = Flavor_Network_Installer::get_table_name($nombre_tabla);
        } else {
            $tabla = $wpdb->prefix . 'flavor_network_' . $nombre_tabla;
        }

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return null;
        }

        // Sanitizar datos
        $datos_sanitizados = $this->sanitize_batch_item_data($tipo, $datos);

        // Anadir nodo_id si se proporciona
        if ($nodo_id) {
            $datos_sanitizados['nodo_id'] = $nodo_id;
        }

        // Anadir timestamp
        $datos_sanitizados['actualizado_en'] = current_time('mysql');

        // Verificar si ya existe (por nodo_id + id_original para evitar duplicados)
        $id_original = $datos['id_original'] ?? $datos['id'] ?? null;
        if ($id_original && $nodo_id) {
            $campo_id_tipo = $tipo . '_id';
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla} WHERE nodo_id = %s AND {$campo_id_tipo} = %d",
                $nodo_id,
                $id_original
            ));

            if ($existe) {
                // Actualizar en lugar de insertar
                $wpdb->update($tabla, $datos_sanitizados, ['id' => $existe]);
                return (int) $existe;
            }
        }

        // Insertar nuevo registro
        $resultado = $wpdb->insert($tabla, $datos_sanitizados);

        if ($resultado === false) {
            return null;
        }

        return $wpdb->insert_id;
    }

    /**
     * Sanitiza los datos de un item del batch segun su tipo
     *
     * @param string $tipo Tipo de contenido
     * @param array $datos Datos a sanitizar
     * @return array Datos sanitizados
     */
    private function sanitize_batch_item_data($tipo, $datos) {
        $sanitizados = [];

        // Campos de texto comunes
        $campos_texto = [
            'titulo', 'nombre', 'slug', 'tipo', 'categoria',
            'ubicacion', 'direccion', 'ciudad', 'estado', 'modalidad',
            'organizador_nombre', 'usuario_nombre', 'instructor_nombre',
            'conductor_nombre', 'origen', 'destino', 'notas', 'disponibilidad',
        ];

        foreach ($campos_texto as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = sanitize_text_field($datos[$campo]);
            }
        }

        // Campos de texto largo
        $campos_textarea = ['descripcion', 'contenido', 'requisitos', 'beneficios'];
        foreach ($campos_textarea as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = sanitize_textarea_field($datos[$campo]);
            }
        }

        // Campos numericos
        $campos_numericos = [
            'precio', 'precio_hora', 'precio_dia', 'precio_plaza',
            'horas_estimadas', 'duracion_horas', 'valoracion_promedio',
            'latitud', 'longitud', 'origen_lat', 'origen_lng',
            'destino_lat', 'destino_lng', 'radio_entrega_km',
        ];

        foreach ($campos_numericos as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = floatval($datos[$campo]);
            }
        }

        // Campos enteros
        $campos_enteros = [
            'aforo_maximo', 'inscritos_count', 'max_participantes',
            'inscritos_actuales', 'numero_sesiones', 'numero_lecciones',
            'max_alumnos', 'plazas_disponibles', 'plazas_totales',
            'capacidad_personas', 'superficie_m2', 'intercambios_completados',
            'evento_id', 'producto_id', 'productor_id', 'servicio_id',
            'espacio_id', 'anuncio_id', 'curso_id', 'taller_id', 'viaje_id',
        ];

        foreach ($campos_enteros as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = absint($datos[$campo]);
            }
        }

        // Campos booleanos
        $campos_booleanos = [
            'visible_en_red', 'es_online', 'es_gratuito', 'certificacion_eco',
            'acepta_mensajeria', 'envio_disponible', 'permite_equipaje',
            'permite_mascotas', 'compartir_en_red',
        ];

        foreach ($campos_booleanos as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = (int) (bool) $datos[$campo];
            }
        }

        // Campos de URL
        $campos_url = ['imagen_url', 'url_online', 'foto_principal', 'imagen_principal'];
        foreach ($campos_url as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = esc_url_raw($datos[$campo]);
            }
        }

        // Campos de fecha
        $campos_fecha = [
            'fecha_inicio', 'fecha_fin', 'fecha_salida', 'hora_salida',
            'fecha_primera_sesion', 'fecha_limite_inscripcion',
        ];

        foreach ($campos_fecha as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = sanitize_text_field($datos[$campo]);
            }
        }

        // Asegurar visible_en_red por defecto
        if (!isset($sanitizados['visible_en_red'])) {
            $sanitizados['visible_en_red'] = 1;
        }

        return $sanitizados;
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Federation_API::get_instance();
}, 15);
