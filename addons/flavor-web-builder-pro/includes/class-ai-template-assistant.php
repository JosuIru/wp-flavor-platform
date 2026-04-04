<?php
/**
 * Asistente IA para composición de plantillas
 *
 * Utiliza el motor de IA existente para ayudar a los usuarios
 * a crear plantillas personalizadas según sus necesidades.
 *
 * @package Flavor_Chat_IA
 * @subpackage Web_Builder
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_AI_Template_Assistant {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Motor de IA
     */
    private $engine_manager;

    /**
     * Registro de componentes
     */
    private $component_registry;

    /**
     * Historial de conversación para contexto
     */
    private $conversation_history = [];

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
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_flavor_ai_template_suggest', [$this, 'ajax_suggest_template']);
        add_action('wp_ajax_flavor_ai_template_refine', [$this, 'ajax_refine_template']);
        add_action('wp_ajax_flavor_ai_template_chat', [$this, 'ajax_chat_message']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Cargar scripts del admin
     */
    public function enqueue_admin_scripts($hook) {
        // Cargar en el editor de posts, páginas y flavor_landing, y en páginas admin de flavor
        $tipos_post_soportados = ['post', 'page', 'flavor_landing'];
        $tipo_post_actual = get_post_type();

        $should_load = (
            strpos($hook, 'flavor-landings') !== false ||
            ($hook === 'post.php' && in_array($tipo_post_actual, $tipos_post_soportados)) ||
            ($hook === 'post-new.php' && in_array($tipo_post_actual, $tipos_post_soportados)) ||
            (isset($_GET['page']) && strpos($_GET['page'], 'flavor') !== false)
        );

        if (!$should_load) {
            return;
        }

        // CSS del asistente (assets en directorio principal del plugin)
        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';
        $url_base_plugin = FLAVOR_CHAT_IA_URL;

        wp_enqueue_style(
            'flavor-ai-template-assistant',
            $url_base_plugin . "assets/css/ai-template-assistant{$sufijo_asset}.css",
            [],
            '1.0.0'
        );

        // JavaScript del asistente
        wp_enqueue_script(
            'flavor-ai-template-assistant',
            $url_base_plugin . "assets/js/ai-template-assistant{$sufijo_asset}.js",
            ['jquery', 'wp-util'],
            '1.0.0',
            true
        );

        wp_localize_script('flavor-ai-template-assistant', 'flavorAITemplateAssistant', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_ai_template_assistant'),
            'strings' => [
                'thinking' => __('Pensando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'apply' => __('Aplicar plantilla', 'flavor-chat-ia'),
                'refine' => __('Refinar', 'flavor-chat-ia'),
                'placeholder' => __('Describe tu negocio o comunidad...', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Obtener el motor de IA
     */
    private function get_engine() {
        if (!$this->engine_manager) {
            if (class_exists('Flavor_Engine_Manager')) {
                $this->engine_manager = Flavor_Engine_Manager::get_instance();
            }
        }
        return $this->engine_manager;
    }

    /**
     * Obtener el registro de componentes
     */
    private function get_component_registry() {
        if (!$this->component_registry) {
            if (class_exists('Flavor_Component_Registry')) {
                $this->component_registry = Flavor_Component_Registry::get_instance();
            }
        }
        return $this->component_registry;
    }

    /**
     * Obtener lista de componentes disponibles para el prompt
     */
    private function get_available_components_for_prompt() {
        $registry = $this->get_component_registry();
        if (!$registry) {
            return '';
        }

        $components = $registry->get_components();
        $components_list = [];

        foreach ($components as $component_id => $component) {
            $components_list[] = sprintf(
                "- %s (%s): %s",
                $component_id,
                $component['label'],
                $component['description'] ?? ''
            );
        }

        return implode("\n", $components_list);
    }

    /**
     * Obtener plantillas existentes para referencia
     *
     * @since 3.4.0 Usa VBP como fuente de templates
     */
    private function get_existing_templates_for_prompt() {
        $templates = [];

        // Usar VBP REST API para obtener templates
        if (class_exists('Flavor_VBP_REST_API')) {
            $vbp_api = Flavor_VBP_REST_API::get_instance();
            if (method_exists($vbp_api, 'get_library_templates')) {
                $vbp_templates = $vbp_api->get_library_templates();
                foreach ($vbp_templates as $template) {
                    $category = $template['category'] ?? 'general';
                    if (!isset($templates[$category])) {
                        $templates[$category] = [
                            'label' => ucfirst($category),
                            'templates' => [],
                        ];
                    }
                    $template_id = $template['id'] ?? sanitize_title($template['title'] ?? 'template');
                    $templates[$category]['templates'][$template_id] = $template;
                }
            }
        }

        $template_examples = [];
        foreach ($templates as $sector => $sector_data) {
            $template_examples[] = "### Sector: {$sector_data['label']}";
            foreach ($sector_data['templates'] as $template_id => $template) {
                $elements = $template['elements'] ?? $template['blocks'] ?? [];
                $components_count = count($elements);
                $component_types = array_column($elements, 'type');
                $template_examples[] = sprintf(
                    "- %s: %s (%d componentes: %s)",
                    $template_id,
                    $template['name'] ?? $template['title'] ?? $template_id,
                    $components_count,
                    implode(', ', array_slice($component_types, 0, 4)) . (count($component_types) > 4 ? '...' : '')
                );
            }
        }

        return implode("\n", $template_examples);
    }

    /**
     * Construir el prompt del sistema para el asistente
     */
    private function build_system_prompt() {
        $components_list = $this->get_available_components_for_prompt();
        $templates_reference = $this->get_existing_templates_for_prompt();

        return <<<PROMPT
Eres un asistente experto en diseño web y composición de plantillas para sitios web de comunidades, empresas y organizaciones.

Tu objetivo es ayudar a los usuarios a crear plantillas de página personalizadas seleccionando y organizando los componentes más adecuados según sus necesidades.

## COMPONENTES DISPONIBLES

Estos son los componentes que puedes utilizar para componer plantillas:

{$components_list}

## PLANTILLAS DE REFERENCIA

Estas son algunas plantillas existentes que puedes usar como referencia:

{$templates_reference}

## INSTRUCCIONES

1. Cuando el usuario describa su negocio, comunidad u organización, analiza sus necesidades.

2. Sugiere una plantilla personalizada con los componentes más apropiados.

3. Tu respuesta DEBE incluir un bloque JSON con la estructura de la plantilla, usando este formato exacto:

```json
{
  "template_name": "Nombre descriptivo de la plantilla",
  "template_description": "Descripción breve",
  "layout": [
    {
      "component_id": "id_del_componente",
      "data": {
        "titulo": "Título del componente",
        "subtitulo": "Subtítulo opcional"
      },
      "settings": {}
    }
  ]
}
```

4. Siempre incluye:
   - Un hero como primer componente
   - Secciones de contenido relevantes
   - Al menos un CTA (llamada a la acción)
   - Entre 4 y 8 componentes en total

5. Adapta los textos (títulos, subtítulos, descripciones) al tipo de negocio o comunidad.

6. Explica brevemente por qué has elegido cada componente.

## CONSIDERACIONES

- Para negocios locales: enfócate en mostrar productos/servicios, ubicación, contacto
- Para comunidades: prioriza la participación, eventos, miembros
- Para asociaciones: destaca misión, actividades, cómo unirse
- Para ayuntamientos: trámites, noticias, servicios ciudadanos
- Para cooperativas: productos, productores, cómo funciona

Responde siempre en español y sé conciso pero informativo.
PROMPT;
    }

    /**
     * AJAX: Sugerir plantilla basada en descripción
     */
    public function ajax_suggest_template() {
        check_ajax_referer('flavor_ai_template_assistant', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
            return;
        }

        $user_description = sanitize_textarea_field($_POST['description'] ?? '');

        if (empty($user_description)) {
            wp_send_json_error(['message' => __('Por favor, describe tu negocio o comunidad', 'flavor-chat-ia')]);
            return;
        }

        $engine = $this->get_engine();
        if (!$engine) {
            wp_send_json_error(['message' => __('Motor de IA no disponible', 'flavor-chat-ia')]);
            return;
        }

        $system_prompt = $this->build_system_prompt();

        $user_message = "Necesito una plantilla para: {$user_description}\n\nPor favor, sugiere los componentes más adecuados y genera la estructura JSON de la plantilla.";

        // Guardar en historial
        $this->conversation_history = [
            ['role' => 'user', 'content' => $user_message]
        ];

        try {
            $response = $engine->send_backend_message(
                $this->conversation_history,
                $system_prompt
            );

            if (!$response['success']) {
                wp_send_json_error(['message' => $response['error'] ?? __('Error en la respuesta de IA', 'flavor-chat-ia')]);
                return;
            }

            $assistant_response = $response['response'];

            // Guardar respuesta en historial
            $this->conversation_history[] = ['role' => 'assistant', 'content' => $assistant_response];

            // Extraer JSON de la respuesta
            $template_data = $this->extract_template_json($assistant_response);

            wp_send_json_success([
                'message' => $assistant_response,
                'template' => $template_data,
                'conversation_id' => wp_generate_uuid4(),
            ]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Refinar plantilla existente
     */
    public function ajax_refine_template() {
        check_ajax_referer('flavor_ai_template_assistant', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
            return;
        }

        $refinement_request = sanitize_textarea_field($_POST['refinement'] ?? '');
        $current_template = json_decode(stripslashes($_POST['current_template'] ?? ''), true);
        $conversation_history = json_decode(stripslashes($_POST['conversation_history'] ?? '[]'), true);

        if (empty($refinement_request)) {
            wp_send_json_error(['message' => __('Por favor, indica qué cambios deseas', 'flavor-chat-ia')]);
            return;
        }

        $engine = $this->get_engine();
        if (!$engine) {
            wp_send_json_error(['message' => __('Motor de IA no disponible', 'flavor-chat-ia')]);
            return;
        }

        $system_prompt = $this->build_system_prompt();

        $context_message = "";
        if (!empty($current_template)) {
            $context_message = "Plantilla actual:\n```json\n" . json_encode($current_template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n```\n\n";
        }

        $user_message = $context_message . "Solicitud de cambio: {$refinement_request}\n\nPor favor, genera la plantilla actualizada con los cambios solicitados.";

        // Añadir al historial
        $conversation_history[] = ['role' => 'user', 'content' => $user_message];

        try {
            $response = $engine->send_backend_message(
                $conversation_history,
                $system_prompt
            );

            if (!$response['success']) {
                wp_send_json_error(['message' => $response['error'] ?? __('Error en la respuesta de IA', 'flavor-chat-ia')]);
                return;
            }

            $assistant_response = $response['response'];

            // Actualizar historial
            $conversation_history[] = ['role' => 'assistant', 'content' => $assistant_response];

            // Extraer JSON
            $template_data = $this->extract_template_json($assistant_response);

            wp_send_json_success([
                'message' => $assistant_response,
                'template' => $template_data,
                'conversation_history' => $conversation_history,
            ]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Mensaje de chat general
     */
    public function ajax_chat_message() {
        check_ajax_referer('flavor_ai_template_assistant', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
            return;
        }

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $conversation_history = json_decode(stripslashes($_POST['conversation_history'] ?? '[]'), true);

        if (empty($message)) {
            wp_send_json_error(['message' => __('Mensaje vacío', 'flavor-chat-ia')]);
            return;
        }

        $engine = $this->get_engine();
        if (!$engine) {
            wp_send_json_error(['message' => __('Motor de IA no disponible', 'flavor-chat-ia')]);
            return;
        }

        $system_prompt = $this->build_system_prompt();

        $conversation_history[] = ['role' => 'user', 'content' => $message];

        try {
            $response = $engine->send_backend_message(
                $conversation_history,
                $system_prompt
            );

            if (!$response['success']) {
                wp_send_json_error(['message' => $response['error'] ?? __('Error en la respuesta de IA', 'flavor-chat-ia')]);
                return;
            }

            $assistant_response = $response['response'];
            $conversation_history[] = ['role' => 'assistant', 'content' => $assistant_response];

            $template_data = $this->extract_template_json($assistant_response);

            wp_send_json_success([
                'message' => $assistant_response,
                'template' => $template_data,
                'conversation_history' => $conversation_history,
            ]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Extraer JSON de plantilla de la respuesta
     */
    private function extract_template_json($response) {
        // Buscar bloque JSON en la respuesta
        $pattern = '/```json\s*([\s\S]*?)\s*```/';

        if (preg_match($pattern, $response, $matches)) {
            $json_string = trim($matches[1]);
            $template_data = json_decode($json_string, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($template_data['layout'])) {
                return $template_data;
            }
        }

        // Intentar buscar JSON sin bloques de código
        $pattern_alt = '/\{[\s\S]*"layout"[\s\S]*\}/';
        if (preg_match($pattern_alt, $response, $matches)) {
            $template_data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($template_data['layout'])) {
                return $template_data;
            }
        }

        return null;
    }

    /**
     * Validar estructura de plantilla
     */
    public function validate_template_structure($template) {
        if (!is_array($template)) {
            return false;
        }

        if (!isset($template['layout']) || !is_array($template['layout'])) {
            return false;
        }

        $registry = $this->get_component_registry();
        $available_components = $registry ? array_keys($registry->get_components()) : [];

        foreach ($template['layout'] as $component) {
            if (!isset($component['component_id'])) {
                return false;
            }

            // Verificar que el componente existe (opcional, puede ser flexible)
            // if (!in_array($component['component_id'], $available_components)) {
            //     return false;
            // }
        }

        return true;
    }

    /**
     * Renderizar panel del asistente en el admin
     */
    public function render_assistant_panel() {
        ?>
        <div id="flavor-ai-template-assistant" class="flavor-ai-assistant-panel">
            <div class="flavor-ai-assistant-header">
                <h3>
                    <span class="dashicons dashicons-superhero-alt"></span>
                    <?php _e('Asistente IA de Plantillas', 'flavor-chat-ia'); ?>
                </h3>
                <button type="button" class="flavor-ai-assistant-toggle" aria-expanded="true">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
            </div>

            <div class="flavor-ai-assistant-body">
                <div class="flavor-ai-assistant-chat">
                    <div class="flavor-ai-chat-messages" id="flavor-ai-chat-messages">
                        <div class="flavor-ai-message flavor-ai-message-assistant">
                            <div class="flavor-ai-message-content">
                                <?php _e('¡Hola! Soy tu asistente para crear plantillas. Describe tu negocio, comunidad u organización y te ayudaré a componer la plantilla perfecta.', 'flavor-chat-ia'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flavor-ai-assistant-input">
                    <textarea
                        id="flavor-ai-input"
                        placeholder="<?php esc_attr_e('Ej: Somos una cooperativa de productos ecológicos que quiere mostrar nuestros productores, productos y cómo unirse...', 'flavor-chat-ia'); ?>"
                        rows="3"
                    ></textarea>
                    <div class="flavor-ai-assistant-actions">
                        <button type="button" id="flavor-ai-send" class="button button-primary">
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                            <?php _e('Generar Plantilla', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>

                <div class="flavor-ai-assistant-preview" id="flavor-ai-preview" style="display: none;">
                    <h4><?php _e('Plantilla Generada', 'flavor-chat-ia'); ?></h4>
                    <div class="flavor-ai-preview-components" id="flavor-ai-preview-components"></div>
                    <div class="flavor-ai-preview-actions">
                        <button type="button" id="flavor-ai-apply" class="button button-primary">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Aplicar Plantilla', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" id="flavor-ai-refine" class="button">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Refinar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Inicializar
Flavor_AI_Template_Assistant::get_instance();
