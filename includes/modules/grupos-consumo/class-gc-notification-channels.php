<?php
/**
 * Gestión de Canales de Notificación para Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los canales de notificación del módulo
 */
class Flavor_GC_Notification_Channels {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Canales registrados
     */
    private $canales = [];

    /**
     * Eventos de notificación del módulo
     */
    const EVENTOS = [
        'gc_nuevo_ciclo' => [
            'nombre' => 'Nuevo ciclo abierto',
            'descripcion' => 'Se envía cuando se abre un nuevo ciclo de pedidos',
            'canales_default' => ['email', 'whatsapp', 'push'],
        ],
        'gc_cierre_pedidos' => [
            'nombre' => 'Cierre de pedidos próximo',
            'descripcion' => 'Recordatorio 24h antes del cierre de pedidos',
            'canales_default' => ['whatsapp', 'push'],
        ],
        'gc_pedido_confirmado' => [
            'nombre' => 'Pedido confirmado',
            'descripcion' => 'Confirmación de pedido realizado',
            'canales_default' => ['email', 'whatsapp'],
        ],
        'gc_pedido_modificado' => [
            'nombre' => 'Pedido modificado',
            'descripcion' => 'Notificación de cambios en el pedido',
            'canales_default' => ['email'],
        ],
        'gc_entrega_lista' => [
            'nombre' => 'Entrega lista',
            'descripcion' => 'Notificación de que la entrega está preparada',
            'canales_default' => ['whatsapp', 'telegram', 'push'],
        ],
        'gc_recordatorio_suscripcion' => [
            'nombre' => 'Recordatorio de suscripción',
            'descripcion' => 'Aviso de próximo cargo de suscripción',
            'canales_default' => ['email', 'whatsapp'],
        ],
        'gc_suscripcion_renovada' => [
            'nombre' => 'Suscripción renovada',
            'descripcion' => 'Confirmación de renovación automática',
            'canales_default' => ['email'],
        ],
        'gc_nuevo_producto' => [
            'nombre' => 'Nuevo producto disponible',
            'descripcion' => 'Aviso de nuevos productos en el catálogo',
            'canales_default' => ['email', 'push'],
        ],
        'gc_consolidado_listo' => [
            'nombre' => 'Consolidado listo',
            'descripcion' => 'Aviso a productores de consolidado disponible',
            'canales_default' => ['email', 'whatsapp'],
        ],
    ];

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
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
     * Inicialización
     */
    private function init() {
        // Registrar eventos en el Notification Manager si existe
        add_action('init', [$this, 'registrar_eventos_notificacion'], 20);

        // Cargar canales disponibles
        add_action('init', [$this, 'cargar_canales'], 15);

        // Hooks para disparar notificaciones
        add_action('gc_ciclo_abierto', [$this, 'notificar_nuevo_ciclo'], 10, 1);
        add_action('gc_pedido_creado', [$this, 'notificar_pedido_confirmado'], 10, 2);
        add_action('gc_pedido_actualizado', [$this, 'notificar_pedido_modificado'], 10, 2);
        add_action('gc_entrega_preparada', [$this, 'notificar_entrega_lista'], 10, 2);
        add_action('gc_suscripcion_procesada', [$this, 'notificar_suscripcion_renovada'], 10, 2);
        add_action('gc_producto_publicado', [$this, 'notificar_nuevo_producto'], 10, 1);
        add_action('gc_consolidado_generado', [$this, 'notificar_consolidado_listo'], 10, 2);

        // Cron para recordatorios
        add_action('gc_enviar_recordatorios', [$this, 'enviar_recordatorios_cierre']);
        add_action('gc_enviar_recordatorios_suscripcion', [$this, 'enviar_recordatorios_suscripcion']);

        // Programar crons si no existen
        if (!wp_next_scheduled('gc_enviar_recordatorios')) {
            wp_schedule_event(time(), 'hourly', 'gc_enviar_recordatorios');
        }
        if (!wp_next_scheduled('gc_enviar_recordatorios_suscripcion')) {
            wp_schedule_event(time(), 'daily', 'gc_enviar_recordatorios_suscripcion');
        }
    }

    /**
     * Cargar canales de notificación disponibles
     */
    public function cargar_canales() {
        $config = get_option('flavor_gc_settings', []);

        // Canal Email (siempre disponible)
        $this->canales['email'] = [
            'nombre' => 'Email',
            'activo' => true,
            'clase' => 'Flavor_GC_Email_Channel',
        ];

        // Canal WhatsApp
        if (!empty($config['whatsapp_enabled'])) {
            require_once __DIR__ . '/class-gc-whatsapp-channel.php';
            $this->canales['whatsapp'] = [
                'nombre' => 'WhatsApp',
                'activo' => true,
                'clase' => 'Flavor_GC_WhatsApp_Channel',
                'instancia' => Flavor_GC_WhatsApp_Channel::get_instance(),
            ];
        }

        // Canal Telegram
        if (!empty($config['telegram_enabled'])) {
            require_once __DIR__ . '/class-gc-telegram-channel.php';
            $this->canales['telegram'] = [
                'nombre' => 'Telegram',
                'activo' => true,
                'clase' => 'Flavor_GC_Telegram_Channel',
                'instancia' => Flavor_GC_Telegram_Channel::get_instance(),
            ];
        }

        // Canal Push (si Firebase está configurado)
        if (class_exists('Flavor_Push_Notifications')) {
            $this->canales['push'] = [
                'nombre' => 'Push',
                'activo' => true,
                'clase' => 'Flavor_Push_Notifications',
            ];
        }

        // Permitir extensión
        $this->canales = apply_filters('gc_notification_channels', $this->canales);
    }

    /**
     * Registrar eventos en el sistema de notificaciones
     */
    public function registrar_eventos_notificacion() {
        if (!has_filter('flavor_notification_events')) {
            return;
        }

        add_filter('flavor_notification_events', function($eventos) {
            foreach (self::EVENTOS as $evento_id => $evento_data) {
                $eventos[$evento_id] = [
                    'nombre' => $evento_data['nombre'],
                    'descripcion' => $evento_data['descripcion'],
                    'modulo' => 'grupos-consumo',
                    'canales' => $evento_data['canales_default'],
                ];
            }
            return $eventos;
        });
    }

    /**
     * Enviar notificación
     *
     * @param string $evento ID del evento
     * @param array $destinatarios Array de user_ids o datos de contacto
     * @param array $datos Datos para la notificación
     * @param array $canales_especificos Canales específicos (opcional)
     * @return array Resultados por canal
     */
    public function enviar($evento, $destinatarios, $datos, $canales_especificos = null) {
        if (!isset(self::EVENTOS[$evento])) {
            return ['error' => __('Evento no registrado', 'flavor-chat-ia')];
        }

        $evento_config = self::EVENTOS[$evento];
        $canales_a_usar = $canales_especificos ?? $evento_config['canales_default'];
        $resultados = [];

        foreach ($canales_a_usar as $canal_id) {
            if (!isset($this->canales[$canal_id]) || !$this->canales[$canal_id]['activo']) {
                continue;
            }

            $resultado = $this->enviar_por_canal($canal_id, $evento, $destinatarios, $datos);
            $resultados[$canal_id] = $resultado;
        }

        // Registrar en tabla de notificaciones
        $this->registrar_notificacion($evento, $destinatarios, $datos, $resultados);

        return $resultados;
    }

    /**
     * Enviar por canal específico
     */
    private function enviar_por_canal($canal_id, $evento, $destinatarios, $datos) {
        $canal = $this->canales[$canal_id];

        switch ($canal_id) {
            case 'email':
                return $this->enviar_email($evento, $destinatarios, $datos);

            case 'whatsapp':
                if (isset($canal['instancia'])) {
                    return $canal['instancia']->enviar($evento, $destinatarios, $datos);
                }
                break;

            case 'telegram':
                if (isset($canal['instancia'])) {
                    return $canal['instancia']->enviar($evento, $destinatarios, $datos);
                }
                break;

            case 'push':
                return $this->enviar_push($evento, $destinatarios, $datos);
        }

        return ['error' => __('Canal no implementado', 'flavor-chat-ia')];
    }

    /**
     * Enviar notificación por email
     */
    private function enviar_email($evento, $destinatarios, $datos) {
        $template = $this->obtener_template_email($evento, $datos);
        $enviados = 0;
        $fallidos = 0;

        foreach ($destinatarios as $destinatario) {
            $email = is_numeric($destinatario)
                ? get_userdata($destinatario)->user_email
                : $destinatario;

            if (!$email) {
                $fallidos++;
                continue;
            }

            $enviado = wp_mail(
                $email,
                $template['asunto'],
                $template['contenido'],
                ['Content-Type: text/html; charset=UTF-8']
            );

            if ($enviado) {
                $enviados++;
            } else {
                $fallidos++;
            }
        }

        return [
            'enviados' => $enviados,
            'fallidos' => $fallidos,
        ];
    }

    /**
     * Enviar notificación push
     */
    private function enviar_push($evento, $destinatarios, $datos) {
        if (!class_exists('Flavor_Push_Notifications')) {
            return ['error' => __('Push notifications no disponibles', 'flavor-chat-ia')];
        }

        $push = Flavor_Push_Notifications::get_instance();
        $template = $this->obtener_template_push($evento, $datos);

        return $push->enviar_a_usuarios($destinatarios, $template['titulo'], $template['cuerpo'], $template['datos']);
    }

    /**
     * Obtener template de email para evento
     */
    private function obtener_template_email($evento, $datos) {
        $sitio_nombre = get_bloginfo('name');

        $templates = [
            'gc_nuevo_ciclo' => [
                'asunto' => "[{$sitio_nombre}] Nuevo ciclo de pedidos abierto",
                'contenido' => $this->render_template('email-nuevo-ciclo', $datos),
            ],
            'gc_cierre_pedidos' => [
                'asunto' => "[{$sitio_nombre}] Cierre de pedidos en 24 horas",
                'contenido' => $this->render_template('email-cierre-pedidos', $datos),
            ],
            'gc_pedido_confirmado' => [
                'asunto' => "[{$sitio_nombre}] Pedido confirmado #{$datos['pedido_id']}",
                'contenido' => $this->render_template('email-pedido-confirmado', $datos),
            ],
            'gc_pedido_modificado' => [
                'asunto' => "[{$sitio_nombre}] Pedido modificado #{$datos['pedido_id']}",
                'contenido' => $this->render_template('email-pedido-modificado', $datos),
            ],
            'gc_entrega_lista' => [
                'asunto' => "[{$sitio_nombre}] Tu entrega está lista",
                'contenido' => $this->render_template('email-entrega-lista', $datos),
            ],
            'gc_recordatorio_suscripcion' => [
                'asunto' => "[{$sitio_nombre}] Recordatorio de tu suscripción",
                'contenido' => $this->render_template('email-recordatorio-suscripcion', $datos),
            ],
            'gc_suscripcion_renovada' => [
                'asunto' => "[{$sitio_nombre}] Suscripción renovada",
                'contenido' => $this->render_template('email-suscripcion-renovada', $datos),
            ],
            'gc_nuevo_producto' => [
                'asunto' => "[{$sitio_nombre}] Nuevo producto disponible",
                'contenido' => $this->render_template('email-nuevo-producto', $datos),
            ],
            'gc_consolidado_listo' => [
                'asunto' => "[{$sitio_nombre}] Consolidado de pedidos disponible",
                'contenido' => $this->render_template('email-consolidado-listo', $datos),
            ],
        ];

        return $templates[$evento] ?? [
            'asunto' => "[{$sitio_nombre}] Notificación",
            'contenido' => '<p>' . esc_html($datos['mensaje'] ?? 'Tienes una nueva notificación.') . '</p>',
        ];
    }

    /**
     * Obtener template push para evento
     */
    private function obtener_template_push($evento, $datos) {
        $templates = [
            'gc_nuevo_ciclo' => [
                'titulo' => 'Nuevo ciclo de pedidos',
                'cuerpo' => 'Se ha abierto un nuevo ciclo. Haz tu pedido antes del cierre.',
                'datos' => ['tipo' => 'ciclo', 'ciclo_id' => $datos['ciclo_id'] ?? 0],
            ],
            'gc_cierre_pedidos' => [
                'titulo' => 'Cierre en 24h',
                'cuerpo' => 'El ciclo de pedidos cierra mañana. No olvides completar tu pedido.',
                'datos' => ['tipo' => 'cierre', 'ciclo_id' => $datos['ciclo_id'] ?? 0],
            ],
            'gc_entrega_lista' => [
                'titulo' => 'Entrega preparada',
                'cuerpo' => 'Tu pedido está listo para recoger.',
                'datos' => ['tipo' => 'entrega', 'pedido_id' => $datos['pedido_id'] ?? 0],
            ],
        ];

        return $templates[$evento] ?? [
            'titulo' => 'Notificación',
            'cuerpo' => $datos['mensaje'] ?? 'Tienes una nueva notificación.',
            'datos' => [],
        ];
    }

    /**
     * Renderizar template de email
     */
    private function render_template($template_nombre, $datos) {
        $template_path = __DIR__ . '/templates/emails/' . $template_nombre . '.php';

        if (!file_exists($template_path)) {
            // Template genérico
            return $this->template_email_generico($datos);
        }

        ob_start();
        extract($datos);
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Template email genérico
     */
    private function template_email_generico($datos) {
        $sitio_nombre = get_bloginfo('name');
        $sitio_url = home_url();

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . esc_html($sitio_nombre) . '</h1>
        </div>
        <div class="content">';

        if (!empty($datos['titulo'])) {
            $html .= '<h2>' . esc_html($datos['titulo']) . '</h2>';
        }

        if (!empty($datos['mensaje'])) {
            $html .= '<p>' . wp_kses_post($datos['mensaje']) . '</p>';
        }

        if (!empty($datos['enlace'])) {
            $html .= '<p><a href="' . esc_url($datos['enlace']) . '" class="btn">' .
                     esc_html($datos['enlace_texto'] ?? 'Ver más') . '</a></p>';
        }

        $html .= '</div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' ' . esc_html($sitio_nombre) . '</p>
            <p><a href="' . esc_url($sitio_url) . '">' . esc_html($sitio_url) . '</a></p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Registrar notificación en base de datos
     */
    private function registrar_notificacion($evento, $destinatarios, $datos, $resultados) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_notificaciones';

        foreach ($destinatarios as $destinatario) {
            $usuario_id = is_numeric($destinatario) ? $destinatario : 0;

            $wpdb->insert($tabla, [
                'usuario_id' => $usuario_id,
                'tipo' => $evento,
                'titulo' => self::EVENTOS[$evento]['nombre'],
                'mensaje' => wp_json_encode($datos),
                'leida' => 0,
                'fecha_creacion' => current_time('mysql'),
            ]);
        }
    }

    /**
     * Notificar nuevo ciclo abierto
     */
    public function notificar_nuevo_ciclo($ciclo_id) {
        $ciclo = get_post($ciclo_id);
        if (!$ciclo) return;

        // Obtener consumidores activos
        $consumidores = $this->obtener_consumidores_activos();

        $datos = [
            'ciclo_id' => $ciclo_id,
            'ciclo_nombre' => $ciclo->post_title,
            'fecha_cierre' => get_post_meta($ciclo_id, '_gc_fecha_cierre', true),
            'fecha_entrega' => get_post_meta($ciclo_id, '_gc_fecha_entrega', true),
            'titulo' => 'Nuevo ciclo de pedidos',
            'mensaje' => sprintf(
                'Se ha abierto un nuevo ciclo de pedidos: %s. Fecha de cierre: %s.',
                $ciclo->post_title,
                date_i18n(get_option('date_format'), strtotime(get_post_meta($ciclo_id, '_gc_fecha_cierre', true)))
            ),
            'enlace' => add_query_arg('ciclo', intval($ciclo_id), Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'ciclo')),
            'enlace_texto' => 'Ver ciclo y hacer pedido',
        ];

        $this->enviar('gc_nuevo_ciclo', $consumidores, $datos);
    }

    /**
     * Notificar pedido confirmado
     */
    public function notificar_pedido_confirmado($pedido_id, $usuario_id) {
        global $wpdb;

        $pedido = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_gc_pedidos WHERE id = %d",
            $pedido_id
        ));

        if (!$pedido) return;

        $datos = [
            'pedido_id' => $pedido_id,
            'total' => $pedido->total,
            'titulo' => 'Pedido confirmado',
            'mensaje' => sprintf(
                'Tu pedido #%d ha sido confirmado. Total: %s',
                $pedido_id,
                number_format($pedido->total, 2) . '€'
            ),
            'enlace' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'mis-pedidos'),
            'enlace_texto' => 'Ver mis pedidos',
        ];

        $this->enviar('gc_pedido_confirmado', [$usuario_id], $datos);
    }

    /**
     * Notificar pedido modificado
     */
    public function notificar_pedido_modificado($pedido_id, $usuario_id) {
        $datos = [
            'pedido_id' => $pedido_id,
            'titulo' => 'Pedido modificado',
            'mensaje' => sprintf('Tu pedido #%d ha sido actualizado.', $pedido_id),
            'enlace' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'mis-pedidos'),
            'enlace_texto' => 'Ver detalles',
        ];

        $this->enviar('gc_pedido_modificado', [$usuario_id], $datos);
    }

    /**
     * Notificar entrega lista
     */
    public function notificar_entrega_lista($entrega_id, $usuario_id) {
        global $wpdb;

        $entrega = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, c.post_title as ciclo_nombre
             FROM {$wpdb->prefix}flavor_gc_entregas e
             LEFT JOIN {$wpdb->posts} c ON e.ciclo_id = c.ID
             WHERE e.id = %d",
            $entrega_id
        ));

        if (!$entrega) return;

        $datos = [
            'entrega_id' => $entrega_id,
            'ciclo_nombre' => $entrega->ciclo_nombre,
            'titulo' => 'Tu entrega está lista',
            'mensaje' => __('Tu pedido está preparado y listo para recoger.', 'flavor-chat-ia'),
            'enlace' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'mis-pedidos'),
            'enlace_texto' => 'Ver detalles de entrega',
        ];

        $this->enviar('gc_entrega_lista', [$usuario_id], $datos);
    }

    /**
     * Notificar suscripción renovada
     */
    public function notificar_suscripcion_renovada($suscripcion_id, $usuario_id) {
        global $wpdb;

        $suscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, c.nombre as cesta_nombre
             FROM {$wpdb->prefix}flavor_gc_suscripciones s
             LEFT JOIN {$wpdb->prefix}flavor_gc_cestas_tipo c ON s.tipo_cesta = c.slug
             WHERE s.id = %d",
            $suscripcion_id
        ));

        if (!$suscripcion) return;

        $datos = [
            'suscripcion_id' => $suscripcion_id,
            'cesta_nombre' => $suscripcion->cesta_nombre,
            'importe' => $suscripcion->importe,
            'titulo' => 'Suscripción renovada',
            'mensaje' => sprintf(
                'Tu suscripción a la cesta "%s" ha sido renovada. Importe: %s',
                $suscripcion->cesta_nombre,
                number_format($suscripcion->importe, 2) . '€'
            ),
            'enlace' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'suscripciones'),
            'enlace_texto' => 'Ver mi suscripción',
        ];

        $this->enviar('gc_suscripcion_renovada', [$usuario_id], $datos);
    }

    /**
     * Notificar nuevo producto
     */
    public function notificar_nuevo_producto($producto_id) {
        $config = get_option('flavor_gc_settings', []);
        if (empty($config['notificar_nuevos_productos'])) {
            return;
        }

        $producto = get_post($producto_id);
        if (!$producto) return;

        $consumidores = $this->obtener_consumidores_activos();

        $datos = [
            'producto_id' => $producto_id,
            'producto_nombre' => $producto->post_title,
            'titulo' => 'Nuevo producto disponible',
            'mensaje' => sprintf(
                'Hay un nuevo producto disponible: %s',
                $producto->post_title
            ),
            'enlace' => add_query_arg('product', intval($producto_id), Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'productos')),
            'enlace_texto' => 'Ver producto',
        ];

        $this->enviar('gc_nuevo_producto', $consumidores, $datos);
    }

    /**
     * Notificar consolidado listo a productores
     */
    public function notificar_consolidado_listo($ciclo_id, $productor_id) {
        $ciclo = get_post($ciclo_id);
        if (!$ciclo) return;

        $datos = [
            'ciclo_id' => $ciclo_id,
            'ciclo_nombre' => $ciclo->post_title,
            'titulo' => 'Consolidado disponible',
            'mensaje' => sprintf(
                'El consolidado de pedidos para el ciclo "%s" está disponible.',
                $ciclo->post_title
            ),
            'enlace' => admin_url('admin.php?page=gc-consolidado&ciclo=' . $ciclo_id),
            'enlace_texto' => 'Ver consolidado',
        ];

        $this->enviar('gc_consolidado_listo', [$productor_id], $datos, ['email', 'whatsapp']);
    }

    /**
     * Enviar recordatorios de cierre de pedidos
     */
    public function enviar_recordatorios_cierre() {
        global $wpdb;

        // Buscar ciclos que cierran en 24 horas
        $manana = date('Y-m-d', strtotime('+1 day'));

        $ciclos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, pm.meta_value as fecha_cierre
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'gc_ciclo'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_gc_fecha_cierre'
             AND DATE(pm.meta_value) = %s",
            $manana
        ));

        foreach ($ciclos as $ciclo) {
            // Verificar si ya se envió recordatorio
            $enviado = get_post_meta($ciclo->ID, '_gc_recordatorio_enviado', true);
            if ($enviado) continue;

            $consumidores = $this->obtener_consumidores_activos();

            $datos = [
                'ciclo_id' => $ciclo->ID,
                'ciclo_nombre' => $ciclo->post_title,
                'fecha_cierre' => $ciclo->fecha_cierre,
                'titulo' => 'Cierre de pedidos mañana',
                'mensaje' => sprintf(
                    'El ciclo "%s" cierra mañana. Si aún no has hecho tu pedido, hazlo antes del cierre.',
                    $ciclo->post_title
                ),
                'enlace' => add_query_arg('ciclo', intval($ciclo->ID), Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'ciclo')),
                'enlace_texto' => 'Hacer pedido ahora',
            ];

            $this->enviar('gc_cierre_pedidos', $consumidores, $datos);

            // Marcar como enviado
            update_post_meta($ciclo->ID, '_gc_recordatorio_enviado', 1);
        }
    }

    /**
     * Enviar recordatorios de suscripción
     */
    public function enviar_recordatorios_suscripcion() {
        global $wpdb;

        // Suscripciones que se renuevan en 3 días
        $fecha_cargo = date('Y-m-d', strtotime('+3 days'));

        $suscripciones = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, c.nombre as cesta_nombre, u.display_name
             FROM {$wpdb->prefix}flavor_gc_suscripciones s
             LEFT JOIN {$wpdb->prefix}flavor_gc_cestas_tipo c ON s.tipo_cesta = c.slug
             LEFT JOIN {$wpdb->prefix}flavor_gc_consumidores con ON s.consumidor_id = con.id
             LEFT JOIN {$wpdb->users} u ON con.usuario_id = u.ID
             WHERE s.estado = 'activa'
             AND DATE(s.fecha_proximo_cargo) = %s",
            $fecha_cargo
        ));

        foreach ($suscripciones as $suscripcion) {
            $consumidor = $wpdb->get_var($wpdb->prepare(
                "SELECT usuario_id FROM {$wpdb->prefix}flavor_gc_consumidores WHERE id = %d",
                $suscripcion->consumidor_id
            ));

            if (!$consumidor) continue;

            $datos = [
                'suscripcion_id' => $suscripcion->id,
                'cesta_nombre' => $suscripcion->cesta_nombre,
                'importe' => $suscripcion->importe,
                'fecha_cargo' => $suscripcion->fecha_proximo_cargo,
                'titulo' => 'Renovación de suscripción próxima',
                'mensaje' => sprintf(
                    'Tu suscripción a la cesta "%s" se renovará en 3 días. Importe: %s',
                    $suscripcion->cesta_nombre,
                    number_format($suscripcion->importe, 2) . '€'
                ),
                'enlace' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'suscripciones'),
                'enlace_texto' => 'Gestionar suscripción',
            ];

            $this->enviar('gc_recordatorio_suscripcion', [$consumidor], $datos);
        }
    }

    /**
     * Obtener consumidores activos
     */
    private function obtener_consumidores_activos() {
        global $wpdb;

        return $wpdb->get_col(
            "SELECT usuario_id FROM {$wpdb->prefix}flavor_gc_consumidores
             WHERE estado = 'activo'"
        );
    }

    /**
     * Obtener canales disponibles
     */
    public function get_canales() {
        return $this->canales;
    }

    /**
     * Verificar si un canal está activo
     */
    public function canal_activo($canal_id) {
        return isset($this->canales[$canal_id]) && $this->canales[$canal_id]['activo'];
    }
}
