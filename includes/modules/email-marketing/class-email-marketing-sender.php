<?php
/**
 * Clase para envío de emails
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_EM_Sender {

    /**
     * Configuración
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct($settings = []) {
        $this->settings = $settings;
    }

    /**
     * Enviar email
     */
    public function enviar($email_data) {
        $to = $email_data->email;
        $subject = $email_data->asunto;
        $message = $email_data->contenido;

        // Headers
        $headers = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        if (!empty($this->settings['remitente_nombre']) && !empty($this->settings['remitente_email'])) {
            $headers[] = sprintf(
                'From: %s <%s>',
                $this->settings['remitente_nombre'],
                $this->settings['remitente_email']
            );
        }

        if (!empty($this->settings['responder_a'])) {
            $headers[] = 'Reply-To: ' . $this->settings['responder_a'];
        }

        // Añadir tracking pixel si está habilitado
        if (!empty($this->settings['tracking_aperturas'])) {
            $hash = md5($to . $email_data->id);
            $tracking_url = add_query_arg('em_track', $hash, home_url('/'));
            $pixel = '<img src="' . esc_url($tracking_url) . '" width="1" height="1" alt="" style="display:none;">';
            $message = str_replace('</body>', $pixel . '</body>', $message);
        }

        // Reemplazar enlaces para tracking si está habilitado
        if (!empty($this->settings['tracking_clicks'])) {
            $message = $this->agregar_tracking_enlaces($message, $email_data);
        }

        // Configurar SMTP si es necesario
        if (!empty($this->settings['proveedor_smtp']) && $this->settings['proveedor_smtp'] !== 'wp_mail') {
            add_action('phpmailer_init', [$this, 'configurar_smtp']);
        }

        $enviado = wp_mail($to, $subject, $message, $headers);

        // Limpiar hook de SMTP
        remove_action('phpmailer_init', [$this, 'configurar_smtp']);

        if ($enviado) {
            return ['success' => true];
        }

        global $phpmailer;
        $error = isset($phpmailer) && isset($phpmailer->ErrorInfo) ? $phpmailer->ErrorInfo : __('Error desconocido', 'flavor-chat-ia');

        return ['success' => false, 'error' => $error];
    }

    /**
     * Configurar SMTP
     */
    public function configurar_smtp($phpmailer) {
        if (empty($this->settings['smtp_host'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $this->settings['smtp_host'];
        $phpmailer->Port = intval($this->settings['smtp_puerto']);
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $this->settings['smtp_usuario'];
        $phpmailer->Password = $this->settings['smtp_password'];

        if (!empty($this->settings['smtp_encriptacion'])) {
            $phpmailer->SMTPSecure = $this->settings['smtp_encriptacion'];
        }
    }

    /**
     * Agregar tracking a enlaces
     */
    private function agregar_tracking_enlaces($contenido, $email_data) {
        $hash = md5($email_data->email . $email_data->id);

        // Buscar todos los enlaces
        $patron = '/<a\s+(?:[^>]*?\s+)?href=["\']([^"\']+)["\']/i';

        return preg_replace_callback($patron, function ($matches) use ($hash) {
            $url_original = $matches[1];

            // No trackear enlaces especiales
            if (strpos($url_original, 'mailto:') === 0 ||
                strpos($url_original, 'tel:') === 0 ||
                strpos($url_original, '#') === 0 ||
                strpos($url_original, 'em_track') !== false ||
                strpos($url_original, 'em_click') !== false) {
                return $matches[0];
            }

            $data = base64_encode($hash . '|' . $url_original);
            $url_tracking = add_query_arg('em_click', $data, home_url('/'));

            return str_replace($url_original, $url_tracking, $matches[0]);
        }, $contenido);
    }

    /**
     * Enviar email de prueba
     */
    public function enviar_test($to, $campania) {
        $email_data = new stdClass();
        $email_data->id = 'test_' . time();
        $email_data->email = $to;
        $email_data->asunto = '[TEST] ' . $campania->asunto;
        $email_data->contenido = $campania->contenido_html;

        return $this->enviar($email_data);
    }

    /**
     * Validar configuración SMTP
     */
    public function validar_smtp() {
        if (empty($this->settings['smtp_host'])) {
            return ['success' => false, 'error' => __('Host SMTP no configurado', 'flavor-chat-ia')];
        }

        // Intentar enviar email de prueba al admin
        $admin_email = get_option('admin_email');

        $email_data = new stdClass();
        $email_data->id = 'smtp_test_' . time();
        $email_data->email = $admin_email;
        $email_data->asunto = __('Test de configuración SMTP', 'flavor-chat-ia');
        $email_data->contenido = sprintf(
            '<p>%s</p><p>%s: %s</p>',
            __('Este es un email de prueba para verificar la configuración SMTP.', 'flavor-chat-ia'),
            __('Fecha', 'flavor-chat-ia'),
            current_time('mysql')
        );

        return $this->enviar($email_data);
    }
}
