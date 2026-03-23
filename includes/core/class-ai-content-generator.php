<?php
/**
 * Generador de Contenido con IA
 *
 * Genera contenido automáticamente usando el motor IA activo:
 * - Descripciones de eventos
 * - Posts y páginas
 * - Textos de bienvenida
 * - Emails de notificación
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_AI_Content_Generator {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Motor de IA activo
     */
    private $engine = null;

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
        add_action('wp_ajax_flavor_ai_generate_content', [$this, 'ajax_generate_content']);
    }

    /**
     * Obtiene el motor de IA activo
     */
    private function get_engine() {
        if ($this->engine === null && class_exists('Flavor_Engine_Manager')) {
            $manager = Flavor_Engine_Manager::get_instance();
            $this->engine = $manager->get_active_engine();
        }
        return $this->engine;
    }

    /**
     * Verifica si el generador está disponible
     */
    public function is_available() {
        $engine = $this->get_engine();
        return $engine && $engine->is_configured();
    }

    /**
     * Handler AJAX para generar contenido
     */
    public function ajax_generate_content() {
        check_ajax_referer('flavor_ai_content', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $type = sanitize_text_field($_POST['type'] ?? '');
        $context = sanitize_textarea_field($_POST['context'] ?? '');
        $options = isset($_POST['options']) ? (array) $_POST['options'] : [];

        $result = $this->generate($type, $context, $options);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Genera contenido según el tipo
     *
     * @param string $type Tipo de contenido
     * @param string $context Contexto o descripción
     * @param array $options Opciones adicionales
     * @return array
     */
    public function generate($type, $context, $options = []) {
        if (!$this->is_available()) {
            return [
                'success' => false,
                'error' => __('El motor de IA no está disponible', 'flavor-chat-ia'),
            ];
        }

        $prompt = $this->build_prompt($type, $context, $options);
        $system_prompt = $this->get_system_prompt($type);

        $response = $this->get_engine()->send_message(
            [['role' => 'user', 'content' => $prompt]],
            $system_prompt,
            []
        );

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $response['error'] ?? __('Error al generar contenido', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'content' => $response['response'],
            'type' => $type,
        ];
    }

    /**
     * Construye el prompt según el tipo de contenido
     */
    private function build_prompt($type, $context, $options) {
        $language = $options['language'] ?? 'es';
        $tone = $options['tone'] ?? 'profesional';
        $length = $options['length'] ?? 'medio';

        $length_guide = [
            'corto' => '50-100 palabras',
            'medio' => '150-250 palabras',
            'largo' => '300-500 palabras',
        ];

        $base_prompt = "Genera contenido en {$language}. Tono: {$tone}. Longitud aproximada: {$length_guide[$length]}.\n\n";

        switch ($type) {
            case 'evento_descripcion':
                return $base_prompt . "Crea una descripción atractiva para un evento con estos datos:\n{$context}\n\nIncluye: introducción llamativa, qué se va a hacer, para quién es ideal, y un cierre motivador.";

            case 'evento_titulo':
                return "Genera 3 opciones de títulos creativos y atractivos para un evento sobre:\n{$context}\n\nFormato: un título por línea, sin numeración.";

            case 'post_blog':
                return $base_prompt . "Escribe un artículo de blog sobre:\n{$context}\n\nEstructura: título, introducción, 2-3 secciones con subtítulos, conclusión.";

            case 'pagina_bienvenida':
                return $base_prompt . "Crea el texto de una página de bienvenida para:\n{$context}\n\nIncluye: saludo cálido, propuesta de valor, beneficios principales, llamada a la acción.";

            case 'email_notificacion':
                $email_type = $options['email_type'] ?? 'general';
                return $base_prompt . "Redacta un email de notificación ({$email_type}) sobre:\n{$context}\n\nIncluye: asunto, saludo, cuerpo breve, despedida. Formato claro y directo.";

            case 'descripcion_modulo':
                return $base_prompt . "Escribe una descripción para un módulo/funcionalidad:\n{$context}\n\nIncluye: qué hace, beneficios principales, casos de uso.";

            case 'faq':
                return "Genera 5 preguntas frecuentes (FAQ) con sus respuestas sobre:\n{$context}\n\nFormato:\nP: [pregunta]\nR: [respuesta]\n\nPreguntas relevantes y respuestas concisas.";

            case 'slogan':
                return "Genera 5 slogans creativos y memorables para:\n{$context}\n\nCaracterísticas: breves, impactantes, fáciles de recordar. Un slogan por línea.";

            case 'bio':
                return $base_prompt . "Escribe una biografía/descripción para:\n{$context}\n\nTono profesional pero cercano.";

            default:
                return $base_prompt . "Genera contenido sobre:\n{$context}";
        }
    }

    /**
     * Obtiene el system prompt según el tipo
     */
    private function get_system_prompt($type) {
        $base = "Eres un redactor experto especializado en comunicación para comunidades y organizaciones. Tu contenido es claro, atractivo y orientado a la acción.";

        switch ($type) {
            case 'evento_descripcion':
            case 'evento_titulo':
                return $base . " Especializado en eventos comunitarios. Generas textos que motivan la participación.";

            case 'email_notificacion':
                return $base . " Especializado en comunicación por email. Textos concisos, claros y con llamadas a la acción efectivas.";

            case 'faq':
                return $base . " Especializado en documentación. Anticipas las dudas de los usuarios y das respuestas útiles.";

            default:
                return $base;
        }
    }

    /**
     * Genera una descripción de evento
     */
    public function generate_event_description($event_data) {
        $context = "Título: {$event_data['titulo']}\n";
        $context .= "Fecha: {$event_data['fecha']}\n";
        $context .= "Lugar: " . ($event_data['lugar'] ?? 'Por definir') . "\n";
        if (!empty($event_data['notas'])) {
            $context .= "Notas: {$event_data['notas']}\n";
        }

        return $this->generate('evento_descripcion', $context);
    }

    /**
     * Genera opciones de título para evento
     */
    public function generate_event_titles($topic) {
        return $this->generate('evento_titulo', $topic);
    }

    /**
     * Genera un post de blog
     */
    public function generate_blog_post($topic, $options = []) {
        return $this->generate('post_blog', $topic, $options);
    }

    /**
     * Genera un email de notificación
     */
    public function generate_notification_email($context, $email_type = 'general') {
        return $this->generate('email_notificacion', $context, ['email_type' => $email_type]);
    }

    /**
     * Genera FAQs
     */
    public function generate_faqs($topic) {
        return $this->generate('faq', $topic);
    }

    /**
     * Genera slogans
     */
    public function generate_slogans($brand_context) {
        return $this->generate('slogan', $brand_context);
    }
}

// Inicializar
add_action('init', function() {
    Flavor_AI_Content_Generator::get_instance();
});
