<?php
/**
 * Canal de Notificaciones Telegram Bot API
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para enviar notificaciones por Telegram Bot API
 */
class Flavor_GC_Telegram_Channel {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * URL base de la API
     */
    const API_URL = 'https://api.telegram.org/bot';

    /**
     * Token del bot
     */
    private $bot_token = '';

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->cargar_configuracion();
        $this->init();
    }

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Cargar configuracion
     */
    private function cargar_configuracion() {
        $config = get_option('flavor_gc_settings', []);
        $this->bot_token = $config['telegram_bot_token'] ?? '';
    }

    /**
     * Inicialización
     */
    private function init() {
        // Registrar endpoint webhook
        add_action('rest_api_init', [$this, 'registrar_webhook_endpoint']);

        // Procesar comandos de bot
        add_action('gc_telegram_comando', [$this, 'procesar_comando'], 10, 2);
    }

    /**
     * Verificar si está configurado
     */
    public function esta_configurado() {
        return !empty($this->bot_token);
    }

    /**
     * Enviar notificación a múltiples destinatarios
     *
     * @param string $evento ID del evento
     * @param array $destinatarios User IDs
     * @param array $datos Datos del mensaje
     * @return array Resultados
     */
    public function enviar($evento, $destinatarios, $datos) {
        if (!$this->esta_configurado()) {
            return ['error' => __('Telegram no está configurado', 'flavor-platform')];
        }

        $resultados = [
            'enviados' => 0,
            'fallidos' => 0,
            'errores' => [],
        ];

        $mensaje = $this->formatear_mensaje($evento, $datos);

        foreach ($destinatarios as $destinatario) {
            $chat_id = $this->obtener_chat_id($destinatario);

            if (!$chat_id) {
                $resultados['fallidos']++;
                $resultados['errores'][] = "Sin chat_id para usuario {$destinatario}";
                continue;
            }

            $resultado = $this->enviar_mensaje($chat_id, $mensaje, $datos);

            if ($resultado['success']) {
                $resultados['enviados']++;
            } else {
                $resultados['fallidos']++;
                $resultados['errores'][] = $resultado['error'];
            }
        }

        return $resultados;
    }

    /**
     * Formatear mensaje según evento
     */
    private function formatear_mensaje($evento, $datos) {
        $sitio_nombre = get_bloginfo('name');

        $templates = [
            'gc_nuevo_ciclo' => "*{$sitio_nombre}*\n\n" .
                "Nuevo ciclo de pedidos abierto\n\n" .
                "Ciclo: {ciclo_nombre}\n" .
                "Cierre: {fecha_cierre}\n\n" .
                "{enlace}",

            'gc_cierre_pedidos' => "*{$sitio_nombre}*\n\n" .
                "El ciclo cierra mañana\n\n" .
                "Ciclo: {ciclo_nombre}\n" .
                "No olvides completar tu pedido.\n\n" .
                "{enlace}",

            'gc_pedido_confirmado' => "*{$sitio_nombre}*\n\n" .
                "Pedido confirmado\n\n" .
                "Pedido: #{pedido_id}\n" .
                "Total: {total}€\n\n" .
                "{enlace}",

            'gc_entrega_lista' => "*{$sitio_nombre}*\n\n" .
                "Tu entrega está lista\n\n" .
                "Ciclo: {ciclo_nombre}\n" .
                "Tu pedido está preparado para recoger.",

            'gc_recordatorio_suscripcion' => "*{$sitio_nombre}*\n\n" .
                "Recordatorio de suscripción\n\n" .
                "Cesta: {cesta_nombre}\n" .
                "Renovación: {fecha_cargo}\n" .
                "Importe: {importe}€",

            'gc_suscripcion_renovada' => "*{$sitio_nombre}*\n\n" .
                "Suscripción renovada\n\n" .
                "Cesta: {cesta_nombre}\n" .
                "Importe: {importe}€",

            'gc_nuevo_producto' => "*{$sitio_nombre}*\n\n" .
                "Nuevo producto disponible\n\n" .
                "Producto: {producto_nombre}\n\n" .
                "{enlace}",

            'gc_consolidado_listo' => "*{$sitio_nombre}*\n\n" .
                "Consolidado disponible\n\n" .
                "Ciclo: {ciclo_nombre}\n" .
                "El consolidado de pedidos está listo.",
        ];

        $template = $templates[$evento] ?? "*{$sitio_nombre}*\n\n{mensaje}";

        // Reemplazar placeholders
        foreach ($datos as $key => $valor) {
            if (is_scalar($valor)) {
                $template = str_replace('{' . $key . '}', $valor, $template);
            }
        }

        // Limpiar placeholders no reemplazados
        $template = preg_replace('/\{[a-z_]+\}/', '', $template);

        return $template;
    }

    /**
     * Enviar mensaje de texto
     */
    public function enviar_mensaje($chat_id, $texto, $datos = []) {
        $params = [
            'chat_id' => $chat_id,
            'text' => $texto,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => false,
        ];

        // Agregar botones inline si hay enlace
        if (!empty($datos['enlace'])) {
            $params['reply_markup'] = json_encode([
                'inline_keyboard' => [[
                    [
                        'text' => $datos['enlace_texto'] ?? 'Ver más',
                        'url' => $datos['enlace'],
                    ],
                ]],
            ]);
        }

        return $this->hacer_peticion('sendMessage', $params);
    }

    /**
     * Enviar mensaje con teclado personalizado
     */
    public function enviar_con_teclado($chat_id, $texto, $botones) {
        $keyboard = [];
        foreach ($botones as $fila) {
            $keyboard_row = [];
            foreach ($fila as $boton) {
                $keyboard_row[] = ['text' => $boton];
            }
            $keyboard[] = $keyboard_row;
        }

        $params = [
            'chat_id' => $chat_id,
            'text' => $texto,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]),
        ];

        return $this->hacer_peticion('sendMessage', $params);
    }

    /**
     * Enviar mensaje con botones inline
     */
    public function enviar_con_botones_inline($chat_id, $texto, $botones) {
        $inline_keyboard = [];
        foreach ($botones as $fila) {
            $keyboard_row = [];
            foreach ($fila as $boton) {
                $btn = ['text' => $boton['texto']];
                if (!empty($boton['url'])) {
                    $btn['url'] = $boton['url'];
                } elseif (!empty($boton['callback'])) {
                    $btn['callback_data'] = $boton['callback'];
                }
                $keyboard_row[] = $btn;
            }
            $inline_keyboard[] = $keyboard_row;
        }

        $params = [
            'chat_id' => $chat_id,
            'text' => $texto,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => $inline_keyboard,
            ]),
        ];

        return $this->hacer_peticion('sendMessage', $params);
    }

    /**
     * Enviar documento/archivo
     */
    public function enviar_documento($chat_id, $archivo_path, $caption = '') {
        $params = [
            'chat_id' => $chat_id,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];

        // Para archivos locales
        if (file_exists($archivo_path)) {
            $params['document'] = new CURLFile($archivo_path);
            return $this->hacer_peticion_multipart('sendDocument', $params);
        }

        // Para URLs
        $params['document'] = $archivo_path;
        return $this->hacer_peticion('sendDocument', $params);
    }

    /**
     * Enviar foto
     */
    public function enviar_foto($chat_id, $foto_url, $caption = '') {
        $params = [
            'chat_id' => $chat_id,
            'photo' => $foto_url,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];

        return $this->hacer_peticion('sendPhoto', $params);
    }

    /**
     * Realizar petición a la API
     */
    private function hacer_peticion($metodo, $params) {
        $url = self::API_URL . $this->bot_token . '/' . $metodo;

        $response = wp_remote_post($url, [
            'body' => $params,
            'timeout' => 30,
        ]);

        return $this->procesar_respuesta($response);
    }

    /**
     * Petición multipart para archivos
     */
    private function hacer_peticion_multipart($metodo, $params) {
        $url = self::API_URL . $this->bot_token . '/' . $metodo;

        $boundary = wp_generate_uuid4();
        $body = '';

        foreach ($params as $key => $value) {
            if ($value instanceof CURLFile) {
                $filename = basename($value->getFilename());
                $content = file_get_contents($value->getFilename());
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$filename}\"\r\n";
                $body .= "Content-Type: application/octet-stream\r\n\r\n";
                $body .= $content . "\r\n";
            } else {
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
                $body .= $value . "\r\n";
            }
        }
        $body .= "--{$boundary}--\r\n";

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => "multipart/form-data; boundary={$boundary}",
            ],
            'body' => $body,
            'timeout' => 60,
        ]);

        return $this->procesar_respuesta($response);
    }

    /**
     * Procesar respuesta de la API
     */
    private function procesar_respuesta($response) {
        if (is_wp_error($response)) {
            $this->log_error('Error de conexión: ' . $response->get_error_message());
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['ok'])) {
            $error_msg = $body['description'] ?? 'Error desconocido';
            $this->log_error("Error API: {$error_msg}");
            return [
                'success' => false,
                'error' => $error_msg,
                'error_code' => $body['error_code'] ?? 0,
            ];
        }

        return [
            'success' => true,
            'result' => $body['result'],
        ];
    }

    /**
     * Obtener chat_id de un usuario
     */
    private function obtener_chat_id($destinatario) {
        // Si ya es un chat_id
        if (!is_numeric($destinatario) || $destinatario > 1000000) {
            return $destinatario;
        }

        // Es un user_id, buscar chat_id
        return get_user_meta($destinatario, 'telegram_chat_id', true);
    }

    /**
     * Configurar webhook
     */
    public function configurar_webhook($url = null) {
        if (!$url) {
            $url = rest_url('flavor-chat-ia/v1/gc/telegram/webhook');
        }

        $params = [
            'url' => $url,
            'allowed_updates' => json_encode(['message', 'callback_query']),
        ];

        return $this->hacer_peticion('setWebhook', $params);
    }

    /**
     * Eliminar webhook
     */
    public function eliminar_webhook() {
        return $this->hacer_peticion('deleteWebhook', []);
    }

    /**
     * Obtener info del webhook
     */
    public function obtener_info_webhook() {
        return $this->hacer_peticion('getWebhookInfo', []);
    }

    /**
     * Obtener info del bot
     */
    public function obtener_info_bot() {
        return $this->hacer_peticion('getMe', []);
    }

    /**
     * Registrar endpoint webhook
     */
    public function registrar_webhook_endpoint() {
        register_rest_route('flavor-chat-ia/v1', '/gc/telegram/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'procesar_webhook'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Procesar webhook entrante
     */
    public function procesar_webhook($request) {
        $update = $request->get_json_params();

        if (empty($update)) {
            return new WP_REST_Response(['ok' => false], 400);
        }

        // Procesar mensaje
        if (!empty($update['message'])) {
            $this->procesar_mensaje($update['message']);
        }

        // Procesar callback de botón inline
        if (!empty($update['callback_query'])) {
            $this->procesar_callback($update['callback_query']);
        }

        return new WP_REST_Response(['ok' => true], 200);
    }

    /**
     * Procesar mensaje entrante
     */
    private function procesar_mensaje($message) {
        $chat_id = $message['chat']['id'];
        $texto = $message['text'] ?? '';
        $usuario_telegram = $message['from'];

        // Verificar si es comando
        if (strpos($texto, '/') === 0) {
            $partes = explode(' ', $texto, 2);
            $comando = str_replace('@' . $this->obtener_username_bot(), '', $partes[0]);
            $argumentos = $partes[1] ?? '';

            do_action('gc_telegram_comando', $comando, [
                'chat_id' => $chat_id,
                'argumentos' => $argumentos,
                'usuario' => $usuario_telegram,
                'mensaje' => $message,
            ]);

            return;
        }

        // Disparar acción para mensaje normal
        do_action('gc_telegram_mensaje', [
            'chat_id' => $chat_id,
            'texto' => $texto,
            'usuario' => $usuario_telegram,
            'mensaje' => $message,
        ]);
    }

    /**
     * Procesar callback de botón inline
     */
    private function procesar_callback($callback_query) {
        $callback_id = $callback_query['id'];
        $chat_id = $callback_query['message']['chat']['id'];
        $data = $callback_query['data'];
        $usuario_telegram = $callback_query['from'];

        // Responder callback para quitar loading
        $this->hacer_peticion('answerCallbackQuery', [
            'callback_query_id' => $callback_id,
        ]);

        do_action('gc_telegram_callback', [
            'chat_id' => $chat_id,
            'data' => $data,
            'usuario' => $usuario_telegram,
            'callback_query' => $callback_query,
        ]);
    }

    /**
     * Procesar comandos del bot
     */
    public function procesar_comando($comando, $contexto) {
        $chat_id = $contexto['chat_id'];

        switch ($comando) {
            case '/start':
                $this->comando_start($chat_id, $contexto);
                break;

            case '/vincular':
                $this->comando_vincular($chat_id, $contexto);
                break;

            case '/pedidos':
                $this->comando_pedidos($chat_id, $contexto);
                break;

            case '/ciclo':
                $this->comando_ciclo_actual($chat_id, $contexto);
                break;

            case '/ayuda':
            case '/help':
                $this->comando_ayuda($chat_id, $contexto);
                break;

            default:
                $this->enviar_mensaje($chat_id, "Comando no reconocido. Usa /ayuda para ver los comandos disponibles.");
        }
    }

    /**
     * Comando /start
     */
    private function comando_start($chat_id, $contexto) {
        $sitio_nombre = get_bloginfo('name');
        $mensaje = "*Bienvenido al bot de {$sitio_nombre}*\n\n";
        $mensaje .= "Desde aquí recibirás notificaciones sobre:\n";
        $mensaje .= "- Nuevos ciclos de pedidos\n";
        $mensaje .= "- Entregas preparadas\n";
        $mensaje .= "- Recordatorios importantes\n\n";
        $mensaje .= "Para vincular tu cuenta, usa el comando:\n";
        $mensaje .= "`/vincular TU_EMAIL`";

        $this->enviar_mensaje($chat_id, $mensaje);
    }

    /**
     * Comando /vincular
     */
    private function comando_vincular($chat_id, $contexto) {
        $email = trim($contexto['argumentos']);

        if (empty($email) || !is_email($email)) {
            $this->enviar_mensaje($chat_id, "Por favor proporciona un email válido.\n\nUso: `/vincular tu@email.com`");
            return;
        }

        // Buscar usuario por email
        $usuario = get_user_by('email', $email);

        if (!$usuario) {
            $this->enviar_mensaje($chat_id, "No se encontró ninguna cuenta con ese email.");
            return;
        }

        // Guardar chat_id en meta del usuario
        update_user_meta($usuario->ID, 'telegram_chat_id', $chat_id);
        update_user_meta($usuario->ID, 'telegram_username', $contexto['usuario']['username'] ?? '');

        $this->enviar_mensaje($chat_id, "Cuenta vinculada correctamente.\n\nAhora recibirás notificaciones de grupos de consumo.");
    }

    /**
     * Comando /pedidos
     */
    private function comando_pedidos($chat_id, $contexto) {
        global $wpdb;

        // Buscar usuario por chat_id
        $usuario_id = $this->obtener_usuario_por_chat_id($chat_id);

        if (!$usuario_id) {
            $this->enviar_mensaje($chat_id, "Tu cuenta no está vinculada.\n\nUsa `/vincular tu@email.com` para vincularla.");
            return;
        }

        // Obtener últimos pedidos
        $pedidos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.post_title as ciclo_nombre
             FROM {$wpdb->prefix}flavor_gc_pedidos p
             LEFT JOIN {$wpdb->posts} c ON p.ciclo_id = c.ID
             WHERE p.usuario_id = %d
             ORDER BY p.fecha_pedido DESC
             LIMIT 5",
            $usuario_id
        ));

        if (empty($pedidos)) {
            $this->enviar_mensaje($chat_id, "No tienes pedidos registrados.");
            return;
        }

        $mensaje = "*Tus últimos pedidos:*\n\n";
        foreach ($pedidos as $pedido) {
            $estado_emoji = $this->obtener_emoji_estado($pedido->estado);
            $mensaje .= "{$estado_emoji} *#{$pedido->id}* - {$pedido->ciclo_nombre}\n";
            $mensaje .= "   Total: " . number_format($pedido->total, 2) . "€\n";
            $mensaje .= "   Estado: {$pedido->estado}\n\n";
        }

        $this->enviar_mensaje($chat_id, $mensaje);
    }

    /**
     * Comando /ciclo
     */
    private function comando_ciclo_actual($chat_id, $contexto) {
        // Buscar ciclo activo
        $ciclos = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_gc_estado',
                    'value' => 'abierto',
                ],
            ],
        ]);

        if (empty($ciclos)) {
            $this->enviar_mensaje($chat_id, "No hay ciclos de pedidos abiertos en este momento.");
            return;
        }

        $ciclo = $ciclos[0];
        $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
        $fecha_entrega = get_post_meta($ciclo->ID, '_gc_fecha_entrega', true);

        $mensaje = "*Ciclo actual: {$ciclo->post_title}*\n\n";
        $mensaje .= "Cierre de pedidos: " . date_i18n('j M Y H:i', strtotime($fecha_cierre)) . "\n";
        $mensaje .= "Fecha de entrega: " . date_i18n('j M Y', strtotime($fecha_entrega)) . "\n";

        $this->enviar_con_botones_inline($chat_id, $mensaje, [
            [['texto' => 'Ver productos', 'url' => add_query_arg('ciclo', intval($ciclo->ID), Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'ciclo'))]],
        ]);
    }

    /**
     * Comando /ayuda
     */
    private function comando_ayuda($chat_id, $contexto) {
        $mensaje = "*Comandos disponibles:*\n\n";
        $mensaje .= "/start - Iniciar bot\n";
        $mensaje .= "/vincular - Vincular cuenta con email\n";
        $mensaje .= "/pedidos - Ver mis últimos pedidos\n";
        $mensaje .= "/ciclo - Ver ciclo actual\n";
        $mensaje .= "/ayuda - Mostrar esta ayuda";

        $this->enviar_mensaje($chat_id, $mensaje);
    }

    /**
     * Obtener usuario por chat_id
     */
    private function obtener_usuario_por_chat_id($chat_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = 'telegram_chat_id'
             AND meta_value = %s",
            $chat_id
        ));
    }

    /**
     * Obtener username del bot
     */
    private function obtener_username_bot() {
        $cache_key = 'gc_telegram_bot_username';
        $username = get_transient($cache_key);

        if (!$username) {
            $info = $this->obtener_info_bot();
            if ($info['success']) {
                $username = $info['result']['username'];
                set_transient($cache_key, $username, DAY_IN_SECONDS);
            }
        }

        return $username ?: '';
    }

    /**
     * Obtener emoji según estado del pedido
     */
    private function obtener_emoji_estado($estado) {
        $emojis = [
            'pendiente' => '',
            'confirmado' => '',
            'preparando' => '',
            'listo' => '',
            'entregado' => '',
            'cancelado' => '',
        ];

        return $emojis[$estado] ?? '';
    }

    /**
     * Log de errores
     */
    private function log_error($mensaje) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            flavor_log_debug( $mensaje, 'GC-Telegram' );
        }
    }

    /**
     * Obtener estado del canal
     */
    public function obtener_estado() {
        if (!$this->esta_configurado()) {
            return [
                'configurado' => false,
                'mensaje' => __('Telegram no está configurado', 'flavor-platform'),
            ];
        }

        $info = $this->obtener_info_bot();

        if (!$info['success']) {
            return [
                'configurado' => true,
                'conectado' => false,
                'mensaje' => __('Error de conexión: ', 'flavor-platform') . ($info['error'] ?? 'Desconocido'),
            ];
        }

        $webhook_info = $this->obtener_info_webhook();

        return [
            'configurado' => true,
            'conectado' => true,
            'bot_username' => $info['result']['username'],
            'bot_nombre' => $info['result']['first_name'],
            'webhook_url' => $webhook_info['success'] ? ($webhook_info['result']['url'] ?? 'No configurado') : 'Error',
            'mensaje' => __('Conectado correctamente', 'flavor-platform'),
        ];
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}
