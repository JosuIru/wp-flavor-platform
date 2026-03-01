<?php
/**
 * Dashboard Tab para Chat Grupos
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Grupos_Dashboard_Tab {

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
        $tabs['chat-grupos'] = [
            'label' => __('Grupos', 'flavor-chat-ia'),
            'icon' => 'dashicons-groups',
            'callback' => [$this, 'render_tab'],
            'priority' => 18,
        ];
        return $tabs;
    }

    public function render_tab() {
        $datos = $this->obtener_datos_usuario();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'mis-grupos';

        ?>
        <div class="flavor-chat-grupos-dashboard">
            <!-- Navegación interna -->
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=chat-grupos&subtab=mis-grupos" class="subtab <?php echo $subtab === 'mis-grupos' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-users"></span> Mis Grupos
                    <?php if ($datos['mensajes_sin_leer'] > 0): ?>
                        <span class="badge"><?php echo $datos['mensajes_sin_leer']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=chat-grupos&subtab=explorar" class="subtab <?php echo $subtab === 'explorar' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-search"></span> Explorar
                </a>
                <a href="?tab=chat-grupos&subtab=crear" class="subtab <?php echo $subtab === 'crear' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt"></span> Crear Grupo
                </a>
                <a href="?tab=chat-grupos&subtab=invitaciones" class="subtab <?php echo $subtab === 'invitaciones' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-email"></span> Invitaciones
                    <?php if ($datos['invitaciones_pendientes'] > 0): ?>
                        <span class="badge"><?php echo $datos['invitaciones_pendientes']; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'explorar':
                        $this->render_explorar($datos);
                        break;
                    case 'crear':
                        $this->render_crear();
                        break;
                    case 'invitaciones':
                        $this->render_invitaciones($datos);
                        break;
                    default:
                        $this->render_mis_grupos($datos);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_mis_grupos($datos) {
        ?>
        <!-- KPIs -->
        <div class="flavor-kpi-grid">
            <?php
            flavor_render_component('shared/kpi-card', [
                'label' => 'Mis Grupos',
                'value' => count($datos['mis_grupos']),
                'icon' => 'dashicons-groups',
                'color' => 'blue'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Sin Leer',
                'value' => $datos['mensajes_sin_leer'],
                'icon' => 'dashicons-email-alt',
                'color' => 'red'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Grupos Admin',
                'value' => $datos['grupos_admin'],
                'icon' => 'dashicons-admin-generic',
                'color' => 'purple'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Mensajes Hoy',
                'value' => $datos['mensajes_hoy'],
                'icon' => 'dashicons-format-chat',
                'color' => 'green'
            ]);
            ?>
        </div>

        <!-- Lista de grupos -->
        <div class="grupos-lista">
            <?php if (empty($datos['mis_grupos'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-groups"></span>
                    <p>No perteneces a ningún grupo todavía</p>
                    <a href="?tab=chat-grupos&subtab=explorar" class="flavor-btn flavor-btn-primary">Explorar grupos</a>
                </div>
            <?php else: ?>
                <?php foreach ($datos['mis_grupos'] as $grupo): ?>
                    <div class="grupo-card" data-id="<?php echo esc_attr($grupo->id); ?>">
                        <div class="grupo-avatar">
                            <?php if ($grupo->imagen): ?>
                                <img src="<?php echo esc_url($grupo->imagen); ?>" alt="">
                            <?php else: ?>
                                <span class="dashicons dashicons-groups"></span>
                            <?php endif; ?>
                            <?php if ($grupo->sin_leer > 0): ?>
                                <span class="badge-sin-leer"><?php echo $grupo->sin_leer; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="grupo-info">
                            <h4><?php echo esc_html($grupo->nombre); ?></h4>
                            <p class="grupo-descripcion"><?php echo esc_html(wp_trim_words($grupo->descripcion, 15)); ?></p>
                            <div class="grupo-meta">
                                <span><span class="dashicons dashicons-admin-users"></span> <?php echo $grupo->total_miembros; ?> miembros</span>
                                <?php if ($grupo->ultimo_mensaje): ?>
                                    <span class="ultimo-mensaje">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo human_time_diff(strtotime($grupo->ultimo_mensaje)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="grupo-acciones">
                            <a href="<?php echo home_url('/mi-portal/chat-grupos/' . $grupo->id . '/'); ?>"
                               class="flavor-btn flavor-btn-primary flavor-btn-sm">
                                Abrir Chat
                            </a>
                            <?php if ($grupo->es_admin): ?>
                                <span class="badge-admin">Admin</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_explorar($datos) {
        ?>
        <div class="explorar-grupos">
            <!-- Buscador -->
            <div class="explorar-busqueda">
                <form method="get" class="form-buscar-grupos">
                    <input type="hidden" name="tab" value="chat-grupos">
                    <input type="hidden" name="subtab" value="explorar">
                    <input type="text" name="buscar" placeholder="Buscar grupos..."
                           value="<?php echo esc_attr($_GET['buscar'] ?? ''); ?>">
                    <button type="submit" class="flavor-btn">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </form>
            </div>

            <!-- Categorías populares -->
            <div class="categorias-grupos">
                <span class="categoria active" data-cat="todos">Todos</span>
                <span class="categoria" data-cat="tematicos">Temáticos</span>
                <span class="categoria" data-cat="barrios">Por Barrio</span>
                <span class="categoria" data-cat="proyectos">Proyectos</span>
            </div>

            <!-- Grupos públicos -->
            <div class="grupos-publicos">
                <h3>Grupos destacados</h3>
                <?php if (empty($datos['grupos_publicos'])): ?>
                    <p class="flavor-empty-state">No hay grupos públicos disponibles</p>
                <?php else: ?>
                    <div class="grupos-grid">
                        <?php foreach ($datos['grupos_publicos'] as $grupo): ?>
                            <div class="grupo-publico-card">
                                <div class="grupo-imagen">
                                    <?php if ($grupo->imagen): ?>
                                        <img src="<?php echo esc_url($grupo->imagen); ?>" alt="">
                                    <?php else: ?>
                                        <div class="placeholder-img">
                                            <span class="dashicons dashicons-groups"></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="grupo-contenido">
                                    <h4><?php echo esc_html($grupo->nombre); ?></h4>
                                    <p><?php echo esc_html(wp_trim_words($grupo->descripcion, 20)); ?></p>
                                    <div class="grupo-stats">
                                        <span><?php echo $grupo->total_miembros; ?> miembros</span>
                                        <span><?php echo $grupo->mensajes_semana; ?> msgs/sem</span>
                                    </div>
                                </div>
                                <div class="grupo-accion">
                                    <?php if ($grupo->ya_miembro): ?>
                                        <span class="ya-miembro">Ya eres miembro</span>
                                    <?php else: ?>
                                        <button class="flavor-btn flavor-btn-primary unirse-grupo" data-id="<?php echo $grupo->id; ?>">
                                            Unirse
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_crear() {
        ?>
        <div class="crear-grupo">
            <h3>Crear nuevo grupo</h3>

            <form id="form-crear-grupo" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_crear_grupo_chat', 'grupo_nonce'); ?>

                <div class="form-group">
                    <label for="nombre">Nombre del grupo *</label>
                    <input type="text" id="nombre" name="nombre" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"></textarea>
                </div>

                <div class="form-group">
                    <label for="imagen">Imagen del grupo</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="privacidad">Privacidad</label>
                    <select id="privacidad" name="privacidad">
                        <option value="publico">Público - Cualquiera puede unirse</option>
                        <option value="privado">Privado - Solo por invitación</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="">Sin categoría</option>
                        <option value="tematicos">Temático</option>
                        <option value="barrios">Por Barrio</option>
                        <option value="proyectos">Proyecto</option>
                        <option value="social">Social</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="permitir_archivos" checked>
                        Permitir compartir archivos
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="permitir_encuestas" checked>
                        Permitir encuestas
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">Crear Grupo</button>
                </div>
            </form>
        </div>
        <?php
    }

    private function render_invitaciones($datos) {
        ?>
        <div class="invitaciones-grupos">
            <h3>Invitaciones pendientes</h3>

            <?php if (empty($datos['invitaciones'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-email"></span>
                    <p>No tienes invitaciones pendientes</p>
                </div>
            <?php else: ?>
                <div class="lista-invitaciones">
                    <?php foreach ($datos['invitaciones'] as $invitacion): ?>
                        <div class="invitacion-card" data-id="<?php echo $invitacion->id; ?>">
                            <div class="invitacion-grupo">
                                <?php if ($invitacion->grupo_imagen): ?>
                                    <img src="<?php echo esc_url($invitacion->grupo_imagen); ?>" alt="">
                                <?php else: ?>
                                    <span class="dashicons dashicons-groups"></span>
                                <?php endif; ?>
                            </div>
                            <div class="invitacion-info">
                                <h4><?php echo esc_html($invitacion->grupo_nombre); ?></h4>
                                <p>Invitado por: <strong><?php echo esc_html($invitacion->invitador_nombre); ?></strong></p>
                                <span class="fecha"><?php echo human_time_diff(strtotime($invitacion->created_at)); ?></span>
                            </div>
                            <div class="invitacion-acciones">
                                <button class="flavor-btn flavor-btn-primary flavor-btn-sm aceptar-invitacion">
                                    Aceptar
                                </button>
                                <button class="flavor-btn flavor-btn-sm rechazar-invitacion">
                                    Rechazar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_invitaciones = $wpdb->prefix . 'flavor_chat_grupos_invitaciones';

        // Mis grupos con conteo de mensajes sin leer
        $mis_grupos = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, m.rol, m.ultimo_leido,
                    (SELECT COUNT(*) FROM $tabla_miembros WHERE grupo_id = g.id AND estado = 'activo') as total_miembros,
                    (SELECT COUNT(*) FROM $tabla_mensajes msg
                     WHERE msg.grupo_id = g.id AND msg.created_at > COALESCE(m.ultimo_leido, '1970-01-01')) as sin_leer,
                    (SELECT MAX(created_at) FROM $tabla_mensajes WHERE grupo_id = g.id) as ultimo_mensaje,
                    CASE WHEN m.rol IN ('admin', 'owner') THEN 1 ELSE 0 END as es_admin
             FROM $tabla_grupos g
             JOIN $tabla_miembros m ON g.id = m.grupo_id AND m.usuario_id = %d AND m.estado = 'activo'
             ORDER BY ultimo_mensaje DESC",
            $user_id
        ));

        // Total mensajes sin leer
        $mensajes_sin_leer = 0;
        $grupos_admin = 0;
        foreach ($mis_grupos as $grupo) {
            $mensajes_sin_leer += $grupo->sin_leer;
            if ($grupo->es_admin) {
                $grupos_admin++;
            }
        }

        // Mensajes enviados hoy
        $mensajes_hoy = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_mensajes
             WHERE usuario_id = %d AND DATE(created_at) = CURDATE()",
            $user_id
        ));

        // Invitaciones pendientes
        $invitaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, g.nombre as grupo_nombre, g.imagen as grupo_imagen, u.display_name as invitador_nombre
             FROM $tabla_invitaciones i
             JOIN $tabla_grupos g ON i.grupo_id = g.id
             JOIN {$wpdb->users} u ON i.invitador_id = u.ID
             WHERE i.invitado_id = %d AND i.estado = 'pendiente'
             ORDER BY i.created_at DESC",
            $user_id
        ));

        // Grupos públicos para explorar
        $grupos_publicos = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*,
                    (SELECT COUNT(*) FROM $tabla_miembros WHERE grupo_id = g.id AND estado = 'activo') as total_miembros,
                    (SELECT COUNT(*) FROM $tabla_mensajes WHERE grupo_id = g.id AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) as mensajes_semana,
                    EXISTS(SELECT 1 FROM $tabla_miembros WHERE grupo_id = g.id AND usuario_id = %d AND estado = 'activo') as ya_miembro
             FROM $tabla_grupos g
             WHERE g.privacidad = 'publico' AND g.estado = 'activo'
             ORDER BY total_miembros DESC
             LIMIT 12",
            $user_id
        ));

        return [
            'mis_grupos' => $mis_grupos ?: [],
            'mensajes_sin_leer' => $mensajes_sin_leer,
            'grupos_admin' => $grupos_admin,
            'mensajes_hoy' => $mensajes_hoy ?: 0,
            'invitaciones' => $invitaciones ?: [],
            'invitaciones_pendientes' => count($invitaciones),
            'grupos_publicos' => $grupos_publicos ?: [],
        ];
    }
}

Flavor_Chat_Grupos_Dashboard_Tab::get_instance();
