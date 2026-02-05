<?php
/**
 * Handler SSE para streaming de respuestas del chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Stream {

    /**
     * Registra el endpoint AJAX para streaming
     */
    public static function register_hooks() {
        add_action('wp_ajax_flavor_chat_send_stream', [__CLASS__, 'handle_send_message_stream']);
        add_action('wp_ajax_nopriv_flavor_chat_send_stream', [__CLASS__, 'handle_send_message_stream']);
    }

    /**
     * Maneja el envío de mensajes con streaming SSE
     */
    public static function handle_send_message_stream() {
        // Verificar nonce (compatible con ambos sistemas de assets)
        $nonce_valido = check_ajax_referer('flavor_chat_nonce', 'nonce', false)
            || check_ajax_referer('chat_ia_nonce', 'nonce', false);
        if (!$nonce_valido) {
            self::enviar_error_sse('Nonce inválido');
            exit;
        }

        // Rate limiting
        $ip_cliente = self::obtener_ip_cliente();
        $clave_rate = 'flavor_chat_rate_' . md5($ip_cliente);
        $contador = get_transient($clave_rate);
        if ($contador !== false && $contador >= 20) {
            self::enviar_error_sse('Demasiadas solicitudes. Espera un momento.');
            exit;
        }
        set_transient($clave_rate, ($contador ?: 0) + 1, 60);

        $mensaje = sanitize_textarea_field($_POST['message'] ?? '');
        $id_sesion = sanitize_text_field($_POST['session_id'] ?? '');
        $idioma = sanitize_text_field($_POST['language'] ?? 'es');
        $honeypot = sanitize_text_field($_POST['website_url'] ?? '');

        // Validaciones
        if (empty($mensaje)) {
            self::enviar_error_sse('Mensaje vacío');
            exit;
        }

        if (strlen($mensaje) > 2000) {
            self::enviar_error_sse('Mensaje demasiado largo');
            exit;
        }

        // Antispam
        if (class_exists('Flavor_Chat_Antispam')) {
            $antispam = Flavor_Chat_Antispam::get_instance();
            $validacion = $antispam->validate_message($mensaje, $id_sesion, $ip_cliente, [
                'honeypot' => $honeypot,
            ]);
            if (!$validacion['valid']) {
                self::enviar_error_sse($validacion['error']);
                exit;
            }
        }

        // Configurar headers SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Nginx

        // Desactivar output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Obtener o crear sesión
        $sesion = new Flavor_Chat_Session($id_sesion);
        if (!$sesion->get_conversation_id()) {
            $sesion->start_conversation($idioma);
        }

        // Guardar mensaje del usuario
        $sesion->add_message('user', $mensaje);

        // Enviar session_id como primer evento
        self::enviar_evento_sse('session', [
            'session_id' => $sesion->get_session_id(),
        ]);

        // Obtener engine activo via Engine Manager
        if (class_exists('Flavor_Engine_Manager')) {
            $manager = Flavor_Engine_Manager::get_instance();
            $motor_activo = $manager->get_active_engine();
            if ($motor_activo && method_exists($motor_activo, 'send_message_stream')) {
                self::ejecutar_streaming($motor_activo, $sesion, $mensaje);
                exit;
            }
        }

        // Fallback: usar engine legacy sin streaming
        if (class_exists('Flavor_Chat_Claude_Engine')) {
            $motor_legacy = Flavor_Chat_Claude_Engine::get_instance();
            $respuesta = $motor_legacy->send_message($sesion, $mensaje);
            if ($respuesta['success']) {
                self::enviar_evento_sse('token', ['token' => $respuesta['response']]);
                $sesion->add_message('assistant', $respuesta['response']);
            } else {
                self::enviar_error_sse($respuesta['error'] ?? 'Error al procesar mensaje');
            }
            self::enviar_evento_sse('done', ['full_response' => $respuesta['response'] ?? '']);
        } else {
            self::enviar_error_sse('No hay motor de IA configurado');
            self::enviar_evento_sse('done', []);
        }

        exit;
    }

    /**
     * Ejecuta streaming con un engine que soporta send_message_stream
     */
    private static function ejecutar_streaming($engine, $sesion, $mensaje) {
        $settings = get_option('flavor_chat_ia_settings', []);
        $system_prompt = $settings['assistant_role'] ?? '';

        // Obtener historial de mensajes de la sesión
        $historial_mensajes = $sesion->get_messages();

        // Formatear mensajes para el engine
        $mensajes_formateados = [];
        if (is_array($historial_mensajes)) {
            foreach ($historial_mensajes as $msg) {
                $mensajes_formateados[] = [
                    'role' => $msg['role'] ?? 'user',
                    'content' => $msg['content'] ?? $msg['message'] ?? '',
                ];
            }
        }

        $respuesta_completa = '';

        $resultado = $engine->send_message_stream(
            $mensajes_formateados,
            $system_prompt,
            function($fragmento) use (&$respuesta_completa) {
                $respuesta_completa .= $fragmento;
                self::enviar_evento_sse('token', ['token' => $fragmento]);

                // Flush inmediato
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
        );

        if ($resultado['success']) {
            // Guardar respuesta completa en la sesión
            $sesion->add_message('assistant', $respuesta_completa);
        } else {
            self::enviar_error_sse($resultado['error'] ?? 'Error en streaming');
        }

        self::enviar_evento_sse('done', [
            'full_response' => $respuesta_completa,
            'cart_updated' => $resultado['cart_updated'] ?? false,
        ]);
    }

    /**
     * Envía un evento SSE
     */
    private static function enviar_evento_sse($tipo_evento, $datos = []) {
        echo "event: {$tipo_evento}\n";
        echo "data: " . json_encode($datos) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Envía un error como evento SSE
     */
    private static function enviar_error_sse($mensaje_error) {
        // Si los headers SSE ya fueron enviados
        if (headers_sent()) {
            self::enviar_evento_sse('error', ['error' => $mensaje_error]);
            self::enviar_evento_sse('done', []);
        } else {
            // Headers SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');

            while (ob_get_level()) {
                ob_end_clean();
            }

            self::enviar_evento_sse('error', ['error' => $mensaje_error]);
            self::enviar_evento_sse('done', []);
        }
        exit;
    }

    /**
     * Obtiene IP del cliente
     */
    private static function obtener_ip_cliente() {
        $claves_ip = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($claves_ip as $clave) {
            if (!empty($_SERVER[$clave])) {
                $ip = explode(',', $_SERVER[$clave])[0];
                if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                    return trim($ip);
                }
            }
        }
        return '0.0.0.0';
    }
}
