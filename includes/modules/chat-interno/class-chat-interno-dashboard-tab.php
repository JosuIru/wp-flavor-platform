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
        $datos = $this->obtener_datos_usuario();
        $conversacion_id = isset($_GET['conv']) ? absint($_GET['conv']) : null;

        ?>
        <div class="flavor-chat-interno-dashboard">
            <div class="chat-layout">
                <!-- Sidebar conversaciones -->
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        <h3>Conversaciones</h3>
                        <button class="nueva-conversacion" title="Nueva conversación">
                            <span class="dashicons dashicons-plus-alt"></span>
                        </button>
                    </div>

                    <div class="chat-buscar">
                        <input type="text" placeholder="Buscar conversaciones..." id="buscar-conversacion">
                    </div>

                    <div class="conversaciones-lista">
                        <?php if (empty($datos['conversaciones'])): ?>
                            <div class="sin-conversaciones">
                                <span class="dashicons dashicons-format-chat"></span>
                                <p>No tienes conversaciones</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($datos['conversaciones'] as $conv): ?>
                                <a href="?tab=chat-interno&conv=<?php echo $conv->id; ?>"
                                   class="conversacion-item <?php echo $conversacion_id == $conv->id ? 'activa' : ''; ?>
                                          <?php echo $conv->sin_leer > 0 ? 'sin-leer' : ''; ?>">
                                    <div class="conv-avatar">
                                        <?php echo get_avatar($conv->otro_usuario_id, 40); ?>
                                        <?php if ($conv->esta_online): ?>
                                            <span class="estado-online"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conv-info">
                                        <span class="conv-nombre"><?php echo esc_html($conv->otro_usuario_nombre); ?></span>
                                        <span class="conv-ultimo">
                                            <?php echo esc_html(wp_trim_words($conv->ultimo_mensaje, 8)); ?>
                                        </span>
                                    </div>
                                    <div class="conv-meta">
                                        <span class="conv-fecha"><?php echo $this->formato_fecha($conv->ultimo_mensaje_fecha); ?></span>
                                        <?php if ($conv->sin_leer > 0): ?>
                                            <span class="badge-sin-leer"><?php echo $conv->sin_leer; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Panel principal -->
                <div class="chat-principal">
                    <?php if ($conversacion_id && isset($datos['conversacion_actual'])): ?>
                        <?php $this->render_conversacion($datos['conversacion_actual']); ?>
                    <?php else: ?>
                        <div class="chat-vacio">
                            <span class="dashicons dashicons-format-chat"></span>
                            <h3>Selecciona una conversación</h3>
                            <p>Elige una conversación de la lista o inicia una nueva</p>
                            <button class="flavor-btn flavor-btn-primary nueva-conversacion">
                                Nueva conversación
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal nueva conversación -->
            <div id="modal-nueva-conversacion" class="flavor-modal" style="display:none;">
                <div class="flavor-modal-content">
                    <div class="flavor-modal-header">
                        <h3>Nueva conversación</h3>
                        <button class="cerrar-modal">&times;</button>
                    </div>
                    <div class="flavor-modal-body">
                        <div class="form-group">
                            <label>Buscar usuario</label>
                            <input type="text" id="buscar-usuario" placeholder="Nombre o email...">
                        </div>
                        <div id="resultados-usuarios"></div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .chat-layout { display: flex; height: 70vh; gap: 0; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
            .chat-sidebar { width: 320px; background: #f8f9fa; border-right: 1px solid #ddd; display: flex; flex-direction: column; }
            .chat-sidebar-header { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #ddd; }
            .chat-sidebar-header h3 { margin: 0; font-size: 16px; }
            .chat-buscar { padding: 10px; }
            .chat-buscar input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 20px; }
            .conversaciones-lista { flex: 1; overflow-y: auto; }
            .conversacion-item { display: flex; padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; text-decoration: none; color: inherit; }
            .conversacion-item:hover { background: #e9ecef; }
            .conversacion-item.activa { background: #e3f2fd; }
            .conversacion-item.sin-leer { background: #fff3e0; }
            .conv-avatar { position: relative; margin-right: 12px; }
            .estado-online { position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #4caf50; border-radius: 50%; border: 2px solid #fff; }
            .conv-info { flex: 1; min-width: 0; }
            .conv-nombre { display: block; font-weight: 600; margin-bottom: 4px; }
            .conv-ultimo { display: block; font-size: 13px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .conv-meta { text-align: right; }
            .conv-fecha { font-size: 11px; color: #999; }
            .badge-sin-leer { display: inline-block; min-width: 20px; padding: 2px 6px; background: #ff5722; color: #fff; border-radius: 10px; font-size: 11px; text-align: center; margin-top: 4px; }
            .chat-principal { flex: 1; display: flex; flex-direction: column; background: #fff; }
            .chat-vacio { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #666; }
            .chat-vacio .dashicons { font-size: 64px; width: 64px; height: 64px; color: #ddd; margin-bottom: 20px; }
            .chat-header { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #ddd; background: #f8f9fa; }
            .chat-mensajes { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
            .mensaje { max-width: 70%; padding: 10px 15px; border-radius: 18px; }
            .mensaje.enviado { align-self: flex-end; background: #0084ff; color: #fff; }
            .mensaje.recibido { align-self: flex-start; background: #e4e6eb; }
            .mensaje-fecha { font-size: 11px; margin-top: 4px; opacity: 0.7; }
            .chat-input { display: flex; padding: 15px; border-top: 1px solid #ddd; gap: 10px; }
            .chat-input textarea { flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 20px; resize: none; }
            .sin-conversaciones { text-align: center; padding: 40px 20px; color: #999; }
        </style>
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
                        "SELECT * FROM $tabla_msg
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
