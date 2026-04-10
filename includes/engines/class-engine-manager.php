<?php
/**
 * Gestor de Motores de IA
 *
 * Maneja el registro y selección de proveedores de IA
 * Soporta configuración separada para frontend (chat público) y backend (admin assistant)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Evitar redeclaración si ya existe (ej: desde chat-ia-addon)
if (class_exists('Flavor_Engine_Manager')) {
    return;
}

class Flavor_Engine_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Motores registrados
     */
    private $engines = [];

    /**
     * Cache de motores activos por contexto
     */
    private $active_engines = [];

    /**
     * Contextos disponibles
     */
    const CONTEXT_FRONTEND = 'frontend';
    const CONTEXT_BACKEND = 'backend';
    const CONTEXT_DEFAULT = 'default';

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
     * Constructor privado
     */
    private function __construct() {
        $this->register_default_engines();
    }

    /**
     * Registra los motores por defecto
     */
    private function register_default_engines() {
        // Claude (Anthropic)
        if (class_exists('Chat_IA_Engine_Claude')) {
            $this->register_engine(new Chat_IA_Engine_Claude());
        }

        // OpenAI (GPT)
        if (class_exists('Chat_IA_Engine_OpenAI')) {
            $this->register_engine(new Chat_IA_Engine_OpenAI());
        }

        // DeepSeek
        if (class_exists('Chat_IA_Engine_DeepSeek')) {
            $this->register_engine(new Chat_IA_Engine_DeepSeek());
        }

        // Mistral
        if (class_exists('Chat_IA_Engine_Mistral')) {
            $this->register_engine(new Chat_IA_Engine_Mistral());
        }

        // Permitir registrar motores personalizados
        do_action('flavor_platform_register_engines', $this);
    }

    /**
     * Registra un motor de IA
     *
     * @param Flavor_Engine_Interface $engine
     */
    public function register_engine($engine) {
        if (method_exists($engine, 'get_id')) {
            $this->engines[$engine->get_id()] = $engine;
        }
    }

    /**
     * Obtiene todos los motores registrados
     *
     * @return array
     */
    public function get_engines() {
        return $this->engines;
    }

    /**
     * Obtiene un motor específico por ID
     *
     * @param string $id
     * @return Flavor_Engine_Interface|null
     */
    public function get_engine($id) {
        return $this->engines[$id] ?? null;
    }

    /**
     * Obtiene la configuración de IA para un contexto específico
     *
     * @param string $context 'frontend', 'backend', o 'default'
     * @return array ['provider' => string, 'model' => string]
     */
    public function get_context_config($context = self::CONTEXT_DEFAULT) {
        $settings = flavor_get_main_settings();

        // Configuración por defecto
        $default_provider = $settings['active_provider'] ?? 'claude';
        $default_model = $settings[$default_provider . '_model'] ?? null;

        // Si el contexto es 'default' o no hay configuración específica, usar la general
        if ($context === self::CONTEXT_DEFAULT) {
            return [
                'provider' => $default_provider,
                'model' => $default_model,
            ];
        }

        // Verificar si hay configuración específica para el contexto
        $context_provider_key = 'ia_provider_' . $context;
        $context_model_key = 'ia_model_' . $context;

        $context_provider = $settings[$context_provider_key] ?? null;
        $context_model = $settings[$context_model_key] ?? null;

        // Si no hay configuración específica, usar la por defecto
        if (empty($context_provider) || $context_provider === 'default') {
            return [
                'provider' => $default_provider,
                'model' => $default_model,
            ];
        }

        // Si no hay modelo específico, usar el modelo por defecto del proveedor
        if (empty($context_model)) {
            $context_model = $settings[$context_provider . '_model'] ?? null;
        }

        return [
            'provider' => $context_provider,
            'model' => $context_model,
        ];
    }

    /**
     * Obtiene el motor activo para un contexto específico
     *
     * @param string $context 'frontend', 'backend', o 'default'
     * @return Flavor_Engine_Interface|null
     */
    public function get_active_engine($context = self::CONTEXT_DEFAULT) {
        // Cache por contexto
        if (isset($this->active_engines[$context])) {
            return $this->active_engines[$context];
        }

        $config = $this->get_context_config($context);
        $engine = $this->get_engine($config['provider']);

        // Fallback a Claude si el motor no existe
        if ($engine === null) {
            $engine = $this->get_engine('claude');
        }

        $this->active_engines[$context] = $engine;
        return $engine;
    }

    /**
     * Obtiene el motor activo para el frontend (chat público)
     *
     * @return Flavor_Engine_Interface|null
     */
    public function get_frontend_engine() {
        return $this->get_active_engine(self::CONTEXT_FRONTEND);
    }

    /**
     * Obtiene el motor activo para el backend (admin assistant)
     *
     * @return Flavor_Engine_Interface|null
     */
    public function get_backend_engine() {
        return $this->get_active_engine(self::CONTEXT_BACKEND);
    }

    /**
     * Establece el motor activo para un contexto
     *
     * @param string $provider_id
     * @param string $context 'frontend', 'backend', o 'default'
     * @param string|null $model Modelo específico (opcional)
     * @return bool
     */
    public function set_active_engine($provider_id, $context = self::CONTEXT_DEFAULT, $model = null) {
        if (!isset($this->engines[$provider_id]) && $provider_id !== 'default') {
            return false;
        }

        $settings = flavor_get_main_settings();

        if ($context === self::CONTEXT_DEFAULT) {
            $settings['active_provider'] = $provider_id;
            if ($model) {
                $settings[$provider_id . '_model'] = $model;
            }
        } else {
            $settings['ia_provider_' . $context] = $provider_id;
            if ($model) {
                $settings['ia_model_' . $context] = $model;
            }
        }

        flavor_update_main_settings($settings);

        // Limpiar cache
        unset($this->active_engines[$context]);

        return true;
    }

    /**
     * Obtiene información de todos los proveedores para mostrar en admin
     *
     * @return array
     */
    public function get_providers_info() {
        $info = [];

        foreach ($this->engines as $id => $engine) {
            $info[$id] = [
                'id' => $id,
                'name' => $engine->get_name(),
                'description' => $engine->get_description(),
                'configured' => $engine->is_configured(),
                'supports_tools' => $engine->supports_tools(),
                'models' => $engine->get_available_models(),
                'settings_fields' => $engine->get_settings_fields(),
            ];
        }

        return $info;
    }

    /**
     * Obtiene la configuración actual para mostrar en admin
     *
     * @return array
     */
    public function get_current_configuration() {
        return [
            'default' => $this->get_context_config(self::CONTEXT_DEFAULT),
            'frontend' => $this->get_context_config(self::CONTEXT_FRONTEND),
            'backend' => $this->get_context_config(self::CONTEXT_BACKEND),
        ];
    }

    /**
     * Obtiene todos los engines configurados (con API key válida)
     * Ordenados según la configuración de fallback del usuario
     *
     * @return array Lista de engines configurados ordenados por prioridad
     */
    public function get_configured_engines() {
        $configured = [];
        $settings = flavor_get_main_settings();

        // Obtener engines que están configurados
        foreach ($this->engines as $id => $engine) {
            if ($engine->is_configured()) {
                $configured[$id] = $engine;
            }
        }

        // Si no hay engines configurados, retornar vacío
        if (empty($configured)) {
            return [];
        }

        // Obtener orden de fallback configurado por el usuario
        $fallback_order = $settings['fallback_order'] ?? [];

        // Si hay orden configurado, usarlo
        if (!empty($fallback_order)) {
            $ordered = [];

            // Primero añadir los que están en el orden configurado
            foreach ($fallback_order as $provider_id) {
                if (isset($configured[$provider_id])) {
                    $ordered[$provider_id] = $configured[$provider_id];
                }
            }

            // Añadir cualquier engine configurado que no esté en el orden (por si se configuró después)
            foreach ($configured as $id => $engine) {
                if (!isset($ordered[$id])) {
                    $ordered[$id] = $engine;
                }
            }

            return $ordered;
        }

        // Fallback: ordenar con el activo primero
        $active_provider = $settings['active_provider'] ?? 'claude';
        if (isset($configured[$active_provider])) {
            $active = [$active_provider => $configured[$active_provider]];
            unset($configured[$active_provider]);
            $configured = $active + $configured;
        }

        return $configured;
    }

    /**
     * Obtiene el orden de fallback configurado
     *
     * @return array Lista de IDs de providers en orden de prioridad
     */
    public function get_fallback_order() {
        $configured = $this->get_configured_engines();
        return array_keys($configured);
    }

    /**
     * Envía un mensaje usando el motor del contexto especificado
     * Con sistema de fallback automático si el motor principal falla
     *
     * @param array $messages
     * @param string $system_prompt
     * @param array $tools
     * @param string $context 'frontend', 'backend', o 'default'
     * @return array
     */
    public function send_message($messages, $system_prompt, $tools = [], $context = self::CONTEXT_DEFAULT) {
        $engine = $this->get_active_engine($context);
        $config = $this->get_context_config($context);

        // Obtener todos los engines configurados para fallback
        $configured_engines = $this->get_configured_engines();

        if (empty($configured_engines)) {
            return [
                'success' => false,
                'error' => __('No hay ningún motor de IA configurado. Configura al menos una API key.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error_code' => 'no_engine_configured',
            ];
        }

        // Si el engine principal no está disponible, usar el primero configurado
        if (!$engine || !$engine->is_configured()) {
            $engine = reset($configured_engines);
            $config['provider'] = $engine->get_id();
        }

        // Construir lista de engines a intentar (primero el activo, luego fallbacks)
        $engines_to_try = [];
        $engines_to_try[$engine->get_id()] = $engine;

        // Añadir el resto como fallback
        foreach ($configured_engines as $id => $fallback_engine) {
            if (!isset($engines_to_try[$id])) {
                $engines_to_try[$id] = $fallback_engine;
            }
        }

        $last_error = null;
        $attempts = [];

        // Intentar con cada engine
        foreach ($engines_to_try as $engine_id => $current_engine) {
            // Preparar tools (quitar si el motor no las soporta)
            $current_tools = $current_engine->supports_tools() ? $tools : [];

            // Pasar el modelo específico si está configurado
            $options = [];
            if ($engine_id === $config['provider'] && !empty($config['model'])) {
                $options['model'] = $config['model'];
            }

            try {
                $response = $current_engine->send_message($messages, $system_prompt, $current_tools, $options);

                if ($response['success']) {
                    // Añadir info del engine usado
                    $response['engine_used'] = $engine_id;
                    $response['engine_name'] = $current_engine->get_name();

                    // Si hubo fallback, indicarlo
                    if ($engine_id !== $config['provider']) {
                        $response['fallback_used'] = true;
                        $response['original_engine'] = $config['provider'];
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log(sprintf('[Flavor IA Fallback] %s falló, se usó %s', $config['provider'], $engine_id));
                        }
                    }

                    return $response;
                }

                // Guardar el error para posible uso posterior
                $last_error = $response['error'] ?? __('Error desconocido', FLAVOR_PLATFORM_TEXT_DOMAIN);
                $attempts[] = [
                    'engine' => $engine_id,
                    'error' => $last_error,
                ];

                // Log del fallo (solo en modo debug)
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf('[Flavor IA] Engine %s falló: %s', $current_engine->get_name(), $last_error));
                }

            } catch (Exception $e) {
                $last_error = $e->getMessage();
                $attempts[] = [
                    'engine' => $engine_id,
                    'error' => $last_error,
                ];

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf('[Flavor IA] Engine %s excepción: %s', $current_engine->get_name(), $last_error));
                }
            }
        }

        // Todos los engines fallaron
        return [
            'success' => false,
            'error' => sprintf(
                __('Todos los motores de IA fallaron. Último error: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $last_error
            ),
            'error_code' => 'all_engines_failed',
            'attempts' => $attempts,
        ];
    }

    /**
     * Envía un mensaje para el chat público (frontend)
     *
     * @param array $messages
     * @param string $system_prompt
     * @param array $tools
     * @return array
     */
    public function send_frontend_message($messages, $system_prompt, $tools = []) {
        return $this->send_message($messages, $system_prompt, $tools, self::CONTEXT_FRONTEND);
    }

    /**
     * Envía un mensaje para el admin assistant (backend)
     *
     * @param array $messages
     * @param string $system_prompt
     * @param array $tools
     * @return array
     */
    public function send_backend_message($messages, $system_prompt, $tools = []) {
        return $this->send_message($messages, $system_prompt, $tools, self::CONTEXT_BACKEND);
    }

    /**
     * Verifica la API key de un proveedor específico
     *
     * @param string $provider_id
     * @param string $api_key
     * @return array
     */
    public function verify_api_key($provider_id, $api_key) {
        $engine = $this->get_engine($provider_id);

        if (!$engine) {
            return [
                'valid' => false,
                'error' => __('Proveedor no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return $engine->verify_api_key($api_key);
    }

    /**
     * Obtiene los proveedores con tier gratuito
     *
     * @return array
     */
    public function get_free_tier_providers() {
        return [
            'deepseek' => [
                'name' => 'DeepSeek',
                'free_limit' => '~500K tokens/día',
                'recommended_model' => 'deepseek-chat',
            ],
            'mistral' => [
                'name' => 'Mistral',
                'free_limit' => '1M tokens/mes',
                'recommended_model' => 'mistral-small-latest',
            ],
        ];
    }

    /**
     * Obtiene los contextos disponibles con sus descripciones
     *
     * @return array
     */
    public function get_available_contexts() {
        return [
            self::CONTEXT_DEFAULT => [
                'name' => __('Por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Configuración general usada cuando no hay una específica', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            self::CONTEXT_FRONTEND => [
                'name' => __('Chat Público (Frontend)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Widget de chat para visitantes del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            self::CONTEXT_BACKEND => [
                'name' => __('Admin Assistant (Backend)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Asistente de IA para administradores', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];
    }
}

// Alias para compatibilidad con código antiguo
class_alias('Flavor_Engine_Manager', 'Chat_IA_Engine_Manager');
