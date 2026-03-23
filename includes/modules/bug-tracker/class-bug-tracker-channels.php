<?php
/**
 * Gestor de canales de notificación para Bug Tracker
 *
 * @package Flavor_Chat_IA
 * @subpackage Bug_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona los canales de notificación
 *
 * Soporta Slack, Discord, Email y Webhooks genéricos.
 */
class Flavor_Bug_Tracker_Channels {

    /**
     * Instancia del módulo principal
     *
     * @var Flavor_Bug_Tracker_Module
     */
    private $modulo;

    /**
     * Tabla de canales
     *
     * @var string
     */
    private $tabla_channels;

    /**
     * Colores por severidad
     *
     * @var array
     */
    private $colores_severidad = [
        'critical' => '#dc2626',
        'high' => '#ea580c',
        'medium' => '#ca8a04',
        'low' => '#2563eb',
        'info' => '#6b7280',
    ];

    /**
     * Emojis por severidad
     *
     * @var array
     */
    private $emojis_severidad = [
        'critical' => '🔴',
        'high' => '🟠',
        'medium' => '🟡',
        'low' => '🔵',
        'info' => '⚪',
    ];

    /**
     * Emojis por tipo
     *
     * @var array
     */
    private $emojis_tipo = [
        'error_php' => '💥',
        'exception' => '⚠️',
        'warning' => '⚡',
        'notice' => '📝',
        'manual' => '📋',
        'crash' => '💀',
        'deprecation' => '🕰️',
    ];

    /**
     * Constructor
     *
     * @param Flavor_Bug_Tracker_Module $modulo Instancia del módulo
     */
    public function __construct(Flavor_Bug_Tracker_Module $modulo) {
        $this->modulo = $modulo;
        $this->tabla_channels = $modulo->get_tabla_channels();
    }

    /**
     * Notifica un bug a todos los canales configurados
     *
     * @param object $bug Datos del bug
     * @return array Resultados de envío por canal
     */
    public function notificar_bug($bug) {
        $canales = $this->obtener_canales_activos();
        $resultados = [];

        foreach ($canales as $canal) {
            // Verificar si el canal debe recibir este bug
            if (!$this->canal_debe_notificar($canal, $bug)) {
                continue;
            }

            // Verificar límite de notificaciones
            if (!$this->verificar_limite_notificaciones($canal)) {
                continue;
            }

            // Enviar según tipo de canal
            $resultado = false;
            switch ($canal->tipo) {
                case 'slack':
                    $resultado = $this->enviar_slack($canal, $bug);
                    break;
                case 'discord':
                    $resultado = $this->enviar_discord($canal, $bug);
                    break;
                case 'email':
                    $resultado = $this->enviar_email($canal, $bug);
                    break;
                case 'webhook':
                    $resultado = $this->enviar_webhook($canal, $bug);
                    break;
            }

            // Actualizar estadísticas del canal
            $this->actualizar_estadisticas_canal($canal->id, $resultado);

            $resultados[$canal->id] = [
                'nombre' => $canal->nombre,
                'tipo' => $canal->tipo,
                'exito' => $resultado,
            ];

            // Disparar acción
            do_action('flavor_bug_notification_sent', $canal, $bug, $resultado);
        }

        return $resultados;
    }

    /**
     * Envía notificación a Slack
     *
     * @param object $canal Configuración del canal
     * @param object $bug Datos del bug
     * @return bool
     */
    public function enviar_slack($canal, $bug) {
        if (empty($canal->webhook_url)) {
            return false;
        }

        $color = $this->colores_severidad[$bug->severidad] ?? '#6b7280';
        $emoji_severidad = $this->emojis_severidad[$bug->severidad] ?? '⚪';
        $emoji_tipo = $this->emojis_tipo[$bug->tipo] ?? '🐛';

        $campos = [
            [
                'title' => 'Código',
                'value' => $bug->codigo,
                'short' => true,
            ],
            [
                'title' => 'Severidad',
                'value' => ucfirst($bug->severidad),
                'short' => true,
            ],
            [
                'title' => 'Tipo',
                'value' => str_replace('_', ' ', ucfirst($bug->tipo)),
                'short' => true,
            ],
            [
                'title' => 'Ocurrencias',
                'value' => (string) $bug->ocurrencias,
                'short' => true,
            ],
        ];

        if ($bug->archivo) {
            $campos[] = [
                'title' => 'Ubicación',
                'value' => basename($bug->archivo) . ':' . $bug->linea,
                'short' => true,
            ];
        }

        if ($bug->modulo_id) {
            $campos[] = [
                'title' => 'Módulo',
                'value' => $bug->modulo_id,
                'short' => true,
            ];
        }

        $payload = [
            'username' => 'Flavor Bug Tracker',
            'icon_emoji' => ':bug:',
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "{$emoji_severidad} {$emoji_tipo} {$bug->titulo}",
                    'text' => mb_substr($bug->mensaje, 0, 500),
                    'fields' => $campos,
                    'footer' => get_bloginfo('name') . ' | Flavor Bug Tracker',
                    'ts' => strtotime($bug->created_at),
                ],
            ],
        ];

        // Añadir botón de acción si tenemos URL del admin
        $admin_url = admin_url('admin.php?page=flavor-bug-tracker&bug_id=' . $bug->id);
        $payload['attachments'][0]['actions'] = [
            [
                'type' => 'button',
                'text' => 'Ver en Admin',
                'url' => $admin_url,
            ],
        ];

        return $this->hacer_peticion_webhook($canal->webhook_url, $payload);
    }

    /**
     * Envía notificación a Discord
     *
     * @param object $canal Configuración del canal
     * @param object $bug Datos del bug
     * @return bool
     */
    public function enviar_discord($canal, $bug) {
        if (empty($canal->webhook_url)) {
            return false;
        }

        $color = hexdec(ltrim($this->colores_severidad[$bug->severidad] ?? '#6b7280', '#'));
        $emoji_severidad = $this->emojis_severidad[$bug->severidad] ?? '⚪';
        $emoji_tipo = $this->emojis_tipo[$bug->tipo] ?? '🐛';

        $campos = [
            [
                'name' => 'Código',
                'value' => "`{$bug->codigo}`",
                'inline' => true,
            ],
            [
                'name' => 'Severidad',
                'value' => $emoji_severidad . ' ' . ucfirst($bug->severidad),
                'inline' => true,
            ],
            [
                'name' => 'Tipo',
                'value' => $emoji_tipo . ' ' . str_replace('_', ' ', ucfirst($bug->tipo)),
                'inline' => true,
            ],
            [
                'name' => 'Ocurrencias',
                'value' => (string) $bug->ocurrencias,
                'inline' => true,
            ],
        ];

        if ($bug->archivo) {
            $campos[] = [
                'name' => 'Archivo',
                'value' => '`' . basename($bug->archivo) . ':' . $bug->linea . '`',
                'inline' => true,
            ];
        }

        if ($bug->modulo_id) {
            $campos[] = [
                'name' => 'Módulo',
                'value' => $bug->modulo_id,
                'inline' => true,
            ];
        }

        $payload = [
            'username' => 'Flavor Bug Tracker',
            'avatar_url' => 'https://cdn.jsdelivr.net/npm/@mdi/svg@7.4.47/svg/bug.svg',
            'embeds' => [
                [
                    'title' => $bug->titulo,
                    'description' => mb_substr($bug->mensaje, 0, 500),
                    'color' => $color,
                    'fields' => $campos,
                    'footer' => [
                        'text' => get_bloginfo('name') . ' | Flavor Bug Tracker',
                    ],
                    'timestamp' => gmdate('c', strtotime($bug->created_at)),
                ],
            ],
        ];

        return $this->hacer_peticion_webhook($canal->webhook_url, $payload);
    }

    /**
     * Envía notificación por email
     *
     * @param object $canal Configuración del canal
     * @param object $bug Datos del bug
     * @return bool
     */
    public function enviar_email($canal, $bug) {
        $destinatarios = $canal->email_destinatarios;
        if (empty($destinatarios)) {
            return false;
        }

        // Convertir string a array si es necesario
        if (is_string($destinatarios)) {
            $destinatarios = array_map('trim', explode(',', $destinatarios));
        }

        $emoji_severidad = $this->emojis_severidad[$bug->severidad] ?? '';
        $asunto = sprintf(
            '[%s] %s Bug %s: %s',
            get_bloginfo('name'),
            $emoji_severidad,
            $bug->codigo,
            mb_substr($bug->titulo, 0, 50)
        );

        // Construir mensaje HTML
        $mensaje = $this->construir_email_html($bug);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        // Marcar como alta prioridad si es crítico
        if ($bug->severidad === 'critical') {
            $headers[] = 'X-Priority: 1';
            $headers[] = 'X-MSMail-Priority: High';
            $headers[] = 'Importance: High';
        }

        return wp_mail($destinatarios, $asunto, $mensaje, $headers);
    }

    /**
     * Envía notificación a webhook genérico
     *
     * @param object $canal Configuración del canal
     * @param object $bug Datos del bug
     * @return bool
     */
    public function enviar_webhook($canal, $bug) {
        if (empty($canal->webhook_url)) {
            return false;
        }

        $payload = [
            'event' => 'bug_reported',
            'timestamp' => current_time('c'),
            'site' => [
                'name' => get_bloginfo('name'),
                'url' => home_url(),
            ],
            'bug' => [
                'id' => $bug->id,
                'codigo' => $bug->codigo,
                'tipo' => $bug->tipo,
                'severidad' => $bug->severidad,
                'titulo' => $bug->titulo,
                'mensaje' => $bug->mensaje,
                'archivo' => $bug->archivo,
                'linea' => $bug->linea,
                'modulo_id' => $bug->modulo_id,
                'ocurrencias' => $bug->ocurrencias,
                'estado' => $bug->estado,
                'created_at' => $bug->created_at,
            ],
        ];

        return $this->hacer_peticion_webhook($canal->webhook_url, $payload);
    }

    /**
     * Realiza una petición HTTP a un webhook
     *
     * @param string $url URL del webhook
     * @param array  $payload Datos a enviar
     * @return bool
     */
    private function hacer_peticion_webhook($url, $payload) {
        $response = wp_remote_post($url, [
            'method' => 'POST',
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            error_log('Flavor Bug Tracker - Error webhook: ' . $response->get_error_message());
            return false;
        }

        $codigo_respuesta = wp_remote_retrieve_response_code($response);
        return $codigo_respuesta >= 200 && $codigo_respuesta < 300;
    }

    /**
     * Construye el email HTML
     *
     * @param object $bug Datos del bug
     * @return string
     */
    private function construir_email_html($bug) {
        $color = $this->colores_severidad[$bug->severidad] ?? '#6b7280';
        $emoji_severidad = $this->emojis_severidad[$bug->severidad] ?? '';
        $admin_url = admin_url('admin.php?page=flavor-bug-tracker&bug_id=' . $bug->id);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="border-left: 4px solid <?php echo esc_attr($color); ?>; padding-left: 20px; margin-bottom: 20px;">
                <h2 style="margin: 0 0 10px 0; color: <?php echo esc_attr($color); ?>;">
                    <?php echo esc_html($emoji_severidad . ' ' . $bug->titulo); ?>
                </h2>
                <p style="color: #666; margin: 0;">
                    <strong>Código:</strong> <?php echo esc_html($bug->codigo); ?>
                </p>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee; width: 120px;"><strong>Severidad:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                        <span style="background: <?php echo esc_attr($color); ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                            <?php echo esc_html(ucfirst($bug->severidad)); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Tipo:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo esc_html(str_replace('_', ' ', ucfirst($bug->tipo))); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Ocurrencias:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo esc_html($bug->ocurrencias); ?></td>
                </tr>
                <?php if ($bug->archivo) : ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Archivo:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                        <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-size: 13px;">
                            <?php echo esc_html(basename($bug->archivo) . ':' . $bug->linea); ?>
                        </code>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($bug->modulo_id) : ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Módulo:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo esc_html($bug->modulo_id); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Fecha:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo esc_html($bug->created_at); ?></td>
                </tr>
            </table>

            <?php if ($bug->mensaje) : ?>
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Mensaje:</h3>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 13px; white-space: pre-wrap; overflow-x: auto;">
<?php echo esc_html($bug->mensaje); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($bug->stack_trace) : ?>
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Stack Trace:</h3>
                <div style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px; white-space: pre-wrap; overflow-x: auto; max-height: 300px;">
<?php echo esc_html($bug->stack_trace); ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 30px;">
                <a href="<?php echo esc_url($admin_url); ?>" style="display: inline-block; background: #2563eb; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 500;">
                    Ver en Panel de Administración
                </a>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 12px; text-align: center;">
                Este email fue enviado automáticamente por Flavor Bug Tracker desde <?php echo esc_html(get_bloginfo('name')); ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene todos los canales activos
     *
     * @return array
     */
    public function obtener_canales_activos() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->tabla_channels} WHERE activo = 1"
        );
    }

    /**
     * Obtiene todos los canales
     *
     * @return array
     */
    public function obtener_canales() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->tabla_channels} ORDER BY nombre ASC"
        );
    }

    /**
     * Obtiene un canal por ID
     *
     * @param int $canal_id ID del canal
     * @return object|null
     */
    public function obtener_canal($canal_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_channels} WHERE id = %d",
            $canal_id
        ));
    }

    /**
     * Crea un nuevo canal
     *
     * @param array $datos Datos del canal
     * @return int|false ID del canal creado o false si falla
     */
    public function crear_canal($datos) {
        global $wpdb;

        $resultado = $wpdb->insert($this->tabla_channels, [
            'nombre' => sanitize_text_field($datos['nombre']),
            'tipo' => sanitize_text_field($datos['tipo']),
            'webhook_url' => isset($datos['webhook_url']) ? esc_url_raw($datos['webhook_url']) : null,
            'email_destinatarios' => isset($datos['email_destinatarios']) ? sanitize_textarea_field($datos['email_destinatarios']) : null,
            'severidad_minima' => sanitize_text_field($datos['severidad_minima'] ?? 'high'),
            'tipos_incluidos' => isset($datos['tipos_incluidos']) ? wp_json_encode($datos['tipos_incluidos']) : null,
            'modulos_incluidos' => isset($datos['modulos_incluidos']) ? wp_json_encode($datos['modulos_incluidos']) : null,
            'activo' => isset($datos['activo']) ? (int) $datos['activo'] : 1,
            'metadata' => isset($datos['metadata']) ? wp_json_encode($datos['metadata']) : null,
        ]);

        return $resultado ? $wpdb->insert_id : false;
    }

    /**
     * Actualiza un canal
     *
     * @param int   $canal_id ID del canal
     * @param array $datos Datos a actualizar
     * @return bool
     */
    public function actualizar_canal($canal_id, $datos) {
        global $wpdb;

        $datos_actualizar = [];

        if (isset($datos['nombre'])) {
            $datos_actualizar['nombre'] = sanitize_text_field($datos['nombre']);
        }
        if (isset($datos['webhook_url'])) {
            $datos_actualizar['webhook_url'] = esc_url_raw($datos['webhook_url']);
        }
        if (isset($datos['email_destinatarios'])) {
            $datos_actualizar['email_destinatarios'] = sanitize_textarea_field($datos['email_destinatarios']);
        }
        if (isset($datos['severidad_minima'])) {
            $datos_actualizar['severidad_minima'] = sanitize_text_field($datos['severidad_minima']);
        }
        if (isset($datos['tipos_incluidos'])) {
            $datos_actualizar['tipos_incluidos'] = wp_json_encode($datos['tipos_incluidos']);
        }
        if (isset($datos['modulos_incluidos'])) {
            $datos_actualizar['modulos_incluidos'] = wp_json_encode($datos['modulos_incluidos']);
        }
        if (isset($datos['activo'])) {
            $datos_actualizar['activo'] = (int) $datos['activo'];
        }

        if (empty($datos_actualizar)) {
            return false;
        }

        return $wpdb->update($this->tabla_channels, $datos_actualizar, ['id' => $canal_id]) !== false;
    }

    /**
     * Elimina un canal
     *
     * @param int $canal_id ID del canal
     * @return bool
     */
    public function eliminar_canal($canal_id) {
        global $wpdb;

        return $wpdb->delete($this->tabla_channels, ['id' => $canal_id]) !== false;
    }

    /**
     * Verifica si un canal debe notificar un bug específico
     *
     * @param object $canal Configuración del canal
     * @param object $bug Datos del bug
     * @return bool
     */
    private function canal_debe_notificar($canal, $bug) {
        // Verificar severidad mínima
        $orden_severidad = ['info' => 1, 'low' => 2, 'medium' => 3, 'high' => 4, 'critical' => 5];
        $severidad_bug = $orden_severidad[$bug->severidad] ?? 3;
        $severidad_minima = $orden_severidad[$canal->severidad_minima] ?? 4;

        if ($severidad_bug < $severidad_minima) {
            return false;
        }

        // Verificar tipos incluidos
        if (!empty($canal->tipos_incluidos)) {
            $tipos = json_decode($canal->tipos_incluidos, true);
            if (is_array($tipos) && !empty($tipos) && !in_array($bug->tipo, $tipos)) {
                return false;
            }
        }

        // Verificar módulos incluidos
        if (!empty($canal->modulos_incluidos) && !empty($bug->modulo_id)) {
            $modulos = json_decode($canal->modulos_incluidos, true);
            if (is_array($modulos) && !empty($modulos) && !in_array($bug->modulo_id, $modulos)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica el límite de notificaciones por hora
     *
     * @param object $canal Configuración del canal
     * @return bool
     */
    private function verificar_limite_notificaciones($canal) {
        $limite = $this->modulo->get_setting('limite_notificaciones_hora');
        if ($limite <= 0) {
            return true;
        }

        $clave_transient = 'flavor_bug_notif_' . $canal->id . '_' . gmdate('YmdH');
        $enviados = (int) get_transient($clave_transient);

        if ($enviados >= $limite) {
            return false;
        }

        set_transient($clave_transient, $enviados + 1, HOUR_IN_SECONDS);
        return true;
    }

    /**
     * Actualiza las estadísticas de un canal
     *
     * @param int  $canal_id ID del canal
     * @param bool $exito Si el envío fue exitoso
     * @return void
     */
    private function actualizar_estadisticas_canal($canal_id, $exito) {
        global $wpdb;

        if ($exito) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_channels} SET envios_exitosos = envios_exitosos + 1, ultimo_envio = %s WHERE id = %d",
                current_time('mysql'),
                $canal_id
            ));
        } else {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_channels} SET envios_fallidos = envios_fallidos + 1 WHERE id = %d",
                $canal_id
            ));
        }
    }

    /**
     * Prueba un canal enviando un mensaje de prueba
     *
     * @param int $canal_id ID del canal
     * @return bool
     */
    public function probar_canal($canal_id) {
        $canal = $this->obtener_canal($canal_id);
        if (!$canal) {
            return false;
        }

        // Crear un bug de prueba
        $bug_prueba = (object) [
            'id' => 0,
            'codigo' => 'BUG-TEST-00000',
            'tipo' => 'manual',
            'severidad' => 'info',
            'titulo' => '🧪 Mensaje de prueba de Bug Tracker',
            'mensaje' => 'Este es un mensaje de prueba para verificar la configuración del canal de notificaciones.',
            'archivo' => null,
            'linea' => null,
            'modulo_id' => 'bug-tracker',
            'ocurrencias' => 1,
            'estado' => 'nuevo',
            'created_at' => current_time('mysql'),
        ];

        switch ($canal->tipo) {
            case 'slack':
                return $this->enviar_slack($canal, $bug_prueba);
            case 'discord':
                return $this->enviar_discord($canal, $bug_prueba);
            case 'email':
                return $this->enviar_email($canal, $bug_prueba);
            case 'webhook':
                return $this->enviar_webhook($canal, $bug_prueba);
            default:
                return false;
        }
    }
}
