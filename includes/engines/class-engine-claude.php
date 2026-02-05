<?php
/**
 * Motor de IA para Claude (Anthropic)
 *
 * @package ChatIAAddon
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chat_IA_Engine_Claude extends Chat_IA_Engine_Base {

    const API_URL = 'https://api.anthropic.com/v1/messages';
    const MODELS_URL = 'https://api.anthropic.com/v1/models';
    const API_VERSION = '2023-06-01';

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'claude';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return 'Claude (Anthropic)';
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __('Claude de Anthropic. Excelente para conversaciones naturales y seguimiento de instrucciones.', 'chat-ia-addon');
    }

    /**
     * @inheritdoc
     */
    public function is_configured() {
        return !empty($this->get_config('api_key'));
    }

    /**
     * Obtiene modelos disponibles desde la API de Anthropic
     * Los cachea por 24 horas para evitar llamadas excesivas
     *
     * @return array
     */
    public function get_available_models() {
        // Intentar obtener de caché
        $cached_models = get_transient('chat_ia_claude_models');
        if ($cached_models !== false) {
            return $cached_models;
        }

        // Intentar obtener de la API
        $api_models = $this->fetch_models_from_api();
        if (!empty($api_models)) {
            // Cachear por 24 horas
            set_transient('chat_ia_claude_models', $api_models, DAY_IN_SECONDS);
            return $api_models;
        }

        // Fallback a modelos por defecto si la API falla
        return $this->get_default_models();
    }

    /**
     * Obtiene modelos desde la API de Anthropic
     *
     * @return array
     */
    private function fetch_models_from_api() {
        $api_key = $this->get_config('api_key');

        if (empty($api_key)) {
            return [];
        }

        $response = wp_remote_get(self::MODELS_URL, [
            'timeout' => 15,
            'headers' => [
                'x-api-key' => $api_key,
                'anthropic-version' => self::API_VERSION,
            ],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['data']) || !is_array($body['data'])) {
            return [];
        }

        $models = [];

        foreach ($body['data'] as $model) {
            $id = $model['id'] ?? '';
            $name = $model['display_name'] ?? $id;

            if (empty($id)) {
                continue;
            }

            // Añadir etiquetas descriptivas
            $label = $name;
            if (strpos($id, 'opus') !== false) {
                $label .= ' (Más capaz)';
            } elseif (strpos($id, 'sonnet') !== false) {
                $label .= ' (Equilibrado)';
            } elseif (strpos($id, 'haiku') !== false) {
                $label .= ' (Rápido)';
            }

            $models[$id] = $label;
        }

        // Ordenar: primero los más recientes/capaces
        uksort($models, function($a, $b) {
            // Priorizar por familia (4 > 3.5 > 3)
            $priority_a = $this->get_model_priority($a);
            $priority_b = $this->get_model_priority($b);
            return $priority_b - $priority_a;
        });

        return $models;
    }

    /**
     * Obtiene la prioridad de un modelo para ordenamiento
     *
     * @param string $model_id
     * @return int
     */
    private function get_model_priority($model_id) {
        if (strpos($model_id, 'claude-4') !== false || strpos($model_id, 'claude-sonnet-4') !== false) {
            return 100;
        }
        if (strpos($model_id, 'claude-3-5') !== false || strpos($model_id, 'claude-3.5') !== false) {
            return 90;
        }
        if (strpos($model_id, 'opus') !== false) {
            return 85;
        }
        if (strpos($model_id, 'sonnet') !== false) {
            return 80;
        }
        if (strpos($model_id, 'haiku') !== false) {
            return 70;
        }
        return 50;
    }

    /**
     * Modelos por defecto si la API no está disponible
     *
     * @return array
     */
    private function get_default_models() {
        return [
            'claude-3-5-haiku-latest' => 'Claude 3.5 Haiku (Recomendado - Económico)',
            'claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Más capaz)',
            'claude-3-5-sonnet-latest' => 'Claude 3.5 Sonnet',
            'claude-3-opus-latest' => 'Claude 3 Opus (Premium)',
        ];
    }

    /**
     * Fuerza actualización del caché de modelos
     *
     * @return array
     */
    public function refresh_models_cache() {
        delete_transient('chat_ia_claude_models');
        return $this->get_available_models();
    }

    /**
     * @inheritdoc
     */
    public function supports_tools() {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function send_message($messages, $system_prompt, $tools = []) {
        $api_key = $this->get_config('api_key');
        $model = $this->get_config('model', 'claude-3-5-haiku-latest');
        $max_tokens = (int) $this->get_config('max_tokens', 1000);

        if (empty($api_key)) {
            return [
                'success' => false,
                'error' => 'API key de Claude no configurada',
                'error_code' => 'no_api_key',
            ];
        }

        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => self::API_VERSION,
        ];

        $body = [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'system' => $system_prompt,
            'messages' => $this->format_messages($messages),
        ];

        if (!empty($tools)) {
            $body['tools'] = $this->format_tools($tools);
        }

        $response = $this->make_request(self::API_URL, $headers, $body, 120);

        if (!$response['success']) {
            // Intentar con modelo fallback (alias latest siempre funciona)
            if (isset($response['status_code']) && $response['status_code'] === 400) {
                $body['model'] = 'claude-3-5-haiku-latest';
                $response = $this->make_request(self::API_URL, $headers, $body, 120);
            }
        }

        if (!$response['success']) {
            return $response;
        }

        return $this->parse_response($response['data']);
    }

    /**
     * Envía mensaje con streaming via API Anthropic
     */
    public function send_message_stream($messages, $system_prompt, $callback) {
        $api_key = $this->get_config('api_key');
        $model = $this->get_config('model', 'claude-3-5-haiku-latest');
        $max_tokens = (int) $this->get_config('max_tokens', 1000);

        if (empty($api_key)) {
            return ['success' => false, 'error' => 'API key de Claude no configurada', 'error_code' => 'no_api_key'];
        }

        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => self::API_VERSION,
        ];

        $body = [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'system' => $system_prompt,
            'messages' => $this->format_messages($messages),
            'stream' => true,
        ];

        $respuesta_texto = '';

        $resultado = $this->make_stream_request(
            self::API_URL,
            $headers,
            $body,
            function($datos_json) use ($callback, &$respuesta_texto) {
                $datos = json_decode($datos_json, true);
                if (!$datos) return;

                $tipo_evento = $datos['type'] ?? '';

                if ($tipo_evento === 'content_block_delta') {
                    $delta = $datos['delta'] ?? [];
                    if (($delta['type'] ?? '') === 'text_delta') {
                        $fragmento_texto = $delta['text'] ?? '';
                        if (!empty($fragmento_texto)) {
                            $respuesta_texto .= $fragmento_texto;
                            call_user_func($callback, $fragmento_texto);
                        }
                    }
                }
            }
        );

        if (!$resultado['success']) {
            return $resultado;
        }

        return [
            'success' => true,
            'response' => $respuesta_texto,
            'tool_calls' => [],
            'stop_reason' => 'end_turn',
        ];
    }

    /**
     * @inheritdoc
     */
    public function verify_api_key($api_key) {
        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => self::API_VERSION,
        ];

        $body = [
            'model' => 'claude-3-5-haiku-latest',
            'max_tokens' => 10,
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ];

        $response = $this->make_request(self::API_URL, $headers, $body, 30);

        return [
            'valid' => $response['success'],
            'error' => $response['error'] ?? null,
        ];
    }

    /**
     * @inheritdoc
     */
    public function get_settings_fields() {
        return [
            'api_key' => [
                'type' => 'password',
                'label' => __('API Key', 'chat-ia-addon'),
                'description' => sprintf(
                    __('Obtén tu API key en %s', 'chat-ia-addon'),
                    '<a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a>'
                ),
                'required' => true,
            ],
            'model' => [
                'type' => 'select',
                'label' => __('Modelo', 'chat-ia-addon'),
                'options' => $this->get_available_models(),
                'default' => 'claude-3-5-haiku-latest',
                'description' => __('Haiku es más económico (~75% ahorro). Sonnet/Opus para tareas complejas.', 'chat-ia-addon'),
            ],
        ];
    }

    /**
     * @inheritdoc
     *
     * Formatea mensajes al formato de Claude, incluyendo tool_use y tool_result
     */
    protected function format_messages($messages) {
        $formatted = [];

        foreach ($messages as $msg) {
            $role = $msg['role'];
            $content = $msg['content'] ?? '';

            // Saltar mensajes de sistema
            if ($role === 'system') {
                continue;
            }

            // Si el mensaje tiene tool_calls (assistant con tool_use)
            if ($role === 'assistant' && !empty($msg['tool_calls'])) {
                $content_blocks = [];

                // Añadir texto si existe
                if (!empty($content) && is_string($content)) {
                    $content_blocks[] = [
                        'type' => 'text',
                        'text' => $content,
                    ];
                }

                // Añadir tool_use blocks
                foreach ($msg['tool_calls'] as $tool_call) {
                    $content_blocks[] = [
                        'type' => 'tool_use',
                        'id' => $tool_call['id'],
                        'name' => $tool_call['name'],
                        'input' => $tool_call['arguments'] ?? (object)[],
                    ];
                }

                $formatted[] = [
                    'role' => 'assistant',
                    'content' => $content_blocks,
                ];
                continue;
            }

            // Si el contenido ya es un array (viene de continue_with_tool_results_unified)
            if ($role === 'assistant' && is_array($content)) {
                // Verificar si contiene tool_use blocks
                $has_tool_use = false;
                foreach ($content as $item) {
                    if (isset($item['type']) && $item['type'] === 'tool_use') {
                        $has_tool_use = true;
                        break;
                    }
                }
                if ($has_tool_use) {
                    $formatted[] = [
                        'role' => 'assistant',
                        'content' => $content,
                    ];
                    continue;
                }
            }

            // Si es un mensaje de tool_result
            if ($role === 'user' && !empty($content)) {
                // Si ya es un array (viene de continue_with_tool_results_unified)
                if (is_array($content)) {
                    // Verificar si es un array de tool_results
                    $is_tool_results = false;
                    foreach ($content as $item) {
                        if (isset($item['type']) && $item['type'] === 'tool_result') {
                            $is_tool_results = true;
                            break;
                        }
                    }
                    if ($is_tool_results) {
                        $formatted[] = [
                            'role' => 'user',
                            'content' => $content,
                        ];
                        continue;
                    }
                }

                // Si es una cadena JSON, intentar decodificar
                if (is_string($content)) {
                    $decoded = json_decode($content, true);
                    if ($decoded !== null && isset($decoded['type']) && $decoded['type'] === 'tool_result') {
                        $content_blocks = [];
                        $tool_results = $decoded['tool_results'] ?? [$decoded];

                        if (is_array($tool_results) && !empty($tool_results)) {
                            foreach ($tool_results as $result) {
                                if (isset($result['tool_use_id'])) {
                                    $content_blocks[] = [
                                        'type' => 'tool_result',
                                        'tool_use_id' => $result['tool_use_id'],
                                        'content' => $result['content'] ?? '',
                                    ];
                                }
                            }
                        }

                        if (!empty($content_blocks)) {
                            $formatted[] = [
                                'role' => 'user',
                                'content' => $content_blocks,
                            ];
                            continue;
                        }
                    }
                }
            }

            // Mensaje normal - saltar si está vacío
            if (empty($content)) {
                continue;
            }

            // Si el contenido es un string, añadirlo directamente
            if (is_string($content)) {
                $formatted[] = [
                    'role' => $role,
                    'content' => $content,
                ];
                continue;
            }

            // Si el contenido es un array (con blocks de text u otro formato), añadirlo tal cual
            if (is_array($content)) {
                $formatted[] = [
                    'role' => $role,
                    'content' => $content,
                ];
                continue;
            }
        }

        return $formatted;
    }

    /**
     * @inheritdoc
     *
     * Asegura que los input_schema tengan el formato correcto para Claude.
     * Claude requiere que 'properties' sea un objeto JSON, no un array vacío.
     */
    protected function format_tools($tools) {
        $formatted = [];

        foreach ($tools as $tool) {
            $formatted_tool = $tool;

            // Asegurar que input_schema tenga el formato correcto
            if (isset($formatted_tool['input_schema'])) {
                // Si properties está vacío o es un array, convertirlo a objeto vacío
                if (!isset($formatted_tool['input_schema']['properties'])
                    || empty($formatted_tool['input_schema']['properties'])
                    || $formatted_tool['input_schema']['properties'] === []) {
                    $formatted_tool['input_schema']['properties'] = (object)[];
                }

                // Asegurar que required sea un array (puede estar vacío)
                if (!isset($formatted_tool['input_schema']['required'])) {
                    $formatted_tool['input_schema']['required'] = [];
                }
            }

            $formatted[] = $formatted_tool;
        }

        return $formatted;
    }

    /**
     * @inheritdoc
     */
    protected function parse_response($data) {
        $content = $data['content'] ?? [];
        $stop_reason = $data['stop_reason'] ?? 'end_turn';

        $text_response = '';
        $tool_calls = [];

        foreach ($content as $block) {
            if ($block['type'] === 'text') {
                $text_response .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                // Asegurar que input sea un objeto, no un array vacío
                $input = $block['input'] ?? [];
                if (empty($input) || $input === []) {
                    $input = (object)[];
                }

                $tool_calls[] = [
                    'id' => $block['id'],
                    'name' => $block['name'],
                    'arguments' => $input,
                ];
            }
        }

        return [
            'success' => true,
            'response' => $text_response,
            'tool_calls' => $tool_calls,
            'stop_reason' => $stop_reason,
            'usage' => $data['usage'] ?? [],
            'raw_content' => $content,
        ];
    }
}
