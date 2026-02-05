<?php
/**
 * Canal de Notificaciones WhatsApp Business API
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para enviar notificaciones por WhatsApp Business API
 */
class Flavor_GC_WhatsApp_Channel {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * URL base de la API
     */
    const API_URL = 'https://graph.facebook.com/v18.0/';

    /**
     * Phone Number ID
     */
    private $phone_id = '';

    /**
     * Access Token
     */
    private $token = '';

    /**
     * Templates de mensajes
     */
    private $templates = [];

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->cargar_configuracion();
        $this->registrar_templates();
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
     * Cargar configuracion desde opciones
     */
    private function cargar_configuracion() {
        $config = get_option('flavor_gc_settings', []);
        $this->phone_id = $config['whatsapp_phone_id'] ?? '';
        $this->token = $config['whatsapp_token'] ?? '';
    }

    /**
     * Registrar templates de mensajes
     */
    private function registrar_templates() {
        $this->templates = [
            'gc_nuevo_ciclo' => [
                'nombre' => 'gc_nuevo_ciclo',
                'idioma' => 'es',
                'componentes' => [
                    ['type' => 'body', 'parameters' => ['ciclo_nombre', 'fecha_cierre']],
                ],
            ],
            'gc_cierre_pedidos' => [
                'nombre' => 'gc_cierre_pedidos',
                'idioma' => 'es',
                'componentes' => [
                    ['type' => 'body', 'parameters' => ['ciclo_nombre', 'hora_cierre']],
                ],
            ],
            'gc_pedido_confirmado' => [
                'nombre' => 'gc_pedido_confirmado',
                'idioma' => 'es',
                'componentes' => [
                    ['type' => 'body', 'parameters' => ['pedido_id', 'total']],
                ],
            ],
            'gc_entrega_lista' => [
                'nombre' => 'gc_entrega_lista',
                'idioma' => 'es',
                'componentes' => [
                    ['type' => 'body', 'parameters' => ['ciclo_nombre']],
                ],
            ],
            'gc_recordatorio_suscripcion' => [
                'nombre' => 'gc_recordatorio_suscripcion',
                'idioma' => 'es',
                'componentes' => [
                    ['type' => 'body', 'parameters' => ['cesta_nombre', 'fecha_cargo', 'importe']],
                ],
            ],
            'gc_consolidado_listo' => [
                'nombre' => 'gc_consolidado_listo',
                'idioma' => 'es',
                'componentes' => [
                    ['type' => 'body', 'parameters' => ['ciclo_nombre']],
                ],
            ],
        ];

        // Permitir extensiones
        $this->templates = apply_filters('gc_whatsapp_templates', $this->templates);
    }

    /**
     * Verificar si el canal está configurado
     */
    public function esta_configurado() {
        return !empty($this->phone_id) && !empty($this->token);
    }

    /**
     * Enviar notificación
     *
     * @param string $evento ID del evento
     * @param array $destinatarios User IDs o números de teléfono
     * @param array $datos Datos para el mensaje
     * @return array Resultados
     */
    public function enviar($evento, $destinatarios, $datos) {
        if (!$this->esta_configurado()) {
            return ['error' => 'WhatsApp no está configurado'];
        }

        $resultados = [
            'enviados' => 0,
            'fallidos' => 0,
            'errores' => [],
        ];

        foreach ($destinatarios as $destinatario) {
            $telefono = $this->obtener_telefono($destinatario);

            if (!$telefono) {
                $resultados['fallidos']++;
                $resultados['errores'][] = "Sin teléfono para usuario {$destinatario}";
                continue;
            }

            $resultado = $this->enviar_mensaje($telefono, $evento, $datos);

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
     * Enviar mensaje individual
     *
     * @param string $telefono Número de teléfono
     * @param string $evento ID del evento
     * @param array $datos Datos del mensaje
     * @return array Resultado
     */
    public function enviar_mensaje($telefono, $evento, $datos) {
        // Verificar si hay template registrado
        if (isset($this->templates[$evento])) {
            return $this->enviar_template($telefono, $evento, $datos);
        }

        // Enviar mensaje de texto simple
        return $this->enviar_texto($telefono, $datos['mensaje'] ?? '');
    }

    /**
     * Enviar mensaje con template
     */
    private function enviar_template($telefono, $evento, $datos) {
        $template_config = $this->templates[$evento];
        $telefono_formateado = $this->formatear_telefono($telefono);

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono_formateado,
            'type' => 'template',
            'template' => [
                'name' => $template_config['nombre'],
                'language' => [
                    'code' => $template_config['idioma'],
                ],
            ],
        ];

        // Agregar componentes con parámetros
        if (!empty($template_config['componentes'])) {
            $componentes = [];
            foreach ($template_config['componentes'] as $componente) {
                $parametros = [];
                foreach ($componente['parameters'] as $param_nombre) {
                    $valor = $datos[$param_nombre] ?? '';
                    if (is_numeric($valor) && strpos($param_nombre, 'total') !== false || strpos($param_nombre, 'importe') !== false) {
                        $valor = number_format($valor, 2) . '€';
                    }
                    $parametros[] = [
                        'type' => 'text',
                        'text' => (string) $valor,
                    ];
                }
                $componentes[] = [
                    'type' => $componente['type'],
                    'parameters' => $parametros,
                ];
            }
            $body['template']['components'] = $componentes;
        }

        return $this->hacer_peticion($body);
    }

    /**
     * Enviar mensaje de texto simple
     */
    private function enviar_texto($telefono, $mensaje) {
        if (empty($mensaje)) {
            return ['success' => false, 'error' => 'Mensaje vacío'];
        }

        $telefono_formateado = $this->formatear_telefono($telefono);

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono_formateado,
            'type' => 'text',
            'text' => [
                'body' => $mensaje,
            ],
        ];

        return $this->hacer_peticion($body);
    }

    /**
     * Enviar mensaje interactivo con botones
     */
    public function enviar_con_botones($telefono, $mensaje, $botones) {
        $telefono_formateado = $this->formatear_telefono($telefono);

        $botones_formateados = [];
        foreach (array_slice($botones, 0, 3) as $index => $boton) {
            $botones_formateados[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $boton['id'] ?? "btn_{$index}",
                    'title' => substr($boton['titulo'], 0, 20),
                ],
            ];
        }

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono_formateado,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => [
                    'text' => $mensaje,
                ],
                'action' => [
                    'buttons' => $botones_formateados,
                ],
            ],
        ];

        return $this->hacer_peticion($body);
    }

    /**
     * Enviar mensaje con lista de opciones
     */
    public function enviar_lista($telefono, $mensaje, $boton_texto, $secciones) {
        $telefono_formateado = $this->formatear_telefono($telefono);

        $secciones_formateadas = [];
        foreach ($secciones as $seccion) {
            $rows = [];
            foreach ($seccion['items'] as $item) {
                $rows[] = [
                    'id' => $item['id'],
                    'title' => substr($item['titulo'], 0, 24),
                    'description' => substr($item['descripcion'] ?? '', 0, 72),
                ];
            }
            $secciones_formateadas[] = [
                'title' => substr($seccion['titulo'], 0, 24),
                'rows' => $rows,
            ];
        }

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono_formateado,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => [
                    'text' => $mensaje,
                ],
                'action' => [
                    'button' => substr($boton_texto, 0, 20),
                    'sections' => $secciones_formateadas,
                ],
            ],
        ];

        return $this->hacer_peticion($body);
    }

    /**
     * Realizar petición a la API
     */
    private function hacer_peticion($body) {
        $url = self::API_URL . $this->phone_id . '/messages';

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            $this->log_error('Error de conexión: ' . $response->get_error_message());
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200) {
            $error_msg = $body_response['error']['message'] ?? 'Error desconocido';
            $this->log_error("Error API ({$status_code}): {$error_msg}");
            return [
                'success' => false,
                'error' => $error_msg,
                'code' => $status_code,
            ];
        }

        return [
            'success' => true,
            'message_id' => $body_response['messages'][0]['id'] ?? null,
        ];
    }

    /**
     * Obtener teléfono de un usuario
     */
    private function obtener_telefono($destinatario) {
        // Si ya es un número de teléfono
        if (!is_numeric($destinatario) || strlen($destinatario) > 10) {
            return $destinatario;
        }

        // Es un user_id, buscar teléfono
        $telefono = get_user_meta($destinatario, 'telefono', true);
        if (!$telefono) {
            $telefono = get_user_meta($destinatario, 'billing_phone', true);
        }
        if (!$telefono) {
            $telefono = get_user_meta($destinatario, 'whatsapp_number', true);
        }

        return $telefono;
    }

    /**
     * Formatear número de teléfono para WhatsApp
     */
    private function formatear_telefono($telefono) {
        // Eliminar espacios, guiones, paréntesis
        $telefono = preg_replace('/[\s\-\(\)]/', '', $telefono);

        // Si empieza con +, quitar
        $telefono = ltrim($telefono, '+');

        // Si es número español sin código de país, añadir 34
        if (strlen($telefono) === 9 && $telefono[0] !== '3') {
            $telefono = '34' . $telefono;
        }

        return $telefono;
    }

    /**
     * Verificar webhook de WhatsApp
     */
    public function verificar_webhook($verify_token, $challenge) {
        $config = get_option('flavor_gc_settings', []);
        $token_almacenado = $config['whatsapp_verify_token'] ?? '';

        if ($verify_token === $token_almacenado) {
            return $challenge;
        }

        return false;
    }

    /**
     * Procesar webhook entrante
     */
    public function procesar_webhook($payload) {
        if (empty($payload['entry'])) {
            return ['processed' => false];
        }

        foreach ($payload['entry'] as $entry) {
            if (empty($entry['changes'])) continue;

            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'messages') continue;

                $value = $change['value'];

                // Procesar mensajes entrantes
                if (!empty($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->procesar_mensaje_entrante($message, $value['contacts'][0] ?? []);
                    }
                }

                // Procesar estados de mensaje
                if (!empty($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->procesar_estado_mensaje($status);
                    }
                }
            }
        }

        return ['processed' => true];
    }

    /**
     * Procesar mensaje entrante
     */
    private function procesar_mensaje_entrante($message, $contacto) {
        $telefono = $message['from'];
        $tipo = $message['type'];

        $datos_mensaje = [
            'id' => $message['id'],
            'telefono' => $telefono,
            'tipo' => $tipo,
            'timestamp' => $message['timestamp'],
            'contacto_nombre' => $contacto['profile']['name'] ?? '',
        ];

        switch ($tipo) {
            case 'text':
                $datos_mensaje['texto'] = $message['text']['body'];
                break;

            case 'button':
                $datos_mensaje['boton_id'] = $message['button']['payload'];
                $datos_mensaje['boton_texto'] = $message['button']['text'];
                break;

            case 'interactive':
                if ($message['interactive']['type'] === 'button_reply') {
                    $datos_mensaje['respuesta_id'] = $message['interactive']['button_reply']['id'];
                    $datos_mensaje['respuesta_titulo'] = $message['interactive']['button_reply']['title'];
                } elseif ($message['interactive']['type'] === 'list_reply') {
                    $datos_mensaje['respuesta_id'] = $message['interactive']['list_reply']['id'];
                    $datos_mensaje['respuesta_titulo'] = $message['interactive']['list_reply']['title'];
                }
                break;
        }

        // Disparar acción para que otros componentes procesen
        do_action('gc_whatsapp_mensaje_recibido', $datos_mensaje);

        // Log
        $this->log_mensaje_entrante($datos_mensaje);
    }

    /**
     * Procesar estado de mensaje
     */
    private function procesar_estado_mensaje($status) {
        $datos_estado = [
            'message_id' => $status['id'],
            'status' => $status['status'],
            'timestamp' => $status['timestamp'],
            'recipient_id' => $status['recipient_id'],
        ];

        // sent, delivered, read, failed
        do_action('gc_whatsapp_estado_mensaje', $datos_estado);

        if ($status['status'] === 'failed') {
            $error = $status['errors'][0] ?? [];
            $this->log_error("Mensaje fallido {$status['id']}: " . ($error['message'] ?? 'Error desconocido'));
        }
    }

    /**
     * Log de errores
     */
    private function log_error($mensaje) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[GC WhatsApp] ' . $mensaje);
        }

        // Guardar en tabla de logs si existe
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_notificaciones_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") === $tabla) {
            $wpdb->insert($tabla, [
                'canal' => 'whatsapp',
                'tipo' => 'error',
                'mensaje' => $mensaje,
                'fecha' => current_time('mysql'),
            ]);
        }
    }

    /**
     * Log de mensaje entrante
     */
    private function log_mensaje_entrante($datos) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[GC WhatsApp] Mensaje recibido: ' . wp_json_encode($datos));
        }
    }

    /**
     * Obtener estado del canal
     */
    public function obtener_estado() {
        if (!$this->esta_configurado()) {
            return [
                'configurado' => false,
                'mensaje' => 'WhatsApp no está configurado',
            ];
        }

        // Verificar conexión con la API
        $url = self::API_URL . $this->phone_id;
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return [
                'configurado' => true,
                'conectado' => false,
                'mensaje' => 'Error de conexión: ' . $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200) {
            return [
                'configurado' => true,
                'conectado' => false,
                'mensaje' => 'Error de autenticación',
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return [
            'configurado' => true,
            'conectado' => true,
            'phone_number' => $body['display_phone_number'] ?? '',
            'quality_rating' => $body['quality_rating'] ?? '',
            'mensaje' => 'Conectado correctamente',
        ];
    }

    /**
     * Registrar endpoint para webhook
     */
    public function registrar_webhook_endpoint() {
        register_rest_route('flavor-chat-ia/v1', '/gc/whatsapp/webhook', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'webhook_verificacion'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'webhook_recepcion'],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    /**
     * Callback verificación webhook
     */
    public function webhook_verificacion($request) {
        $mode = $request->get_param('hub_mode');
        $token = $request->get_param('hub_verify_token');
        $challenge = $request->get_param('hub_challenge');

        if ($mode === 'subscribe') {
            $resultado = $this->verificar_webhook($token, $challenge);
            if ($resultado) {
                return new WP_REST_Response($resultado, 200);
            }
        }

        return new WP_REST_Response('Forbidden', 403);
    }

    /**
     * Callback recepción webhook
     */
    public function webhook_recepcion($request) {
        $payload = $request->get_json_params();
        $resultado = $this->procesar_webhook($payload);
        return new WP_REST_Response($resultado, 200);
    }
}
