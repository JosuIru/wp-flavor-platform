<?php
/**
 * Motor de integración con Claude API
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Claude_Engine {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Contador de profundidad de recursión para tool_use
     */
    private $recursion_depth = 0;

    /**
     * Máxima profundidad de recursión permitida
     */
    const MAX_RECURSION_DEPTH = 5;

    /**
     * Motor de IA activo (para llamadas directas legacy)
     * @deprecated Usar Flavor_Engine_Manager
     */
    const API_URL = 'https://api.anthropic.com/v1/messages';
    const API_VERSION = '2023-06-01';
    const MODEL = 'claude-sonnet-4-20250514';
    const MODEL_FALLBACK = 'claude-3-5-sonnet-20241022';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Platform_Claude_Engine
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {}

    /**
     * Obtiene el gestor de motores de IA
     *
     * @return Flavor_Engine_Manager|null
     */
    private function get_engine_manager() {
        if (class_exists('Flavor_Engine_Manager')) {
            return Flavor_Engine_Manager::get_instance();
        }
        return null;
    }

    /**
     * Envía un mensaje a la IA activa y obtiene la respuesta
     *
     * @param Flavor_Platform_Session $session Sesión actual
     * @param string $user_message Mensaje del usuario
     * @return array
     */
    public function send_message($session, $user_message) {
        // Resetear contador de recursión
        $this->recursion_depth = 0;

        $settings = flavor_get_main_settings();

        // Usar Engine Manager si está disponible
        $engine_manager = $this->get_engine_manager();

        if ($engine_manager) {
            $active_engine = $engine_manager->get_active_engine();

            if (!$active_engine) {
                return [
                    'success' => false,
                    'error' => __('No hay motor de IA configurado', 'flavor-platform'),
                    'error_code' => 'no_engine',
                ];
            }

            if (!$active_engine->is_configured()) {
                return [
                    'success' => false,
                    'error' => sprintf('El proveedor %s no está configurado. Añade la API key.', $active_engine->get_name()),
                    'error_code' => 'not_configured',
                ];
            }
        } else {
            // Fallback: verificar API key legacy
            $api_key = $settings['claude_api_key'] ?? $settings['api_key'] ?? '';
            if (empty($api_key)) {
                return [
                    'success' => false,
                    'error' => __('API key no configurada', 'flavor-platform'),
                    'error_code' => 'no_api_key',
                ];
            }
        }

        // Construir el contexto y mensajes
        $system_prompt = $this->build_system_prompt($session);
        $messages = $this->build_messages($session, $user_message);
        $tools = $this->get_tools_from_modules();

        // Usar Engine Manager para la llamada a la API (contexto frontend = chat público)
        if ($engine_manager) {
            $response = $engine_manager->send_frontend_message($messages, $system_prompt, $tools);
        } else {
            // Fallback: llamada directa a Claude API (legacy)
            $api_key = $settings['claude_api_key'] ?? $settings['api_key'] ?? '';
            $request_body = [
                'model' => self::MODEL,
                'max_tokens' => intval($settings['max_tokens_per_message'] ?? 1000),
                'system' => $system_prompt,
                'messages' => $messages,
            ];

            if (!empty($tools)) {
                $request_body['tools'] = $tools;
            }

            $response = $this->make_api_request($api_key, $request_body);
        }

        if (!$response['success']) {
            return $response;
        }

        // Procesar la respuesta (unificar formato)
        return $this->process_unified_response($session, $response, $messages);
    }

    /**
     * Procesa respuesta unificada de cualquier motor de IA
     *
     * @param Flavor_Platform_Session $session
     * @param array $response
     * @param array $messages
     * @return array
     */
    private function process_unified_response($session, $response, $messages) {
        // Si viene del Engine Manager, el formato ya está normalizado
        if (isset($response['response']) && isset($response['tool_calls'])) {
            $text_response = $response['response'];
            $tool_calls = $response['tool_calls'];
            $stop_reason = $response['stop_reason'] ?? 'end_turn';
            $cart_updated = false;

            // Si hay tool_calls, ejecutar las herramientas
            if (!empty($tool_calls) && ($stop_reason === 'tool_use' || $stop_reason === 'tool_calls')) {
                $tool_results = [];

                foreach ($tool_calls as $tool_call) {
                    $result = $this->execute_tool($tool_call['name'], $tool_call['arguments']);
                    $tool_results[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $tool_call['id'],
                        'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    ];

                    if (!empty($result['cart_updated'])) {
                        $cart_updated = true;
                    }
                }

                // Continuar la conversación con los resultados
                return $this->continue_with_tool_results_unified($session, $response, $messages, $tool_results, $cart_updated);
            }

            // Guardar el mensaje del asistente en la sesión
            $session->add_message('assistant', $text_response);

            return [
                'success' => true,
                'response' => $text_response,
                'cart_updated' => $cart_updated,
                'tokens_used' => $response['usage']['output_tokens'] ?? $response['usage']['completion_tokens'] ?? 0,
            ];
        }

        // Si viene de la llamada legacy (formato Claude original)
        if (isset($response['data'])) {
            return $this->process_response($session, $response['data'], $messages);
        }

        return [
            'success' => false,
            'error' => __('Formato de respuesta desconocido', 'flavor-platform'),
            'error_code' => 'unknown_format',
        ];
    }

    /**
     * Obtiene las herramientas de todos los módulos cargados
     *
     * @return array
     */
    private function get_tools_from_modules() {
        $tools = [];

        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            $tools = $loader->get_all_tool_definitions();
        }

        return $tools;
    }

    /**
     * Construye el system prompt
     *
     * @param Flavor_Platform_Session $session
     * @return string
     */
    private function build_system_prompt($session) {
        $settings = flavor_get_main_settings();
        $language = $session->get_language();

        $assistant_name = $settings['assistant_name'] ?? __('Asistente Virtual', 'flavor-platform');
        $assistant_role = $settings['assistant_role'] ?? '';
        $tone = $settings['tone'] ?? 'friendly';

        // Instrucciones de tono
        $tone_instructions = $this->get_tone_instructions($tone, $language);

        // Conocimiento base de los módulos
        $modules_knowledge = '';
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            $modules_knowledge = $loader->get_combined_knowledge_base();
        }

        // Conocimiento personalizado
        $custom_knowledge = '';
        $kb_class = function_exists('flavor_get_runtime_class_name')
            ? flavor_get_runtime_class_name('Flavor_Chat_Knowledge_Base')
            : 'Flavor_Chat_Knowledge_Base';
        if (class_exists($kb_class)) {
            $kb = $kb_class::get_instance();
            $custom_knowledge = $kb->get_full_context($language);
        }

        // Información del sitio
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');

        // Fecha actual
        $fecha_hoy = date_i18n('l j \d\e F \d\e Y', current_time('timestamp'));
        $fecha_hoy_iso = current_time('Y-m-d');

        // Construir el prompt
        $prompt = $this->get_base_system_prompt($language);

        // Reemplazar variables
        $prompt = str_replace(
            [
                '{assistant_name}',
                '{assistant_role}',
                '{tone_instructions}',
                '{site_name}',
                '{site_description}',
                '{modules_knowledge}',
                '{custom_knowledge}',
                '{fecha_hoy}',
                '{fecha_hoy_iso}',
            ],
            [
                $assistant_name,
                $assistant_role,
                $tone_instructions,
                $site_name,
                $site_description,
                $modules_knowledge,
                $custom_knowledge,
                $fecha_hoy,
                $fecha_hoy_iso,
            ],
            $prompt
        );

        // Añadir protecciones antispam (anti-jailbreak y on-topic)
        $antispam_class = function_exists('flavor_get_runtime_class_name')
            ? flavor_get_runtime_class_name('Flavor_Chat_Antispam')
            : 'Flavor_Chat_Antispam';
        if (class_exists($antispam_class)) {
            $antispam = $antispam_class::get_instance();
            $site_name = get_bloginfo('name');
            $business_topics = $settings['business_topics'] ?? [];

            $prompt .= "\n\n" . $antispam->get_jailbreak_protection_prompt();
            $prompt .= "\n" . $antispam->get_on_topic_prompt($site_name, $business_topics);
        }

        return $prompt;
    }

    /**
     * Obtiene el prompt base según idioma
     *
     * @param string $language
     * @return string
     */
    private function get_base_system_prompt($language) {
        $prompts = [
            'es' => <<<PROMPT
Eres {assistant_name}, asistente virtual de {site_name}.

{assistant_role}

FECHA ACTUAL: {fecha_hoy} ({fecha_hoy_iso})

{tone_instructions}

INFORMACIÓN DEL SITIO:
{site_description}

{modules_knowledge}

{custom_knowledge}

INSTRUCCIONES GENERALES:
- Responde SIEMPRE en español
- Sé conciso pero completo
- Si no puedes ayudar con algo, ofrece alternativas o escalado a humanos
- Usa las herramientas disponibles para obtener información actualizada
- No inventes información que no tengas
- Si necesitas más datos del usuario, pregunta
- Mantén un tono {tone_instructions}

HERRAMIENTAS:
Tienes acceso a herramientas para consultar y gestionar información. Úsalas cuando sea necesario para dar respuestas precisas y actualizadas.
PROMPT,

            'en' => <<<PROMPT
You are {assistant_name}, virtual assistant for {site_name}.

{assistant_role}

CURRENT DATE: {fecha_hoy} ({fecha_hoy_iso})

{tone_instructions}

SITE INFORMATION:
{site_description}

{modules_knowledge}

{custom_knowledge}

GENERAL INSTRUCTIONS:
- Always respond in English
- Be concise but complete
- If you can't help with something, offer alternatives or escalation to humans
- Use available tools to get updated information
- Don't make up information you don't have
- If you need more user data, ask
- Keep a {tone_instructions} tone

TOOLS:
You have access to tools to query and manage information. Use them when necessary for accurate and up-to-date responses.
PROMPT,

            'eu' => <<<PROMPT
{assistant_name} naiz, {site_name}-(r)en laguntzaile birtuala.

{assistant_role}

GAURKO DATA: {fecha_hoy} ({fecha_hoy_iso})

{tone_instructions}

GUNEAREN INFORMAZIOA:
{site_description}

{modules_knowledge}

{custom_knowledge}

JARRAIBIDE OROKORRAK:
- Beti euskaraz erantzun
- Laburra baina osoa izan
- Ezin badut lagundu, alternatibak edo giza arreta eskaini
- Erabili tresnak informazio eguneratua lortzeko
- Ez asmatu ez duzun informaziorik
- Datu gehiago behar badituzu, galdetu
- Mantendu tonu {tone_instructions}

TRESNAK:
Informazioa kontsultatzeko eta kudeatzeko tresnak dituzu. Erabili beharrezkoa denean erantzun zehatzak eta eguneratuak emateko.
PROMPT,

            'fr' => <<<PROMPT
Vous êtes {assistant_name}, assistant virtuel de {site_name}.

{assistant_role}

DATE ACTUELLE: {fecha_hoy} ({fecha_hoy_iso})

{tone_instructions}

INFORMATIONS DU SITE:
{site_description}

{modules_knowledge}

{custom_knowledge}

INSTRUCTIONS GÉNÉRALES:
- Répondez TOUJOURS en français
- Soyez concis mais complet
- Si vous ne pouvez pas aider, proposez des alternatives ou une escalade vers un humain
- Utilisez les outils disponibles pour obtenir des informations actualisées
- N'inventez pas d'informations que vous n'avez pas
- Si vous avez besoin de plus de données, demandez
- Maintenez un ton {tone_instructions}

OUTILS:
Vous avez accès à des outils pour consulter et gérer les informations. Utilisez-les si nécessaire pour des réponses précises et à jour.
PROMPT,

            'ca' => <<<PROMPT
Ets {assistant_name}, assistent virtual de {site_name}.

{assistant_role}

DATA ACTUAL: {fecha_hoy} ({fecha_hoy_iso})

{tone_instructions}

INFORMACIÓ DEL LLOC:
{site_description}

{modules_knowledge}

{custom_knowledge}

INSTRUCCIONS GENERALS:
- Respon SEMPRE en català
- Sigues concís però complet
- Si no pots ajudar amb alguna cosa, ofereix alternatives o escalat a humans
- Utilitza les eines disponibles per obtenir informació actualitzada
- No inventis informació que no tinguis
- Si necessites més dades de l'usuari, pregunta
- Mantingues un to {tone_instructions}

EINES:
Tens accés a eines per consultar i gestionar informació. Utilitza-les quan sigui necessari per donar respostes precises i actualitzades.
PROMPT,
        ];

        return $prompts[$language] ?? $prompts['es'];
    }

    /**
     * Obtiene instrucciones según el tono
     *
     * @param string $tone
     * @param string $language
     * @return string
     */
    private function get_tone_instructions($tone, $language) {
        $tones = [
            'es' => [
                'formal' => 'profesional y formal, usando usted',
                'friendly' => 'amigable y cercano, pero profesional',
                'casual' => 'informal y relajado, como un amigo',
                'enthusiastic' => 'entusiasta y positivo, transmitiendo energía',
            ],
            'en' => [
                'formal' => 'professional and formal',
                'friendly' => 'friendly and approachable, yet professional',
                'casual' => 'informal and relaxed, like a friend',
                'enthusiastic' => 'enthusiastic and positive, conveying energy',
            ],
            'eu' => [
                'formal' => 'profesionala eta formala, zuka erabiliz',
                'friendly' => 'adiskidetsua eta hurbila, baina profesionala',
                'casual' => 'informala eta lasaia, lagun bat bezala',
                'enthusiastic' => 'gogotsu eta positiboa, energia transmitituz',
            ],
            'fr' => [
                'formal' => 'professionnel et formel, utilisant le vouvoiement',
                'friendly' => 'amical et accessible, mais professionnel',
                'casual' => 'décontracté et informel, comme un ami',
                'enthusiastic' => 'enthousiaste et positif, transmettant de l\'énergie',
            ],
            'ca' => [
                'formal' => 'professional i formal, fent servir vostè',
                'friendly' => 'amable i proper, però professional',
                'casual' => 'informal i relaxat, com un amic',
                'enthusiastic' => 'entusiasta i positiu, transmetent energia',
            ],
        ];

        $lang_tones = $tones[$language] ?? $tones['es'];
        return $lang_tones[$tone] ?? $lang_tones['friendly'];
    }

    /**
     * Construye el array de mensajes para la API
     *
     * @param Flavor_Platform_Session $session
     * @param string $user_message
     * @return array
     */
    private function build_messages($session, $user_message) {
        $messages = [];

        // Obtener historial de la sesión
        $history = $session->get_messages();

        foreach ($history as $msg) {
            if ($msg['role'] === 'user' || $msg['role'] === 'assistant') {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        // Añadir el mensaje actual del usuario
        $messages[] = [
            'role' => 'user',
            'content' => $user_message,
        ];

        return $messages;
    }

    /**
     * Realiza la llamada a la API de Claude
     *
     * @param string $api_key
     * @param array $body
     * @return array
     */
    private function make_api_request($api_key, $body) {
        $args = [
            'method' => 'POST',
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => self::API_VERSION,
            ],
            'body' => json_encode($body),
        ];

        $response = wp_remote_post(self::API_URL, $args);

        if (is_wp_error($response)) {
            flavor_platform_log('Error API: ' . $response->get_error_message(), 'error');
            return [
                'success' => false,
                'error' => __('Error de conexión con la API', 'flavor-platform'),
                'error_code' => 'connection_error',
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200) {
            $error_message = $body['error']['message'] ?? 'Error desconocido';

            // Intentar con modelo fallback si es error de modelo
            if ($status_code === 400 && strpos($error_message, 'model') !== false) {
                flavor_platform_log('Intentando con modelo fallback', 'warning');
                $body_array = json_decode($args['body'], true);
                $body_array['model'] = self::MODEL_FALLBACK;
                $args['body'] = json_encode($body_array);

                $response = wp_remote_post(self::API_URL, $args);

                if (!is_wp_error($response)) {
                    $status_code = wp_remote_retrieve_response_code($response);
                    $body = json_decode(wp_remote_retrieve_body($response), true);

                    if ($status_code === 200) {
                        return [
                            'success' => true,
                            'data' => $body,
                        ];
                    }
                }
            }

            flavor_platform_log("Error API [{$status_code}]: {$error_message}", 'error');

            return [
                'success' => false,
                'error' => $error_message,
                'error_code' => 'api_error',
                'status_code' => $status_code,
            ];
        }

        return [
            'success' => true,
            'data' => $body,
        ];
    }

    /**
     * Procesa la respuesta de Claude
     *
     * @param Flavor_Platform_Session $session
     * @param array $data
     * @param array $messages
     * @return array
     */
    private function process_response($session, $data, $messages) {
        $content = $data['content'] ?? [];
        $stop_reason = $data['stop_reason'] ?? 'end_turn';

        $text_response = '';
        $tool_uses = [];
        $cart_updated = false;

        // Extraer texto y tool_use
        foreach ($content as $block) {
            if ($block['type'] === 'text') {
                $text_response .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $tool_uses[] = $block;
            }
        }

        // Si hay tool_use, ejecutar las herramientas
        if (!empty($tool_uses) && $stop_reason === 'tool_use') {
            $tool_results = [];

            foreach ($tool_uses as $tool_use) {
                $result = $this->execute_tool($tool_use['name'], $tool_use['input']);
                $tool_results[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $tool_use['id'],
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];

                // Verificar si se actualizó el carrito
                if (!empty($result['cart_updated'])) {
                    $cart_updated = true;
                }
            }

            // Continuar la conversación con los resultados
            return $this->continue_with_tool_results($session, $data, $messages, $tool_results, $cart_updated);
        }

        // Guardar el mensaje del asistente en la sesión
        $session->add_message('assistant', $text_response);

        return [
            'success' => true,
            'response' => $text_response,
            'cart_updated' => $cart_updated,
            'tokens_used' => $data['usage']['output_tokens'] ?? 0,
        ];
    }

    /**
     * Ejecuta una herramienta
     *
     * @param string $tool_name
     * @param array $params
     * @return array
     */
    private function execute_tool($tool_name, $params) {
        flavor_platform_log("Ejecutando tool: {$tool_name}", 'info');

        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            return $loader->execute_action($tool_name, $params);
        }

        return [
            'success' => false,
            'error' => __('Sistema de módulos no disponible', 'flavor-platform'),
        ];
    }

    /**
     * Continúa la conversación después de ejecutar tools (Engine Manager)
     *
     * @param Flavor_Platform_Session $session
     * @param array $original_response
     * @param array $messages
     * @param array $tool_results
     * @param bool $cart_updated
     * @return array
     */
    private function continue_with_tool_results_unified($session, $original_response, $messages, $tool_results, $cart_updated) {
        $this->recursion_depth++;

        if ($this->recursion_depth > self::MAX_RECURSION_DEPTH) {
            return [
                'success' => false,
                'error' => __('Demasiadas llamadas a herramientas', 'flavor-platform'),
                'error_code' => 'max_recursion',
            ];
        }

        $engine_manager = $this->get_engine_manager();

        if (!$engine_manager) {
            return [
                'success' => false,
                'error' => __('Engine Manager no disponible', 'flavor-platform'),
                'error_code' => 'no_engine_manager',
            ];
        }

        // Construir mensajes incluyendo la respuesta del asistente y los resultados
        $new_messages = $messages;

        // Añadir la respuesta del asistente con tool_use (formato Claude)
        $assistant_content = [];
        if (!empty($original_response['response'])) {
            $assistant_content[] = [
                'type' => 'text',
                'text' => $original_response['response'],
            ];
        }

        // Añadir los tool_use blocks
        foreach ($original_response['tool_calls'] as $tool_call) {
            $assistant_content[] = [
                'type' => 'tool_use',
                'id' => $tool_call['id'],
                'name' => $tool_call['name'],
                'input' => $tool_call['arguments'],
            ];
        }

        $new_messages[] = [
            'role' => 'assistant',
            'content' => $assistant_content,
        ];

        // Añadir los resultados de las herramientas
        $new_messages[] = [
            'role' => 'user',
            'content' => $tool_results,
        ];

        // Preparar nuevo request
        $system_prompt = $this->build_system_prompt($session);
        $tools = $this->get_tools_from_modules();

        $response = $engine_manager->send_frontend_message($new_messages, $system_prompt, $tools);

        if (!$response['success']) {
            return $response;
        }

        // Procesar recursivamente
        $result = $this->process_unified_response($session, $response, $new_messages);

        // Propagar cart_updated
        if ($cart_updated && !($result['cart_updated'] ?? false)) {
            $result['cart_updated'] = true;
        }

        return $result;
    }

    /**
     * Continúa la conversación después de ejecutar tools (Legacy)
     *
     * @param Flavor_Platform_Session $session
     * @param array $original_response
     * @param array $messages
     * @param array $tool_results
     * @param bool $cart_updated
     * @return array
     */
    private function continue_with_tool_results($session, $original_response, $messages, $tool_results, $cart_updated) {
        $this->recursion_depth++;

        if ($this->recursion_depth > self::MAX_RECURSION_DEPTH) {
            return [
                'success' => false,
                'error' => __('Demasiadas llamadas a herramientas', 'flavor-platform'),
                'error_code' => 'max_recursion',
            ];
        }

        $settings = flavor_get_main_settings();
        $api_key = $settings['claude_api_key'] ?? $settings['api_key'] ?? '';

        // Construir mensajes incluyendo la respuesta del asistente y los resultados
        $new_messages = $messages;

        // Añadir la respuesta del asistente con tool_use
        $new_messages[] = [
            'role' => 'assistant',
            'content' => $original_response['content'],
        ];

        // Añadir los resultados de las herramientas
        $new_messages[] = [
            'role' => 'user',
            'content' => $tool_results,
        ];

        // Preparar nuevo request
        $system_prompt = $this->build_system_prompt($session);
        $tools = $this->get_tools_from_modules();

        $request_body = [
            'model' => self::MODEL,
            'max_tokens' => intval($settings['max_tokens_per_message'] ?? 1000),
            'system' => $system_prompt,
            'messages' => $new_messages,
        ];

        if (!empty($tools)) {
            $request_body['tools'] = $tools;
        }

        $response = $this->make_api_request($api_key, $request_body);

        if (!$response['success']) {
            return $response;
        }

        // Procesar recursivamente
        $result = $this->process_response($session, $response['data'], $new_messages);

        // Propagar cart_updated
        if ($cart_updated && !$result['cart_updated']) {
            $result['cart_updated'] = true;
        }

        return $result;
    }
}

if (!class_exists('Flavor_Chat_Claude_Engine', false)) {
    class_alias('Flavor_Platform_Claude_Engine', 'Flavor_Chat_Claude_Engine');
}
