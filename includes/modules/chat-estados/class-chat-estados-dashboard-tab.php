<?php
/**
 * Dashboard Tab para Chat Estados (Stories)
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Estados_Dashboard_Tab {

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
        $tabs['estados'] = [
            'label' => __('Estados', 'flavor-chat-ia'),
            'icon' => 'dashicons-format-status',
            'callback' => [$this, 'render_tab'],
            'priority' => 19,
        ];
        return $tabs;
    }

    public function render_tab() {
        $datos = $this->obtener_datos_usuario();
        ?>
        <div class="flavor-estados-dashboard">
            <!-- Crear nuevo estado -->
            <div class="crear-estado">
                <div class="crear-estado-preview">
                    <?php echo get_avatar(get_current_user_id(), 60); ?>
                    <div class="crear-estado-btn" id="btn-crear-estado">
                        <span class="dashicons dashicons-plus"></span>
                    </div>
                </div>
                <span>Mi estado</span>
            </div>

            <!-- Estados de contactos -->
            <div class="estados-contactos">
                <h3>Estados recientes</h3>

                <?php if (empty($datos['estados_contactos'])): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-format-status"></span>
                        <p>No hay estados recientes de tus contactos</p>
                    </div>
                <?php else: ?>
                    <div class="estados-lista">
                        <?php foreach ($datos['estados_contactos'] as $contacto): ?>
                            <div class="estado-contacto" data-usuario="<?php echo $contacto->usuario_id; ?>">
                                <div class="estado-avatar <?php echo $contacto->visto ? 'visto' : 'nuevo'; ?>">
                                    <?php echo get_avatar($contacto->usuario_id, 60); ?>
                                </div>
                                <span class="estado-nombre"><?php echo esc_html($contacto->nombre); ?></span>
                                <span class="estado-tiempo"><?php echo human_time_diff(strtotime($contacto->ultimo_estado)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mis estados activos -->
            <?php if (!empty($datos['mis_estados'])): ?>
            <div class="mis-estados">
                <h3>Mis estados activos</h3>
                <div class="mis-estados-grid">
                    <?php foreach ($datos['mis_estados'] as $estado): ?>
                        <div class="mi-estado-item" data-id="<?php echo $estado->id; ?>">
                            <?php if ($estado->tipo === 'imagen'): ?>
                                <img src="<?php echo esc_url($estado->contenido); ?>" alt="">
                            <?php elseif ($estado->tipo === 'texto'): ?>
                                <div class="estado-texto" style="background: <?php echo esc_attr($estado->fondo ?? '#667eea'); ?>">
                                    <?php echo esc_html($estado->contenido); ?>
                                </div>
                            <?php endif; ?>
                            <div class="estado-overlay">
                                <span class="vistas">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo $estado->vistas; ?>
                                </span>
                                <button class="eliminar-estado" title="Eliminar">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                            <span class="estado-expira">Expira en <?php echo human_time_diff(strtotime($estado->expira_at)); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Modal crear estado -->
            <div id="modal-crear-estado" class="flavor-modal" style="display:none;">
                <div class="flavor-modal-content modal-estado">
                    <div class="flavor-modal-header">
                        <h3>Nuevo estado</h3>
                        <button class="cerrar-modal">&times;</button>
                    </div>
                    <div class="flavor-modal-body">
                        <!-- Tabs tipo de estado -->
                        <div class="estado-tipo-tabs">
                            <button class="tipo-tab active" data-tipo="texto">
                                <span class="dashicons dashicons-edit"></span> Texto
                            </button>
                            <button class="tipo-tab" data-tipo="imagen">
                                <span class="dashicons dashicons-format-image"></span> Imagen
                            </button>
                        </div>

                        <!-- Formulario texto -->
                        <form id="form-estado-texto" class="form-estado">
                            <?php wp_nonce_field('flavor_crear_estado', 'estado_nonce'); ?>
                            <input type="hidden" name="tipo" value="texto">

                            <div class="estado-preview-texto" id="preview-texto">
                                <textarea name="contenido" placeholder="Escribe tu estado..." maxlength="250"></textarea>
                            </div>

                            <div class="estado-fondos">
                                <span class="fondo-opcion active" data-color="#667eea" style="background: #667eea;"></span>
                                <span class="fondo-opcion" data-color="#f44336" style="background: #f44336;"></span>
                                <span class="fondo-opcion" data-color="#4caf50" style="background: #4caf50;"></span>
                                <span class="fondo-opcion" data-color="#ff9800" style="background: #ff9800;"></span>
                                <span class="fondo-opcion" data-color="#9c27b0" style="background: #9c27b0;"></span>
                                <span class="fondo-opcion" data-color="#00bcd4" style="background: #00bcd4;"></span>
                                <span class="fondo-opcion" data-color="#795548" style="background: #795548;"></span>
                                <span class="fondo-opcion" data-color="#607d8b" style="background: #607d8b;"></span>
                            </div>
                            <input type="hidden" name="fondo" value="#667eea">

                            <button type="submit" class="flavor-btn flavor-btn-primary">Publicar estado</button>
                        </form>

                        <!-- Formulario imagen -->
                        <form id="form-estado-imagen" class="form-estado" style="display:none;">
                            <?php wp_nonce_field('flavor_crear_estado', 'estado_nonce'); ?>
                            <input type="hidden" name="tipo" value="imagen">

                            <div class="estado-upload-imagen">
                                <input type="file" name="imagen" accept="image/*" id="input-imagen-estado">
                                <div class="upload-placeholder" id="placeholder-imagen">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p>Arrastra una imagen o haz clic para seleccionar</p>
                                </div>
                                <img id="preview-imagen-estado" style="display:none;">
                            </div>

                            <div class="form-group">
                                <input type="text" name="caption" placeholder="Añadir texto (opcional)">
                            </div>

                            <button type="submit" class="flavor-btn flavor-btn-primary">Publicar estado</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal ver estados -->
            <div id="modal-ver-estados" class="flavor-modal modal-fullscreen" style="display:none;">
                <div class="estados-viewer">
                    <div class="estados-progress">
                        <!-- Barras de progreso dinámicas -->
                    </div>
                    <button class="cerrar-estados">&times;</button>
                    <div class="estados-contenido">
                        <!-- Contenido del estado actual -->
                    </div>
                    <div class="estados-info">
                        <div class="info-usuario">
                            <img src="" alt="" class="usuario-avatar">
                            <span class="usuario-nombre"></span>
                            <span class="estado-fecha"></span>
                        </div>
                    </div>
                    <button class="nav-estado nav-prev"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
                    <button class="nav-estado nav-next"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
                </div>
            </div>
        </div>

        <style>
            .flavor-estados-dashboard { padding: 20px; }
            .crear-estado { display: inline-block; text-align: center; margin-right: 20px; cursor: pointer; }
            .crear-estado-preview { position: relative; }
            .crear-estado-btn { position: absolute; bottom: 0; right: 0; width: 24px; height: 24px; background: #4caf50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; }
            .estados-contactos { margin-top: 30px; }
            .estados-lista { display: flex; gap: 15px; flex-wrap: wrap; }
            .estado-contacto { text-align: center; cursor: pointer; }
            .estado-avatar { padding: 3px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); }
            .estado-avatar.visto { background: #ddd; }
            .estado-avatar img { border-radius: 50%; border: 3px solid #fff; }
            .estado-nombre { display: block; font-size: 12px; margin-top: 5px; }
            .estado-tiempo { display: block; font-size: 11px; color: #999; }
            .mis-estados { margin-top: 30px; }
            .mis-estados-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; }
            .mi-estado-item { position: relative; border-radius: 12px; overflow: hidden; aspect-ratio: 9/16; }
            .mi-estado-item img { width: 100%; height: 100%; object-fit: cover; }
            .estado-texto { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; padding: 15px; text-align: center; }
            .estado-overlay { position: absolute; top: 0; left: 0; right: 0; padding: 10px; display: flex; justify-content: space-between; background: linear-gradient(to bottom, rgba(0,0,0,0.5), transparent); color: #fff; }
            .estado-expira { position: absolute; bottom: 10px; left: 10px; right: 10px; text-align: center; font-size: 11px; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
            .modal-estado .flavor-modal-content { max-width: 400px; }
            .estado-tipo-tabs { display: flex; margin-bottom: 20px; }
            .tipo-tab { flex: 1; padding: 10px; border: none; background: #f5f5f5; cursor: pointer; }
            .tipo-tab.active { background: #667eea; color: #fff; }
            .estado-preview-texto { aspect-ratio: 9/16; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; transition: background 0.3s; background: #667eea; }
            .estado-preview-texto textarea { background: transparent; border: none; color: #fff; text-align: center; font-size: 18px; width: 100%; height: 100%; resize: none; padding: 20px; }
            .estado-preview-texto textarea::placeholder { color: rgba(255,255,255,0.7); }
            .estado-fondos { display: flex; gap: 10px; justify-content: center; margin-bottom: 20px; }
            .fondo-opcion { width: 30px; height: 30px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; transition: transform 0.2s; }
            .fondo-opcion:hover, .fondo-opcion.active { transform: scale(1.2); border-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
            .estado-upload-imagen { aspect-ratio: 9/16; border: 2px dashed #ddd; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; cursor: pointer; position: relative; overflow: hidden; }
            .upload-placeholder { text-align: center; color: #999; }
            .upload-placeholder .dashicons { font-size: 48px; width: 48px; height: 48px; }
            #preview-imagen-estado { width: 100%; height: 100%; object-fit: cover; position: absolute; }
            .modal-fullscreen .estados-viewer { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: #000; display: flex; align-items: center; justify-content: center; }
            .estados-contenido { max-width: 400px; width: 100%; }
            .cerrar-estados { position: absolute; top: 20px; right: 20px; background: none; border: none; color: #fff; font-size: 32px; cursor: pointer; z-index: 10; }
            .nav-estado { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); border: none; color: #fff; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; }
            .nav-prev { left: 20px; }
            .nav-next { right: 20px; }
        </style>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_estados = $wpdb->prefix . 'flavor_chat_estados';
        $tabla_vistas = $wpdb->prefix . 'flavor_chat_estados_vistas';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        // Mis estados activos (no expirados)
        $mis_estados = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, (SELECT COUNT(*) FROM $tabla_vistas WHERE estado_id = e.id) as vistas
             FROM $tabla_estados e
             WHERE e.usuario_id = %d AND e.expira_at > NOW()
             ORDER BY e.created_at DESC",
            $user_id
        ));

        // Estados de contactos (personas que sigo)
        $estados_contactos = $wpdb->get_results($wpdb->prepare(
            "SELECT e.usuario_id, u.display_name as nombre,
                    MAX(e.created_at) as ultimo_estado,
                    EXISTS(SELECT 1 FROM $tabla_vistas WHERE estado_id = e.id AND usuario_id = %d) as visto
             FROM $tabla_estados e
             JOIN {$wpdb->users} u ON e.usuario_id = u.ID
             JOIN $tabla_seguimientos s ON e.usuario_id = s.seguido_id AND s.seguidor_id = %d
             WHERE e.expira_at > NOW()
             GROUP BY e.usuario_id
             ORDER BY ultimo_estado DESC",
            $user_id, $user_id
        ));

        return [
            'mis_estados' => $mis_estados ?: [],
            'estados_contactos' => $estados_contactos ?: [],
        ];
    }
}

Flavor_Chat_Estados_Dashboard_Tab::get_instance();
