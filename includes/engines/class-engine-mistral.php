<?php
/**
 * Motor de IA para Mistral AI
 *
 * Mistral ofrece modelos con tier gratuito (1M tokens/mes)
 *
 * @package ChatIAAddon
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chat_IA_Engine_Mistral extends Chat_IA_Engine_Base {

    const API_URL = 'https://api.mistral.ai/v1/chat/completions';

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'mistral';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return 'Mistral AI';
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __('Mistral AI ofrece modelos europeos de alta calidad. Tier gratuito: 1M tokens/mes.', 'chat-ia-addon');
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
            'mistral-small-latest' => 'Mistral Small (Gratuito - Recomendado)',
            'mistral-medium-latest' => 'Mistral Medium',
            'mistral-large-latest' => 'Mistral Large (Más capaz)',
            'open-mistral-nemo' => 'Mistral Nemo (Open source)',
            'codestral-latest' => 'Codestral (Código)',
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
        $model = $this->get_config('model', 'mistral-small-latest');
        $max_tokens = (int) $this->get_config('max_tokens', 1000);

        if (empty($api_key)) {
            return [
                'success' => false,
                'error' => __('API key de Mistral no configurada', 'flavor-chat-ia'),
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
     * @inheritdoc
     */
    public function verify_api_key($api_key) {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];

        $body = [
            'model' => 'mistral-small-latest',
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
                    __('Obtén tu API key gratis en %s (tier gratuito disponible)', 'chat-ia-addon'),
                    '<a href="https://console.mistral.ai/" target="_blank">console.mistral.ai</a>'
                ),
                'required' => true,
            ],
            'model' => [
                'type' => 'select',
                'label' => __('Modelo', 'chat-ia-addon'),
                'options' => $this->get_available_models(),
                'default' => 'mistral-small-latest',
            ],
        ];
    }

    /**
     * Formatea mensajes al formato Mistral (compatible con OpenAI)
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
     * Formatea tools al formato Mistral (compatible con OpenAI)
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
