<?php
/**
 * Interfaz para motores de IA
 *
 * Define el contrato que deben cumplir todos los proveedores de IA
 *
 * @package ChatIAAddon
 */

if (!defined('ABSPATH')) {
    exit;
}

interface Chat_IA_Engine_Interface {

    /**
     * Obtiene el ID único del proveedor
     *
     * @return string
     */
    public function get_id();

    /**
     * Obtiene el nombre del proveedor
     *
     * @return string
     */
    public function get_name();

    /**
     * Obtiene la descripción del proveedor
     *
     * @return string
     */
    public function get_description();

    /**
     * Verifica si el proveedor está configurado correctamente
     *
     * @return bool
     */
    public function is_configured();

    /**
     * Obtiene los modelos disponibles para este proveedor
     *
     * @return array ['model_id' => 'Nombre del modelo', ...]
     */
    public function get_available_models();

    /**
     * Envía un mensaje y obtiene la respuesta
     *
     * @param array $messages Historial de mensajes
     * @param string $system_prompt Prompt del sistema
     * @param array $tools Definiciones de herramientas (opcional)
     * @return array ['success' => bool, 'response' => string, 'tool_calls' => array, 'error' => string]
     */
    public function send_message($messages, $system_prompt, $tools = []);

    /**
     * Envía un mensaje con streaming y ejecuta callback por cada chunk
     *
     * @param array $messages Historial de mensajes
     * @param string $system_prompt Prompt del sistema
     * @param callable $callback Función callback($chunk_texto) llamada por cada token
     * @return array ['success' => bool, 'response' => string, 'error' => string]
     */
    public function send_message_stream($messages, $system_prompt, $callback);

    /**
     * Verifica si la API key es válida
     *
     * @param string $api_key
     * @return array ['valid' => bool, 'error' => string]
     */
    public function verify_api_key($api_key);

    /**
     * Obtiene los campos de configuración específicos del proveedor
     *
     * @return array
     */
    public function get_settings_fields();

    /**
     * Soporta herramientas/funciones
     *
     * @return bool
     */
    public function supports_tools();
}

/**
 * Clase base abstracta para motores de IA
 */
abstract class Chat_IA_Engine_Base implements Chat_IA_Engine_Interface {

    /**
     * Configuración del proveedor
     */
    protected $config = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_config();
    }

    /**
     * Carga la configuración del proveedor
     */
    protected function load_config() {
        $settings = get_option('flavor_chat_ia_settings', []);
        $provider_id = $this->get_id();

        // Cargar configuración con el formato {provider}_{key}
        $this->config = [
            'api_key' => $settings[$provider_id . '_api_key'] ?? '',
            'model' => $settings[$provider_id . '_model'] ?? '',
            'max_tokens' => $settings['max_tokens'] ?? 1000,
        ];

        // Compatibilidad con api_key legacy (Claude)
        if ($provider_id === 'claude' && empty($this->config['api_key'])) {
            $this->config['api_key'] = $settings['api_key'] ?? '';
        }
    }

    /**
     * Obtiene un valor de configuración
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get_config($key, $default = null) {
        if (empty($this->config)) {
            $this->load_config();
        }
        $value = $this->config[$key] ?? null;
        // Devolver default si el valor es null o cadena vacía
        if ($value === null || $value === '') {
            return $default;
        }
        return $value;
    }

    /**
     * Realiza una petición HTTP a la API con manejo de rate limit
     *
     * @param string $url
     * @param array $headers
     * @param array $body
     * @param int $timeout
     * @param int $max_retries Número máximo de reintentos para rate limit
     * @return array
     */
    protected function make_request($url, $headers, $body, $timeout = 60, $max_retries = 3) {
        $args = [
            'method' => 'POST',
            'timeout' => $timeout,
            'headers' => $headers,
            'body' => json_encode($body),
        ];

        $attempt = 0;
        $last_error = null;

        while ($attempt < $max_retries) {
            $attempt++;

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'error' => $response->get_error_message(),
                    'error_code' => 'connection_error',
                ];
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            // Rate limit (429) o overload (529) - reintentar con backoff
            if (in_array($status_code, [429, 529])) {
                $last_error = $this->extract_error_message($response_body, $status_code);

                // Obtener tiempo de espera del header o usar exponencial
                $retry_after = wp_remote_retrieve_header($response, 'retry-after');
                $wait_time = $retry_after ? (int) $retry_after : pow(2, $attempt);
                $wait_time = min($wait_time, 30); // Máximo 30 segundos

                if ($attempt < $max_retries) {
                    error_log("[Chat IA] Rate limit (HTTP {$status_code}), reintento {$attempt}/{$max_retries} en {$wait_time}s");
                    sleep($wait_time);
                    continue;
                }
            }

            if ($status_code !== 200) {
                $error_message = $this->extract_error_message($response_body, $status_code);
                return [
                    'success' => false,
                    'error' => $error_message,
                    'error_code' => 'api_error',
                    'status_code' => $status_code,
                ];
            }

            return [
                'success' => true,
                'data' => $response_body,
            ];
        }

        // Agotados los reintentos
        return [
            'success' => false,
            'error' => $last_error ?: 'Límite de peticiones excedido. Espera unos segundos e inténtalo de nuevo.',
            'error_code' => 'rate_limit_exceeded',
            'status_code' => 429,
        ];
    }

    /**
     * Extrae el mensaje de error de la respuesta
     *
     * @param array $body
     * @param int $status_code
     * @return string
     */
    protected function extract_error_message($body, $status_code) {
        // Log para debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Chat IA Engine] Error response: ' . json_encode($body));
        }

        if (isset($body['error']['message'])) {
            return $body['error']['message'];
        }
        if (isset($body['message'])) {
            return $body['message'];
        }
        if (isset($body['error']) && is_string($body['error'])) {
            return $body['error'];
        }
        return "Error HTTP {$status_code}";
    }

    /**
     * Implementación por defecto de streaming: fallback a respuesta completa
     */
    public function send_message_stream($messages, $system_prompt, $callback) {
        $resultado = $this->send_message($messages, $system_prompt);
        if ($resultado['success'] && !empty($resultado['response'])) {
            call_user_func($callback, $resultado['response']);
        }
        return $resultado;
    }

    /**
     * Realiza una petición HTTP con streaming
     *
     * @param string $url URL de la API
     * @param array $headers Headers HTTP
     * @param array $body Body de la petición
     * @param callable $callback_linea Callback para cada línea SSE recibida
     * @param int $timeout Timeout en segundos
     * @return array ['success' => bool, 'error' => string]
     */
    protected function make_stream_request($url, $headers, $body, $callback_linea, $timeout = 120) {
        $respuesta_completa = '';
        $error_detectado = null;

        $headers_curl = [];
        foreach ($headers as $clave => $valor) {
            $headers_curl[] = "{$clave}: {$valor}";
        }

        $curl_handle = curl_init($url);

        curl_setopt_array($curl_handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $headers_curl,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_WRITEFUNCTION => function($ch, $data) use ($callback_linea, &$respuesta_completa, &$error_detectado) {
                $respuesta_completa .= $data;

                // Procesar líneas SSE
                $lineas = explode("\n", $data);
                foreach ($lineas as $linea) {
                    $linea = trim($linea);
                    if (empty($linea) || $linea === 'event: ping') {
                        continue;
                    }
                    if (strpos($linea, 'data: ') === 0) {
                        $datos_linea = substr($linea, 6);
                        if ($datos_linea === '[DONE]') {
                            continue;
                        }
                        call_user_func($callback_linea, $datos_linea);
                    }
                }

                return strlen($data);
            },
        ]);

        $resultado_curl = curl_exec($curl_handle);
        $codigo_http = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        $error_curl = curl_error($curl_handle);
        curl_close($curl_handle);

        if ($resultado_curl === false) {
            return [
                'success' => false,
                'error' => $error_curl ?: 'Error de conexión',
                'error_code' => 'connection_error',
            ];
        }

        if ($codigo_http !== 200) {
            $cuerpo_error = json_decode($respuesta_completa, true);
            return [
                'success' => false,
                'error' => $this->extract_error_message($cuerpo_error, $codigo_http),
                'error_code' => 'api_error',
                'status_code' => $codigo_http,
            ];
        }

        return ['success' => true];
    }

    /**
     * Formatea los mensajes al formato del proveedor
     *
     * @param array $messages
     * @return array
     */
    abstract protected function format_messages($messages);

    /**
     * Formatea las herramientas al formato del proveedor
     *
     * @param array $tools
     * @return array
     */
    abstract protected function format_tools($tools);

    /**
     * Parsea la respuesta del proveedor
     *
     * @param array $response
     * @return array
     */
    abstract protected function parse_response($response);
}
