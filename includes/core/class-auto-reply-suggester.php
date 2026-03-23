<?php
/**
 * Sugeridor de Respuestas Automáticas
 *
 * Genera sugerencias de respuestas para:
 * - Incidencias/tickets de soporte
 * - Mensajes del chat interno
 * - Comentarios en foros
 * - Solicitudes de socios
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Auto_Reply_Suggester {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Motor de IA activo
     */
    private $engine = null;

    /**
     * Cache de respuestas frecuentes
     */
    private $response_templates = [];

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
        $this->load_response_templates();
        add_action('wp_ajax_flavor_get_reply_suggestions', [$this, 'ajax_get_suggestions']);
        add_action('wp_ajax_flavor_accept_suggestion', [$this, 'ajax_accept_suggestion']);
    }

    /**
     * Carga plantillas de respuestas comunes
     */
    private function load_response_templates() {
        $this->response_templates = [
            'incidencias' => [
                'recibida' => __('Hemos recibido tu incidencia y la estamos revisando. Te contactaremos pronto con una solución.', 'flavor-chat-ia'),
                'en_proceso' => __('Estamos trabajando en tu caso. Te mantendremos informado/a del progreso.', 'flavor-chat-ia'),
                'solicitar_info' => __('Para poder ayudarte mejor, necesitamos más información. ¿Podrías proporcionarnos los siguientes datos?', 'flavor-chat-ia'),
                'resuelta' => __('Tu incidencia ha sido resuelta. Si tienes alguna otra consulta, no dudes en contactarnos.', 'flavor-chat-ia'),
            ],
            'socios' => [
                'bienvenida' => __('¡Bienvenido/a a nuestra comunidad! Estamos encantados de tenerte con nosotros.', 'flavor-chat-ia'),
                'solicitud_recibida' => __('Hemos recibido tu solicitud de membresía. La revisaremos en breve.', 'flavor-chat-ia'),
                'solicitud_aprobada' => __('¡Enhorabuena! Tu solicitud ha sido aprobada. Ya puedes acceder a todos los beneficios de socio/a.', 'flavor-chat-ia'),
                'recordatorio_cuota' => __('Te recordamos que tienes una cuota pendiente de pago. Puedes realizarlo desde tu área de socio/a.', 'flavor-chat-ia'),
            ],
            'eventos' => [
                'inscripcion_confirmada' => __('Tu inscripción al evento ha sido confirmada. ¡Te esperamos!', 'flavor-chat-ia'),
                'recordatorio' => __('Te recordamos que mañana tienes el evento. ¡No faltes!', 'flavor-chat-ia'),
                'cancelacion' => __('Lamentamos informarte que el evento ha sido cancelado. Te contactaremos con más información.', 'flavor-chat-ia'),
            ],
            'reservas' => [
                'confirmada' => __('Tu reserva ha sido confirmada. Te esperamos en la fecha indicada.', 'flavor-chat-ia'),
                'pendiente' => __('Tu reserva está pendiente de confirmación. Te notificaremos en breve.', 'flavor-chat-ia'),
                'cancelada' => __('Tu reserva ha sido cancelada según tu solicitud.', 'flavor-chat-ia'),
            ],
            'grupos_consumo' => [
                'pedido_recibido' => __('Hemos recibido tu pedido. Te avisaremos cuando esté listo para recoger.', 'flavor-chat-ia'),
                'pedido_listo' => __('¡Tu pedido está listo! Puedes pasar a recogerlo en el horario habitual.', 'flavor-chat-ia'),
                'ciclo_abierto' => __('Se ha abierto un nuevo ciclo de pedidos. ¡No te quedes sin tus productos favoritos!', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Obtiene el motor de IA
     */
    private function get_engine() {
        if ($this->engine === null && class_exists('Flavor_Engine_Manager')) {
            $manager = Flavor_Engine_Manager::get_instance();
            $this->engine = $manager->get_active_engine();
        }
        return $this->engine;
    }

    /**
     * Handler AJAX para obtener sugerencias
     */
    public function ajax_get_suggestions() {
        check_ajax_referer('flavor_auto_reply', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $context_type = sanitize_text_field($_POST['context_type'] ?? '');
        $original_message = sanitize_textarea_field($_POST['message'] ?? '');
        $metadata = isset($_POST['metadata']) ? (array) $_POST['metadata'] : [];

        $suggestions = $this->get_suggestions($context_type, $original_message, $metadata);

        wp_send_json_success(['suggestions' => $suggestions]);
    }

    /**
     * Handler AJAX para registrar aceptación de sugerencia
     */
    public function ajax_accept_suggestion() {
        check_ajax_referer('flavor_auto_reply', 'nonce');

        $suggestion_id = sanitize_text_field($_POST['suggestion_id'] ?? '');
        $context_type = sanitize_text_field($_POST['context_type'] ?? '');

        // Registrar para mejorar futuras sugerencias
        $this->log_accepted_suggestion($suggestion_id, $context_type);

        wp_send_json_success();
    }

    /**
     * Obtiene sugerencias de respuesta
     *
     * @param string $context_type Tipo de contexto (incidencias, socios, etc.)
     * @param string $original_message Mensaje original a responder
     * @param array $metadata Metadatos adicionales
     * @return array
     */
    public function get_suggestions($context_type, $original_message, $metadata = []) {
        $suggestions = [];

        // 1. Sugerencias de plantillas predefinidas
        $template_suggestions = $this->get_template_suggestions($context_type, $original_message);
        $suggestions = array_merge($suggestions, $template_suggestions);

        // 2. Sugerencias generadas por IA (si está disponible)
        $ai_suggestions = $this->get_ai_suggestions($context_type, $original_message, $metadata);
        $suggestions = array_merge($suggestions, $ai_suggestions);

        // Limitar a 3 sugerencias
        return array_slice($suggestions, 0, 3);
    }

    /**
     * Obtiene sugerencias de plantillas predefinidas
     */
    private function get_template_suggestions($context_type, $message) {
        $suggestions = [];
        $message_lower = mb_strtolower($message);

        if (!isset($this->response_templates[$context_type])) {
            return $suggestions;
        }

        // Detectar intención del mensaje
        $keywords = $this->detect_keywords($message_lower);

        foreach ($this->response_templates[$context_type] as $template_key => $template_text) {
            $relevance = $this->calculate_template_relevance($template_key, $keywords, $context_type);
            if ($relevance > 0.3) {
                $suggestions[] = [
                    'id' => "template_{$context_type}_{$template_key}",
                    'type' => 'template',
                    'text' => $template_text,
                    'relevance' => $relevance,
                    'label' => $this->get_template_label($template_key),
                ];
            }
        }

        // Ordenar por relevancia
        usort($suggestions, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });

        return array_slice($suggestions, 0, 2);
    }

    /**
     * Detecta palabras clave en el mensaje
     */
    private function detect_keywords($message) {
        $keyword_patterns = [
            'urgente' => ['urgente', 'urgencia', 'importante', 'crítico', 'grave'],
            'problema' => ['problema', 'error', 'fallo', 'no funciona', 'roto'],
            'pregunta' => ['cómo', 'qué', 'cuándo', 'dónde', 'por qué', '?'],
            'queja' => ['queja', 'molesto', 'mal', 'decepcionado', 'insatisfecho'],
            'solicitud' => ['solicito', 'quisiera', 'necesito', 'por favor', 'podría'],
            'agradecimiento' => ['gracias', 'agradezco', 'perfecto', 'genial', 'excelente'],
            'cancelacion' => ['cancelar', 'anular', 'dar de baja', 'desuscribir'],
            'informacion' => ['información', 'saber', 'conocer', 'detalles', 'más info'],
        ];

        $detected_keywords = [];
        foreach ($keyword_patterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($message, $pattern) !== false) {
                    $detected_keywords[$category] = true;
                    break;
                }
            }
        }

        return $detected_keywords;
    }

    /**
     * Calcula la relevancia de una plantilla
     */
    private function calculate_template_relevance($template_key, $keywords, $context_type) {
        $relevance_map = [
            'incidencias' => [
                'recibida' => ['problema' => 0.8, 'urgente' => 0.6],
                'en_proceso' => ['pregunta' => 0.7],
                'solicitar_info' => ['informacion' => 0.6],
                'resuelta' => ['agradecimiento' => 0.9],
            ],
            'socios' => [
                'bienvenida' => [],
                'solicitud_recibida' => ['solicitud' => 0.9],
                'solicitud_aprobada' => [],
                'recordatorio_cuota' => [],
            ],
            'eventos' => [
                'inscripcion_confirmada' => ['solicitud' => 0.7],
                'recordatorio' => [],
                'cancelacion' => ['cancelacion' => 0.9],
            ],
            'reservas' => [
                'confirmada' => ['solicitud' => 0.7],
                'pendiente' => ['pregunta' => 0.6],
                'cancelada' => ['cancelacion' => 0.9],
            ],
            'grupos_consumo' => [
                'pedido_recibido' => ['solicitud' => 0.8],
                'pedido_listo' => [],
                'ciclo_abierto' => [],
            ],
        ];

        $base_relevance = 0.3;
        $relevance = $base_relevance;

        if (isset($relevance_map[$context_type][$template_key])) {
            foreach ($relevance_map[$context_type][$template_key] as $keyword => $weight) {
                if (isset($keywords[$keyword])) {
                    $relevance = max($relevance, $weight);
                }
            }
        }

        return $relevance;
    }

    /**
     * Obtiene etiqueta legible para plantilla
     */
    private function get_template_label($template_key) {
        $labels = [
            'recibida' => __('Acuse de recibo', 'flavor-chat-ia'),
            'en_proceso' => __('En proceso', 'flavor-chat-ia'),
            'solicitar_info' => __('Solicitar información', 'flavor-chat-ia'),
            'resuelta' => __('Caso resuelto', 'flavor-chat-ia'),
            'bienvenida' => __('Bienvenida', 'flavor-chat-ia'),
            'solicitud_recibida' => __('Solicitud recibida', 'flavor-chat-ia'),
            'solicitud_aprobada' => __('Solicitud aprobada', 'flavor-chat-ia'),
            'recordatorio_cuota' => __('Recordatorio cuota', 'flavor-chat-ia'),
            'inscripcion_confirmada' => __('Inscripción confirmada', 'flavor-chat-ia'),
            'recordatorio' => __('Recordatorio', 'flavor-chat-ia'),
            'cancelacion' => __('Cancelación', 'flavor-chat-ia'),
            'confirmada' => __('Reserva confirmada', 'flavor-chat-ia'),
            'pendiente' => __('Pendiente', 'flavor-chat-ia'),
            'cancelada' => __('Cancelada', 'flavor-chat-ia'),
            'pedido_recibido' => __('Pedido recibido', 'flavor-chat-ia'),
            'pedido_listo' => __('Pedido listo', 'flavor-chat-ia'),
            'ciclo_abierto' => __('Nuevo ciclo', 'flavor-chat-ia'),
        ];

        return $labels[$template_key] ?? ucfirst(str_replace('_', ' ', $template_key));
    }

    /**
     * Obtiene sugerencias generadas por IA
     */
    private function get_ai_suggestions($context_type, $original_message, $metadata = []) {
        $engine = $this->get_engine();
        if (!$engine || !$engine->is_configured()) {
            return [];
        }

        // Limitar uso de IA para no saturar
        $cache_key = 'flavor_reply_' . md5($context_type . $original_message);
        $cached_suggestion = get_transient($cache_key);
        if ($cached_suggestion !== false) {
            return [$cached_suggestion];
        }

        $system_prompt = $this->build_system_prompt($context_type);
        $user_prompt = $this->build_user_prompt($context_type, $original_message, $metadata);

        try {
            $response = $engine->send_message(
                [['role' => 'user', 'content' => $user_prompt]],
                $system_prompt,
                []
            );

            if ($response['success'] && !empty($response['response'])) {
                $ai_suggestion = [
                    'id' => 'ai_' . substr(md5($response['response']), 0, 8),
                    'type' => 'ai',
                    'text' => $response['response'],
                    'relevance' => 0.85,
                    'label' => __('Sugerencia IA', 'flavor-chat-ia'),
                ];

                // Cache por 5 minutos
                set_transient($cache_key, $ai_suggestion, 5 * MINUTE_IN_SECONDS);

                return [$ai_suggestion];
            }
        } catch (Exception $e) {
            error_log('Flavor Auto Reply AI Error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Construye el system prompt para IA
     */
    private function build_system_prompt($context_type) {
        $base = "Eres un asistente que ayuda a redactar respuestas profesionales y empáticas para una organización comunitaria. ";
        $base .= "Genera respuestas concisas, claras y con un tono amable pero profesional. ";
        $base .= "Las respuestas deben ser en español y no exceder 2-3 frases.";

        $context_specific = [
            'incidencias' => " Estás respondiendo a tickets de soporte. Muestra comprensión del problema y ofrece ayuda concreta.",
            'socios' => " Estás comunicándote con miembros de la comunidad. Sé cercano pero profesional.",
            'eventos' => " Estás gestionando comunicaciones sobre eventos comunitarios. Sé entusiasta pero informativo.",
            'reservas' => " Estás gestionando reservas de espacios o recursos. Sé claro con fechas y condiciones.",
            'grupos_consumo' => " Estás gestionando un grupo de consumo. Menciona productos y plazos si es relevante.",
            'foros' => " Estás moderando o participando en foros comunitarios. Fomenta el diálogo constructivo.",
        ];

        return $base . ($context_specific[$context_type] ?? '');
    }

    /**
     * Construye el prompt de usuario para IA
     */
    private function build_user_prompt($context_type, $original_message, $metadata) {
        $prompt = "Genera una respuesta breve y profesional para el siguiente mensaje:\n\n";
        $prompt .= "\"" . $original_message . "\"\n\n";

        if (!empty($metadata)) {
            $prompt .= "Contexto adicional:\n";
            if (isset($metadata['sender_name'])) {
                $prompt .= "- Remitente: {$metadata['sender_name']}\n";
            }
            if (isset($metadata['status'])) {
                $prompt .= "- Estado actual: {$metadata['status']}\n";
            }
            if (isset($metadata['priority'])) {
                $prompt .= "- Prioridad: {$metadata['priority']}\n";
            }
        }

        $prompt .= "\nResponde únicamente con el texto de la respuesta, sin explicaciones adicionales.";

        return $prompt;
    }

    /**
     * Registra sugerencia aceptada para mejorar futuras sugerencias
     */
    private function log_accepted_suggestion($suggestion_id, $context_type) {
        $log = get_option('flavor_suggestion_log', []);

        $log[] = [
            'suggestion_id' => $suggestion_id,
            'context_type' => $context_type,
            'timestamp' => current_time('timestamp'),
            'user_id' => get_current_user_id(),
        ];

        // Mantener solo los últimos 100 registros
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }

        update_option('flavor_suggestion_log', $log);
    }

    /**
     * Obtiene sugerencia rápida para incidencia
     */
    public function suggest_for_incidencia($incidencia_data) {
        return $this->get_suggestions('incidencias', $incidencia_data['descripcion'] ?? '', [
            'status' => $incidencia_data['estado'] ?? '',
            'priority' => $incidencia_data['prioridad'] ?? '',
            'sender_name' => $incidencia_data['usuario_nombre'] ?? '',
        ]);
    }

    /**
     * Obtiene sugerencia rápida para mensaje de chat
     */
    public function suggest_for_chat_message($message, $sender_name = '') {
        return $this->get_suggestions('chat', $message, [
            'sender_name' => $sender_name,
        ]);
    }

    /**
     * Obtiene sugerencia rápida para solicitud de socio
     */
    public function suggest_for_solicitud_socio($solicitud_data) {
        return $this->get_suggestions('socios', $solicitud_data['mensaje'] ?? '', [
            'sender_name' => $solicitud_data['nombre'] ?? '',
            'status' => $solicitud_data['estado'] ?? 'pendiente',
        ]);
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Auto_Reply_Suggester::get_instance();
});
