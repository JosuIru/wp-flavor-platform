<?php
/**
 * Dashboard Tab para Chat Interno
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Interno_Dashboard_Tab {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public function registrar_tabs($tabs) {
        $tabs['chat-interno'] = [
            'label' => __('Mensajes', 'flavor-chat-ia'),
            'icon' => 'dashicons-email-alt2',
            'callback' => [$this, 'render_tab'],
            'priority' => 17,
        ];
        return $tabs;
    }

    public function render_tab() {
        $conversacion_id = isset($_GET['conv']) ? absint($_GET['conv']) : null;

        ?>
        <div class="flavor-chat-interno-dashboard">
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=chat-interno" class="subtab <?php echo !$conversacion_id ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-email-alt2"></span> <?php esc_html_e('Conversaciones', 'flavor-chat-ia'); ?>
                </a>
                <a href="?tab=chat-interno&subtab=nuevo" class="subtab <?php echo (isset($_GET['subtab']) && $_GET['subtab'] === 'nuevo') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Nuevo', 'flavor-chat-ia'); ?>
                </a>
                <a href="?tab=chat-interno&subtab=archivados" class="subtab <?php echo (isset($_GET['subtab']) && $_GET['subtab'] === 'archivados') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-archive"></span> <?php esc_html_e('Archivados', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                if ($conversacion_id) {
                    echo do_shortcode('[chat_interno_mensajes id="' . absint($conversacion_id) . '"]');
                } elseif (isset($_GET['subtab']) && $_GET['subtab'] === 'nuevo') {
                    echo do_shortcode('[chat_interno_nuevo]');
                } elseif (isset($_GET['subtab']) && $_GET['subtab'] === 'archivados') {
                    echo do_shortcode('[chat_interno_archivados]');
                } else {
                    echo do_shortcode('[chat_interno_conversaciones]');
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_conversacion($conv) {
        ?>
        <div class="chat-header">
            <?php echo get_avatar($conv->otro_usuario_id, 40); ?>
            <div class="chat-header-info">
                <strong><?php echo esc_html($conv->otro_usuario_nombre); ?></strong>
                <span class="estado-texto">
                    <?php echo $conv->esta_online ? 'En línea' : 'Últ. vez: ' . $this->formato_fecha($conv->ultima_conexion); ?>
                </span>
            </div>
            <div class="chat-header-acciones">
                <button class="flavor-btn-icon" title="Buscar en conversación">
                    <span class="dashicons dashicons-search"></span>
                </button>
                <button class="flavor-btn-icon" title="Más opciones">
                    <span class="dashicons dashicons-ellipsis"></span>
                </button>
            </div>
        </div>

        <div class="chat-mensajes" id="chat-mensajes" data-conv="<?php echo $conv->id; ?>">
            <?php if (empty($conv->mensajes)): ?>
                <div class="sin-mensajes">
                    <p>Envía el primer mensaje</p>
                </div>
            <?php else: ?>
                <?php
                $fecha_actual = '';
                foreach ($conv->mensajes as $msg):
                    $fecha_msg = date('Y-m-d', strtotime($msg->created_at));
                    if ($fecha_msg !== $fecha_actual):
                        $fecha_actual = $fecha_msg;
                ?>
                    <div class="separador-fecha">
                        <span><?php echo $this->formato_fecha_separador($msg->created_at); ?></span>
                    </div>
                <?php endif; ?>
                    <div class="mensaje <?php echo $msg->remitente_id == get_current_user_id() ? 'enviado' : 'recibido'; ?>">
                        <div class="mensaje-contenido"><?php echo esc_html($msg->mensaje); ?></div>
                        <div class="mensaje-fecha">
                            <?php echo date('H:i', strtotime($msg->created_at)); ?>
                            <?php if ($msg->leido && $msg->remitente_id == get_current_user_id()): ?>
                                <span class="leido">✓✓</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-input">
            <button class="flavor-btn-icon adjuntar" title="Adjuntar archivo">
                <span class="dashicons dashicons-paperclip"></span>
            </button>
            <textarea id="mensaje-texto" placeholder="Escribe un mensaje..." rows="1"></textarea>
            <button class="flavor-btn flavor-btn-primary enviar-mensaje" data-conv="<?php echo $conv->id; ?>">
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
        </div>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_conv = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_msg = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_part = $wpdb->prefix . 'flavor_chat_participantes';
        $tabla_estados = $wpdb->prefix . 'flavor_chat_estados_usuario';

        // Obtener conversaciones
        $conversaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id,
                    p2.usuario_id as otro_usuario_id,
                    u.display_name as otro_usuario_nombre,
                    (SELECT mensaje FROM $tabla_msg WHERE conversacion_id = c.id ORDER BY created_at DESC LIMIT 1) as ultimo_mensaje,
                    (SELECT created_at FROM $tabla_msg WHERE conversacion_id = c.id ORDER BY created_at DESC LIMIT 1) as ultimo_mensaje_fecha,
                    (SELECT COUNT(*) FROM $tabla_msg WHERE conversacion_id = c.id AND remitente_id != %d AND leido = 0) as sin_leer,
                    EXISTS(SELECT 1 FROM $tabla_estados WHERE usuario_id = p2.usuario_id AND estado = 'online' AND ultima_actividad > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as esta_online
             FROM $tabla_conv c
             JOIN $tabla_part p1 ON c.id = p1.conversacion_id AND p1.usuario_id = %d
             JOIN $tabla_part p2 ON c.id = p2.conversacion_id AND p2.usuario_id != %d
             JOIN {$wpdb->users} u ON p2.usuario_id = u.ID
             WHERE c.estado = 'activa'
             ORDER BY ultimo_mensaje_fecha DESC",
            $user_id, $user_id, $user_id
        ));

        // Conversación actual si está seleccionada
        $conversacion_actual = null;
        $conv_id = isset($_GET['conv']) ? absint($_GET['conv']) : null;

        if ($conv_id) {
            // Verificar que el usuario tiene acceso
            $tiene_acceso = $wpdb->get_var($wpdb->prepare(
                "SELECT 1 FROM $tabla_part WHERE conversacion_id = %d AND usuario_id = %d",
                $conv_id, $user_id
            ));

            if ($tiene_acceso) {
                // Obtener datos de la conversación
                $conversacion_actual = $wpdb->get_row($wpdb->prepare(
                    "SELECT c.id,
                            p2.usuario_id as otro_usuario_id,
                            u.display_name as otro_usuario_nombre,
                            e.estado = 'online' as esta_online,
                            e.ultima_actividad as ultima_conexion
                     FROM $tabla_conv c
                     JOIN $tabla_part p1 ON c.id = p1.conversacion_id AND p1.usuario_id = %d
                     JOIN $tabla_part p2 ON c.id = p2.conversacion_id AND p2.usuario_id != %d
                     JOIN {$wpdb->users} u ON p2.usuario_id = u.ID
                     LEFT JOIN $tabla_estados e ON e.usuario_id = p2.usuario_id
                     WHERE c.id = %d",
                    $user_id, $user_id, $conv_id
                ));

                if ($conversacion_actual) {
                    // Obtener mensajes
                    $conversacion_actual->mensajes = $wpdb->get_results($wpdb->prepare(
                        "SELECT id, remitente_id, mensaje, created_at, leido
                         FROM $tabla_msg
                         WHERE conversacion_id = %d
                         ORDER BY created_at ASC
                         LIMIT 100",
                        $conv_id
                    ));

                    // Marcar como leídos
                    $wpdb->update(
                        $tabla_msg,
                        ['leido' => 1, 'leido_at' => current_time('mysql')],
                        ['conversacion_id' => $conv_id, 'destinatario_id' => $user_id, 'leido' => 0],
                        ['%d', '%s'],
                        ['%d', '%d', '%d']
                    );
                }
            }
        }

        return [
            'conversaciones' => $conversaciones ?: [],
            'conversacion_actual' => $conversacion_actual,
        ];
    }

    private function formato_fecha($fecha) {
        if (!$fecha) return '';

        $timestamp = strtotime($fecha);
        $hoy = strtotime('today');
        $ayer = strtotime('yesterday');

        if ($timestamp >= $hoy) {
            return date('H:i', $timestamp);
        } elseif ($timestamp >= $ayer) {
            return 'Ayer';
        } elseif ($timestamp >= strtotime('-7 days')) {
            return date_i18n('l', $timestamp);
        } else {
            return date_i18n('j M', $timestamp);
        }
    }

    private function formato_fecha_separador($fecha) {
        $timestamp = strtotime($fecha);
        $hoy = strtotime('today');
        $ayer = strtotime('yesterday');

        if ($timestamp >= $hoy) {
            return 'Hoy';
        } elseif ($timestamp >= $ayer) {
            return 'Ayer';
        } else {
            return date_i18n('j \d\e F \d\e Y', $timestamp);
        }
    }
}

Flavor_Chat_Interno_Dashboard_Tab::get_instance();
