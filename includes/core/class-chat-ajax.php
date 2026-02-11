<?php
/**
 * Manejadores AJAX para el chat
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Ajax {

    /**
     * Registra los hooks AJAX
     */
    public static function register_hooks() {
        // Para usuarios logueados y no logueados
        add_action('wp_ajax_flavor_chat_send', [__CLASS__, 'handle_send_message']);
        add_action('wp_ajax_nopriv_flavor_chat_send', [__CLASS__, 'handle_send_message']);

        add_action('wp_ajax_flavor_chat_start', [__CLASS__, 'handle_start_session']);
        add_action('wp_ajax_nopriv_flavor_chat_start', [__CLASS__, 'handle_start_session']);
    }

    /**
     * Maneja el envío de mensajes
     */
    public static function handle_send_message() {
        // Verificar nonce
        if (!check_ajax_referer('flavor_chat_nonce', 'nonce', false)) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-chat-ia')], 403);
        }

        // Rate limiting básico
        if (!self::check_rate_limit()) {
            wp_send_json_error(['error' => __('Demasiadas solicitudes. Espera un momento.', 'flavor-chat-ia')], 429);
        }

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $language = sanitize_text_field($_POST['language'] ?? 'es');
        $honeypot = sanitize_text_field($_POST['website_url'] ?? ''); // Campo honeypot
        $ip = self::get_client_ip();

        // Validar idioma
        $supported_languages = ['es', 'eu', 'en', 'fr', 'ca'];
        if (!in_array($language, $supported_languages)) {
            $language = 'es';
        }

        if (empty($message)) {
            wp_send_json_error(['error' => __('Mensaje vacío', 'flavor-chat-ia')]);
        }

        if (strlen($message) > 2000) {
            wp_send_json_error(['error' => __('Mensaje demasiado largo', 'flavor-chat-ia')]);
        }

        // Sistema Antispam
        if (class_exists('Flavor_Chat_Antispam')) {
            $antispam = Flavor_Chat_Antispam::get_instance();
            $validation = $antispam->validate_message($message, $session_id, $ip, [
                'honeypot' => $honeypot,
            ]);

            if (!$validation['valid']) {
                wp_send_json_error([
                    'error' => $validation['error'],
                    'error_code' => $validation['error_code'],
                ]);
            }
        }

        // Obtener o crear sesión
        $session = new Flavor_Chat_Session($session_id);

        if (!$session->get_conversation_id()) {
            $session->start_conversation($language);
        }

        // Guardar mensaje del usuario
        $session->add_message('user', $message);

        // Enviar a Claude
        $engine = Flavor_Chat_Claude_Engine::get_instance();
        $response = $engine->send_message($session, $message);

        if (!$response['success']) {
            // Respuesta de fallback
            $fallback = self::get_fallback_response($language);
            wp_send_json_error([
                'error' => $response['error'] ?? __('Error al procesar mensaje', 'flavor-chat-ia'),
                'fallback' => $fallback,
            ]);
        }

        wp_send_json_success([
            'response' => $response['response'],
            'session_id' => $session->get_session_id(),
            'cart_updated' => $response['cart_updated'] ?? false,
        ]);
    }

    /**
     * Maneja el inicio de sesión
     */
    public static function handle_start_session() {
        // Verificar nonce
        if (!check_ajax_referer('flavor_chat_nonce', 'nonce', false)) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-chat-ia')], 403);
        }

        $language = sanitize_text_field($_POST['language'] ?? 'es');

        $session = new Flavor_Chat_Session();
        $session->start_conversation($language);

        wp_send_json_success([
            'session_id' => $session->get_session_id(),
            'conversation_id' => $session->get_conversation_id(),
        ]);
    }

    /**
     * Verifica rate limiting
     *
     * @return bool
     */
    private static function check_rate_limit() {
        $ip = self::get_client_ip();
        $key = 'flavor_chat_rate_' . md5($ip);

        $count = get_transient($key);

        if ($count === false) {
            set_transient($key, 1, 60); // 1 minuto
            return true;
        }

        if ($count >= 20) { // Máximo 20 mensajes por minuto
            return false;
        }

        set_transient($key, $count + 1, 60);
        return true;
    }

    /**
     * Obtiene IP del cliente
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                    return trim($ip);
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Respuesta de fallback
     *
     * @param string $language
     * @return string
     */
    private static function get_fallback_response($language) {
        $responses = [
            'es' => 'Lo siento, estoy teniendo problemas técnicos. ¿Podrías intentarlo de nuevo en unos momentos?',
            'en' => 'Sorry, I\'m experiencing technical difficulties. Could you try again in a few moments?',
            'eu' => 'Barkatu, arazo teknikoak ditut. Momentu batzuk barru berriro saia zaitezke?',
            'fr' => 'Désolé, je rencontre des difficultés techniques. Pourriez-vous réessayer dans quelques instants?',
            'ca' => 'Ho sento, estic tenint problemes tècnics. Podries intentar-ho de nou en uns moments?',
        ];

        return $responses[$language] ?? $responses['es'];
    }
}
