<?php
/**
 * Sistema de Requests Asíncronas para el Mesh P2P
 *
 * Implementa requests no bloqueantes para mejorar el rendimiento
 * al comunicarse con múltiples peers.
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mesh_Async {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Cola de requests pendientes
     *
     * @var array
     */
    private $request_queue = [];

    /**
     * Constructor privado
     */
    private function __construct() {}

    /**
     * Obtiene la instancia singleton
     *
     * @return self
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ═══════════════════════════════════════════════════════════════════
    // REQUESTS PARALELAS CON CURL MULTI
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Envía múltiples requests en paralelo usando curl_multi
     *
     * @param array $requests Array de requests [['url' => '', 'data' => [], 'headers' => []], ...]
     * @param int   $timeout  Timeout por request en segundos
     * @return array Resultados indexados igual que $requests
     */
    public function send_parallel(array $requests, $timeout = 5) {
        if (empty($requests)) {
            return [];
        }

        // Si solo hay una request, usar método simple
        if (count($requests) === 1) {
            $result = $this->send_single($requests[0], $timeout);
            return [$result];
        }

        // Verificar que curl_multi está disponible
        if (!function_exists('curl_multi_init')) {
            return $this->send_sequential($requests, $timeout);
        }

        $multi_handle = curl_multi_init();
        $curl_handles = [];
        $results = [];

        // Crear handles individuales
        foreach ($requests as $index => $request) {
            $curl_handle = $this->create_curl_handle($request, $timeout);
            curl_multi_add_handle($multi_handle, $curl_handle);
            $curl_handles[$index] = $curl_handle;
        }

        // Ejecutar en paralelo
        $running = null;
        do {
            $status = curl_multi_exec($multi_handle, $running);
            if ($status > CURLM_OK) {
                break;
            }
            // Esperar actividad en los sockets
            if ($running > 0) {
                curl_multi_select($multi_handle, 0.1);
            }
        } while ($running > 0);

        // Recoger resultados
        foreach ($curl_handles as $index => $curl_handle) {
            $response = curl_multi_getcontent($curl_handle);
            $http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            $error = curl_error($curl_handle);

            $results[$index] = [
                'success'   => ($http_code >= 200 && $http_code < 300),
                'http_code' => $http_code,
                'body'      => $response,
                'error'     => $error ?: null,
            ];

            curl_multi_remove_handle($multi_handle, $curl_handle);
            curl_close($curl_handle);
        }

        curl_multi_close($multi_handle);

        return $results;
    }

    /**
     * Crea un handle de curl configurado
     *
     * @param array $request Datos del request
     * @param int   $timeout Timeout
     * @return resource
     */
    private function create_curl_handle(array $request, $timeout) {
        $curl_handle = curl_init();

        $url = $request['url'];
        $data = $request['data'] ?? null;
        $headers = $request['headers'] ?? [];
        $method = $request['method'] ?? ($data ? 'POST' : 'GET');

        // Headers por defecto
        $default_headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: FlavorMesh/1.5',
        ];

        // Merge headers
        $final_headers = $default_headers;
        foreach ($headers as $key => $value) {
            if (is_numeric($key)) {
                $final_headers[] = $value;
            } else {
                $final_headers[] = "{$key}: {$value}";
            }
        }

        curl_setopt_array($curl_handle, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(2, $timeout),
            CURLOPT_HTTPHEADER     => $final_headers,
            CURLOPT_SSL_VERIFYPEER => apply_filters('flavor_mesh_ssl_verify', true),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);

        if ($method === 'POST' && $data) {
            curl_setopt($curl_handle, CURLOPT_POST, true);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, is_array($data) ? wp_json_encode($data) : $data);
        }

        return $curl_handle;
    }

    /**
     * Envía requests secuencialmente (fallback)
     *
     * @param array $requests
     * @param int   $timeout
     * @return array
     */
    private function send_sequential(array $requests, $timeout) {
        $results = [];
        foreach ($requests as $index => $request) {
            $results[$index] = $this->send_single($request, $timeout);
        }
        return $results;
    }

    /**
     * Envía una única request
     *
     * @param array $request
     * @param int   $timeout
     * @return array
     */
    private function send_single(array $request, $timeout) {
        $args = [
            'timeout'   => $timeout,
            'headers'   => array_merge(
                ['Content-Type' => 'application/json'],
                $request['headers'] ?? []
            ),
            'sslverify' => apply_filters('flavor_mesh_ssl_verify', true),
        ];

        if (!empty($request['data'])) {
            $args['body'] = wp_json_encode($request['data']);
            $response = wp_remote_post($request['url'], $args);
        } else {
            $response = wp_remote_get($request['url'], $args);
        }

        if (is_wp_error($response)) {
            return [
                'success'   => false,
                'http_code' => 0,
                'body'      => null,
                'error'     => $response->get_error_message(),
            ];
        }

        $http_code = wp_remote_retrieve_response_code($response);

        return [
            'success'   => ($http_code >= 200 && $http_code < 300),
            'http_code' => $http_code,
            'body'      => wp_remote_retrieve_body($response),
            'error'     => null,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // FIRE-AND-FORGET (NO BLOQUEA)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Envía un request sin esperar respuesta (fire-and-forget)
     *
     * Usa socket directo con timeout mínimo para no bloquear.
     *
     * @param string $url     URL destino
     * @param array  $data    Datos a enviar
     * @param array  $headers Headers adicionales
     * @return bool True si se envió (no garantiza recepción)
     */
    public function fire_and_forget($url, array $data = [], array $headers = []) {
        $parts = parse_url($url);

        if (!$parts || empty($parts['host'])) {
            return false;
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        // Usar SSL si es HTTPS
        $socket_host = ($parts['scheme'] === 'https' ? 'ssl://' : '') . $host;

        // Preparar body
        $body = wp_json_encode($data);

        // Preparar headers
        $request_headers = [
            "POST {$path} HTTP/1.1",
            "Host: {$host}",
            "Content-Type: application/json",
            "Content-Length: " . strlen($body),
            "Connection: close",
            "User-Agent: FlavorMesh/1.5",
        ];

        foreach ($headers as $key => $value) {
            $request_headers[] = "{$key}: {$value}";
        }

        $request = implode("\r\n", $request_headers) . "\r\n\r\n" . $body;

        // Abrir socket con timeout mínimo
        $fp = @fsockopen($socket_host, $port, $errno, $errstr, 0.5);

        if (!$fp) {
            return false;
        }

        // Configurar socket como no bloqueante
        stream_set_blocking($fp, false);
        stream_set_timeout($fp, 0, 500000); // 500ms

        // Escribir y cerrar inmediatamente
        fwrite($fp, $request);
        fclose($fp);

        return true;
    }

    // ═══════════════════════════════════════════════════════════════════
    // COLA DE REQUESTS (PARA CRON)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Añade un request a la cola para procesamiento posterior
     *
     * @param string $url
     * @param array  $data
     * @param array  $headers
     * @param string $priority low|normal|high
     */
    public function queue_request($url, array $data, array $headers = [], $priority = 'normal') {
        $queue = get_option('flavor_mesh_request_queue', []);

        $queue[] = [
            'url'      => $url,
            'data'     => $data,
            'headers'  => $headers,
            'priority' => $priority,
            'queued'   => time(),
        ];

        // Ordenar por prioridad
        usort($queue, function($a, $b) {
            $priority_order = ['high' => 0, 'normal' => 1, 'low' => 2];
            return ($priority_order[$a['priority']] ?? 1) - ($priority_order[$b['priority']] ?? 1);
        });

        // Limitar cola a 1000 items
        $queue = array_slice($queue, 0, 1000);

        update_option('flavor_mesh_request_queue', $queue, false);
    }

    /**
     * Procesa la cola de requests
     *
     * @param int $batch_size Número de requests por batch
     * @return int Número de requests procesadas
     */
    public function process_queue($batch_size = 20) {
        $queue = get_option('flavor_mesh_request_queue', []);

        if (empty($queue)) {
            return 0;
        }

        // Tomar batch
        $batch = array_slice($queue, 0, $batch_size);
        $remaining = array_slice($queue, $batch_size);

        // Preparar requests
        $requests = [];
        foreach ($batch as $item) {
            $requests[] = [
                'url'     => $item['url'],
                'data'    => $item['data'],
                'headers' => $item['headers'],
            ];
        }

        // Enviar en paralelo
        $results = $this->send_parallel($requests, 10);

        // Guardar cola restante
        update_option('flavor_mesh_request_queue', $remaining, false);

        // Contar éxitos
        $success_count = count(array_filter($results, function($r) {
            return $r['success'];
        }));

        return $success_count;
    }

    /**
     * Obtiene el tamaño de la cola
     *
     * @return int
     */
    public function get_queue_size() {
        $queue = get_option('flavor_mesh_request_queue', []);
        return count($queue);
    }

    // ═══════════════════════════════════════════════════════════════════
    // BROADCAST A MÚLTIPLES PEERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Envía un mensaje a múltiples peers en paralelo
     *
     * @param array  $peers    Array de peers con site_url
     * @param string $endpoint Endpoint relativo (ej: /wp-json/flavor-mesh/v1/gossip/receive)
     * @param array  $data     Datos a enviar
     * @param array  $headers  Headers adicionales
     * @param int    $timeout  Timeout por request
     * @return array Resultados por peer_id
     */
    public function broadcast_to_peers(array $peers, $endpoint, array $data, array $headers = [], $timeout = 5) {
        $requests = [];
        $peer_map = [];

        foreach ($peers as $index => $peer) {
            if (empty($peer->site_url)) {
                continue;
            }

            $url = rtrim($peer->site_url, '/') . $endpoint;
            $peer_id = $peer->peer_id ?? $index;

            $requests[] = [
                'url'     => $url,
                'data'    => $data,
                'headers' => $headers,
            ];

            $peer_map[] = $peer_id;
        }

        if (empty($requests)) {
            return [];
        }

        $results = $this->send_parallel($requests, $timeout);

        // Mapear resultados a peer_id
        $mapped_results = [];
        foreach ($results as $index => $result) {
            $peer_id = $peer_map[$index] ?? $index;
            $mapped_results[$peer_id] = $result;
        }

        return $mapped_results;
    }
}

/**
 * Helper function para acceder al sistema async
 *
 * @return Flavor_Mesh_Async
 */
function flavor_mesh_async() {
    return Flavor_Mesh_Async::instance();
}
