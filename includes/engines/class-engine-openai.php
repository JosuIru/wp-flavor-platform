<?php
/**
 * Motor de IA para OpenAI (GPT)
 *
 * @package ChatIAAddon
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chat_IA_Engine_OpenAI extends Chat_IA_Engine_Base {

    const API_URL = 'https://api.openai.com/v1/chat/completions';

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'openai';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return 'OpenAI (GPT)';
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __('GPT-4 y GPT-3.5 de OpenAI. Ampliamente usado y con buena documentación.', 'chat-ia-addon');
    }

    /**
     * @inheritdoc
     */
    public function is_configured() {
        return !empty($this->get_config('api_key'));
    }

    /**
     * @inheritdoc
     */
    public function get_available_models() {
        return [
            'gpt-4o' => 'GPT-4o (Recomendado)',
            'gpt-4o-mini' => 'GPT-4o Mini (Económico)',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Más rápido)',
        ];
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
        $model = $this->get_config('model', 'gpt-4o-mini');
        $max_tokens = (int) $this->get_config('max_tokens', 1000);

        if (empty($api_key)) {
            return [
                'success' => false,
                'error' => 'API key no configurada',
                'error_code' => 'no_api_key',
            ];
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];

        $formatted_messages = $this->format_messages($messages, $system_prompt);

        $body = [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => $formatted_messages,
        ];

        if (!empty($tools)) {
            $body['tools'] = $this->format_tools($tools);
            $body['tool_choice'] = 'auto';
        }

        $response = $this->make_request(self::API_URL, $headers, $body, 120);

        if (!$response['success']) {
            return $response;
        }

        return $this->parse_response($response['data']);
    }

    /**
     * Envía mensaje con streaming via API OpenAI
     */
    public function send_message_stream($messages, $system_prompt, $callback) {
        $api_key = $this->get_config('api_key');
        $model = $this->get_config('model', 'gpt-4o-mini');
        $max_tokens = (int) $this->get_config('max_tokens', 1000);

        if (empty($api_key)) {
            return ['success' => false, 'error' => 'API key no configurada', 'error_code' => 'no_api_key'];
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];

        $mensajes_formateados = $this->format_messages($messages, $system_prompt);

        $body = [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => $mensajes_formateados,
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

                $delta = $datos['choices'][0]['delta'] ?? [];
                $contenido = $delta['content'] ?? '';
                if (!empty($contenido)) {
                    $respuesta_texto .= $contenido;
                    call_user_func($callback, $contenido);
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
            'stop_reason' => 'stop',
        ];
    }

    /**
     * @inheritdoc
     */
    public function verify_api_key($api_key) {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];

        $body = [
            'model' => 'gpt-3.5-turbo',
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
                    '<a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>'
                ),
                'required' => true,
            ],
            'model' => [
                'type' => 'select',
                'label' => __('Modelo', 'chat-ia-addon'),
                'options' => $this->get_available_models(),
                'default' => 'gpt-4o-mini',
            ],
        ];
    }

    /**
     * Formatea mensajes al formato OpenAI
     */
    protected function format_messages($messages, $system_prompt = '') {
        $formatted = [];

        if (!empty($system_prompt)) {
            $formatted[] = [
                'role' => 'system',
                'content' => $system_prompt,
            ];
        }

        foreach ($messages as $msg) {
            $formatted[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        return $formatted;
    }

    /**
     * Formatea tools al formato OpenAI
     */
    protected function format_tools($tools) {
        $formatted = [];

        foreach ($tools as $tool) {
            $formatted[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'] ?? '',
                    'parameters' => $tool['input_schema'] ?? $tool['parameters'] ?? ['type' => 'object', 'properties' => []],
                ],
            ];
        }

        return $formatted;
    }

    /**
     * @inheritdoc
     */
    protected function parse_response($data) {
        $choice = $data['choices'][0] ?? [];
        $message = $choice['message'] ?? [];
        $finish_reason = $choice['finish_reason'] ?? 'stop';

        $text_response = $message['content'] ?? '';
        $tool_calls = [];

        if (!empty($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $tc) {
                $tool_calls[] = [
                    'id' => $tc['id'],
                    'name' => $tc['function']['name'],
                    'arguments' => json_decode($tc['function']['arguments'], true) ?? [],
                ];
            }
        }

        return [
            'success' => true,
            'response' => $text_response,
            'tool_calls' => $tool_calls,
            'stop_reason' => $finish_reason,
            'usage' => $data['usage'] ?? [],
        ];
    }
}
