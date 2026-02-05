<?php
/**
 * Base de conocimiento personalizada
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Knowledge_Base {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Chat_Knowledge_Base
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
     * Obtiene el contexto completo para el system prompt
     *
     * @param string $language
     * @return string
     */
    public function get_full_context($language = 'es') {
        $context = [];
        $settings = get_option('flavor_chat_ia_settings', []);

        // Información del negocio
        $business_info = $settings['business_info'] ?? [];
        if (!empty($business_info)) {
            $context[] = "INFORMACIÓN DEL NEGOCIO:";
            if (!empty($business_info['name'])) {
                $context[] = "- Nombre: {$business_info['name']}";
            }
            if (!empty($business_info['description'])) {
                $context[] = "- Descripción: {$business_info['description']}";
            }
            if (!empty($business_info['address'])) {
                $context[] = "- Dirección: {$business_info['address']}";
            }
            if (!empty($business_info['phone'])) {
                $context[] = "- Teléfono: {$business_info['phone']}";
            }
            if (!empty($business_info['email'])) {
                $context[] = "- Email: {$business_info['email']}";
            }
            if (!empty($business_info['schedule'])) {
                $context[] = "- Horario: {$business_info['schedule']}";
            }
        }

        // Políticas
        $policies = $settings['policies'] ?? [];
        if (!empty($policies)) {
            $context[] = "\nPOLÍTICAS:";
            if (!empty($policies['shipping'])) {
                $context[] = "Envíos: {$policies['shipping']}";
            }
            if (!empty($policies['returns'])) {
                $context[] = "Devoluciones: {$policies['returns']}";
            }
            if (!empty($policies['privacy'])) {
                $context[] = "Privacidad: {$policies['privacy']}";
            }
        }

        // Conocimiento personalizado desde settings (legacy support)
        $custom_knowledge = $settings['knowledge_base'] ?? [];
        if (!empty($custom_knowledge)) {
            $context[] = "\nINFORMACIÓN ADICIONAL:";
            foreach ($custom_knowledge as $item) {
                $titulo = $item['titulo'] ?? $item['title'] ?? '';
                $contenido = $item['contenido'] ?? $item['content'] ?? '';
                if (!empty($titulo) && !empty($contenido)) {
                    $context[] = "- {$titulo}: {$contenido}";
                }
            }
        }

        // FAQs personalizadas (soporte para ambos formatos de campo)
        $faqs = $settings['faqs'] ?? [];
        if (!empty($faqs)) {
            $context[] = "\nPREGUNTAS FRECUENTES:";
            foreach ($faqs as $faq) {
                $question = $faq['question'] ?? $faq['pregunta'] ?? '';
                $answer = $faq['answer'] ?? $faq['respuesta'] ?? '';
                if (!empty($question) && !empty($answer)) {
                    $context[] = "P: {$question}";
                    $context[] = "R: {$answer}";
                }
            }
        }

        // Información de contacto/escalado
        $contact_info = [];
        if (!empty($settings['escalation_phone'])) {
            $contact_info[] = "Teléfono: {$settings['escalation_phone']}";
        }
        if (!empty($settings['escalation_whatsapp'])) {
            $contact_info[] = "WhatsApp: {$settings['escalation_whatsapp']}";
        }
        if (!empty($settings['escalation_email'])) {
            $contact_info[] = "Email: {$settings['escalation_email']}";
        }
        if (!empty($settings['escalation_hours'])) {
            $contact_info[] = "Horario de atención: {$settings['escalation_hours']}";
        }

        if (!empty($contact_info)) {
            $context[] = "\nCONTACTO PARA ESCALADO:";
            $context[] = implode("\n", $contact_info);
        }

        return implode("\n", $context);
    }

    /**
     * Invalida la caché de conocimiento
     */
    public function invalidate_cache() {
        delete_transient('flavor_chat_knowledge_context');
    }

    /**
     * Añade conocimiento desde la interfaz
     *
     * @param string $titulo
     * @param string $contenido
     * @return bool
     */
    public function add_knowledge($titulo, $contenido) {
        $settings = get_option('flavor_chat_ia_settings', []);

        if (!isset($settings['knowledge_base'])) {
            $settings['knowledge_base'] = [];
        }

        $settings['knowledge_base'][] = [
            'titulo' => sanitize_text_field($titulo),
            'contenido' => sanitize_textarea_field($contenido),
        ];

        return update_option('flavor_chat_ia_settings', $settings);
    }

    /**
     * Añade una FAQ
     *
     * @param string $pregunta
     * @param string $respuesta
     * @return bool
     */
    public function add_faq($pregunta, $respuesta) {
        $settings = get_option('flavor_chat_ia_settings', []);

        if (!isset($settings['faqs'])) {
            $settings['faqs'] = [];
        }

        $settings['faqs'][] = [
            'pregunta' => sanitize_text_field($pregunta),
            'respuesta' => sanitize_textarea_field($respuesta),
        ];

        return update_option('flavor_chat_ia_settings', $settings);
    }
}
