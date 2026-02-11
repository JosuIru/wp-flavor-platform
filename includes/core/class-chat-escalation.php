<?php
/**
 * Gestión de escalado a humanos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Escalation {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Chat_Escalation
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
     * Crea un ticket de escalado
     *
     * @param Flavor_Chat_Session $session
     * @param string $reason
     * @param string $contact_method
     * @return array
     */
    public function create_escalation($session, $reason, $contact_method = null) {
        global $wpdb;

        $conversation_id = $session->get_conversation_id();

        if (!$conversation_id) {
            return [
                'success' => false,
                'error' => __('No hay conversación activa', 'flavor-chat-ia'),
            ];
        }

        // Generar resumen de la conversación
        $summary = $this->generate_summary($session);

        // Insertar en BD
        $result = $wpdb->insert(
            $wpdb->prefix . 'flavor_chat_escalations',
            [
                'conversation_id' => $conversation_id,
                'reason' => $reason,
                'summary' => $summary,
                'contact_method' => $contact_method,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return [
                'success' => false,
                'error' => __('Error al crear escalado', 'flavor-chat-ia'),
            ];
        }

        $escalation_id = $wpdb->insert_id;

        // Marcar conversación como escalada
        $session->mark_escalated($reason);

        // Notificar al admin
        $this->notify_admin($escalation_id, $reason, $summary, $contact_method);

        // Obtener información de contacto
        $settings = get_option('flavor_chat_ia_settings', []);
        $contact_info = $this->get_contact_info($settings, $contact_method);

        return [
            'success' => true,
            'escalation_id' => $escalation_id,
            'contact_info' => $contact_info,
            'message' => $this->get_escalation_message($contact_method, $contact_info),
        ];
    }

    /**
     * Genera un resumen de la conversación
     *
     * @param Flavor_Chat_Session $session
     * @return string
     */
    private function generate_summary($session) {
        $messages = $session->get_messages(10); // Últimos 10 mensajes
        $summary = [];

        foreach ($messages as $msg) {
            $role = $msg['role'] === 'user' ? 'Cliente' : 'Asistente';
            $content = wp_trim_words($msg['content'], 30);
            $summary[] = "{$role}: {$content}";
        }

        return implode("\n", $summary);
    }

    /**
     * Notifica al administrador
     *
     * @param int $escalation_id
     * @param string $reason
     * @param string $summary
     * @param string $contact_method
     */
    private function notify_admin($escalation_id, $reason, $summary, $contact_method) {
        $settings = get_option('flavor_chat_ia_settings', []);
        $admin_email = $settings['escalation_email'] ?? get_option('admin_email');

        if (empty($admin_email)) {
            return;
        }

        $site_name = get_bloginfo('name');

        $subject = sprintf(
            '[%s] Nueva solicitud de atención #%d',
            $site_name,
            $escalation_id
        );

        $body = sprintf(
            "Se ha recibido una nueva solicitud de atención en el chat.\n\n" .
            "ID: #%d\n" .
            "Motivo: %s\n" .
            "Método de contacto preferido: %s\n\n" .
            "Resumen de la conversación:\n%s\n\n" .
            "Gestionar desde: %s",
            $escalation_id,
            $reason,
            $contact_method ?: 'No especificado',
            $summary,
            admin_url('admin.php?page=flavor-chat-ia-escalations')
        );

        wp_mail($admin_email, $subject, $body);
    }

    /**
     * Obtiene información de contacto
     *
     * @param array $settings
     * @param string $preferred_method
     * @return array
     */
    private function get_contact_info($settings, $preferred_method = null) {
        $info = [];

        if (!empty($settings['escalation_phone'])) {
            $info['phone'] = $settings['escalation_phone'];
        }

        if (!empty($settings['escalation_whatsapp'])) {
            $info['whatsapp'] = $settings['escalation_whatsapp'];
            $info['whatsapp_url'] = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $settings['escalation_whatsapp']);
        }

        if (!empty($settings['escalation_email'])) {
            $info['email'] = $settings['escalation_email'];
        }

        if (!empty($settings['escalation_hours'])) {
            $info['hours'] = $settings['escalation_hours'];
        }

        return $info;
    }

    /**
     * Obtiene el mensaje de escalado según el método
     *
     * @param string $method
     * @param array $contact_info
     * @return string
     */
    private function get_escalation_message($method, $contact_info) {
        $messages = [];

        switch ($method) {
            case 'whatsapp':
                if (!empty($contact_info['whatsapp'])) {
                    $messages[] = sprintf(
                        'Puedes contactarnos por WhatsApp: %s',
                        $contact_info['whatsapp']
                    );
                }
                break;

            case 'phone':
                if (!empty($contact_info['phone'])) {
                    $messages[] = sprintf(
                        'Puedes llamarnos al: %s',
                        $contact_info['phone']
                    );
                }
                break;

            case 'email':
                if (!empty($contact_info['email'])) {
                    $messages[] = sprintf(
                        'Puedes escribirnos a: %s',
                        $contact_info['email']
                    );
                }
                break;
        }

        if (!empty($contact_info['hours'])) {
            $messages[] = sprintf('Horario de atención: %s', $contact_info['hours']);
        }

        if (empty($messages)) {
            $messages[] = 'Hemos notificado a nuestro equipo. Te contactarán pronto.';
        }

        return implode("\n", $messages);
    }

    /**
     * Obtiene los escalados pendientes
     *
     * @return array
     */
    public function get_pending_escalations() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT e.*, c.session_id, c.started_at as conversation_started
             FROM {$wpdb->prefix}flavor_chat_escalations e
             JOIN {$wpdb->prefix}flavor_chat_conversations c ON e.conversation_id = c.id
             WHERE e.status = 'pending'
             ORDER BY e.created_at DESC",
            ARRAY_A
        );
    }

    /**
     * Actualiza el estado de un escalado
     *
     * @param int $escalation_id
     * @param string $status
     * @param string $notes
     * @return bool
     */
    public function update_status($escalation_id, $status, $notes = '') {
        global $wpdb;

        $data = ['status' => $status];
        $format = ['%s'];

        if ($status === 'resolved') {
            $data['resolved_at'] = current_time('mysql');
            $format[] = '%s';
        }

        if (!empty($notes)) {
            $data['notes'] = $notes;
            $format[] = '%s';
        }

        return $wpdb->update(
            $wpdb->prefix . 'flavor_chat_escalations',
            $data,
            ['id' => $escalation_id],
            $format,
            ['%d']
        ) !== false;
    }
}
