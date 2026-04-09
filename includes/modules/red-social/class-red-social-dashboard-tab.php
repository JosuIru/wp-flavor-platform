<?php
/**
 * Dashboard Tab para Red Social
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Red_Social_Dashboard_Tab {

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
        $tabs['red-social'] = [
            'label' => __('Mi Red', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-share',
            'callback' => [$this, 'render_tab'],
            'priority' => 15,
        ];
        return $tabs;
    }

    public function render_tab() {
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'feed';
        $this->enqueue_assets();

        ?>
        <div class="flavor-red-social-dashboard">
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=red-social&subtab=feed" class="subtab <?php echo $subtab === 'feed' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-rss"></span> Feed
                </a>
                <a href="?tab=red-social&subtab=perfil" class="subtab <?php echo $subtab === 'perfil' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-users"></span> Mi Perfil
                </a>
                <a href="?tab=red-social&subtab=amigos" class="subtab <?php echo $subtab === 'amigos' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-groups"></span> Conexiones
                </a>
                <a href="?tab=red-social&subtab=actividad" class="subtab <?php echo $subtab === 'actividad' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-line"></span> Mi Actividad
                </a>
                <a href="?tab=red-social&subtab=reputacion" class="subtab <?php echo $subtab === 'reputacion' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-star-filled"></span> Reputación
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'perfil':
                        echo do_shortcode('[rs_perfil]');
                        break;
                    case 'amigos':
                        echo do_shortcode('[rs_explorar]');
                        break;
                    case 'actividad':
                        echo do_shortcode('[rs_mi_actividad]');
                        break;
                    case 'reputacion':
                        echo do_shortcode('[rs_reputacion]');
                        break;
                    default:
                        echo do_shortcode('[rs_feed]');
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function enqueue_assets() {
        $module_url = plugin_dir_url(__FILE__);
        $version = defined('Flavor_Chat_Red_Social_Module::VERSION') ? Flavor_Chat_Red_Social_Module::VERSION : '2.0.0';

        wp_enqueue_style(
            'flavor-red-social',
            $module_url . 'assets/css/red-social.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-red-social',
            $module_url . 'assets/js/red-social.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-red-social', 'flavorRedSocial', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rs_nonce'),
            'userId' => get_current_user_id(),
            'maxCaracteres' => 5000,
            'maxImagenes' => 10,
        ]);
    }

    private function render_feed($datos) {
        ?>
        <!-- KPIs -->
        <div class="flavor-kpi-grid">
            <?php
            flavor_render_component('shared/kpi-card', [
                'label' => 'Publicaciones',
                'value' => $datos['total_publicaciones'],
                'icon' => 'dashicons-edit',
                'color' => 'blue'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Seguidores',
                'value' => $datos['seguidores'],
                'icon' => 'dashicons-groups',
                'color' => 'green'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Siguiendo',
                'value' => $datos['siguiendo'],
                'icon' => 'dashicons-heart',
                'color' => 'purple'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Puntos',
                'value' => number_format($datos['puntos_reputacion']),
                'icon' => 'dashicons-star-filled',
                'color' => 'yellow'
            ]);
            ?>
        </div>

        <!-- Crear publicación -->
        <div class="flavor-card flavor-crear-publicacion">
            <form id="form-nueva-publicacion" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_crear_publicacion', 'publicacion_nonce'); ?>
                <div class="crear-header">
                    <?php echo get_avatar(get_current_user_id(), 40); ?>
                    <textarea name="contenido" placeholder="¿Qué quieres compartir?" rows="3"></textarea>
                </div>
                <div class="crear-footer">
                    <div class="crear-opciones">
                        <label class="opcion-adjunto">
                            <span class="dashicons dashicons-format-image"></span>
                            <input type="file" name="imagen" accept="image/*" style="display:none;">
                        </label>
                        <label class="opcion-hashtag">
                            <span class="dashicons dashicons-tag"></span>
                        </label>
                    </div>
                    <button type="submit" class="flavor-btn flavor-btn-primary">Publicar</button>
                </div>
            </form>
        </div>

        <!-- Feed de publicaciones -->
        <div class="flavor-feed-publicaciones">
            <?php if (empty($datos['publicaciones'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-format-status"></span>
                    <p>Tu feed está vacío. ¡Sigue a otros usuarios para ver sus publicaciones!</p>
                    <a href="?tab=red-social&subtab=amigos" class="flavor-btn">Explorar conexiones</a>
                </div>
            <?php else: ?>
                <?php foreach ($datos['publicaciones'] as $publicacion): ?>
                    <div class="flavor-publicacion" data-id="<?php echo esc_attr($publicacion->id); ?>">
                        <div class="publicacion-header">
                            <?php echo get_avatar($publicacion->usuario_id, 40); ?>
                            <div class="publicacion-meta">
                                <strong><?php echo esc_html($publicacion->usuario_nombre); ?></strong>
                                <span class="fecha"><?php echo human_time_diff(strtotime($publicacion->created_at)); ?></span>
                            </div>
                        </div>
                        <div class="publicacion-contenido">
                            <?php echo wp_kses_post($publicacion->contenido); ?>
                            <?php if (!empty($publicacion->imagen)): ?>
                                <img src="<?php echo esc_url($publicacion->imagen); ?>" alt="" class="publicacion-imagen">
                            <?php endif; ?>
                        </div>
                        <div class="publicacion-acciones">
                            <button class="accion-reaccion <?php echo $publicacion->mi_reaccion ? 'activa' : ''; ?>"
                                    data-id="<?php echo $publicacion->id; ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <span class="count"><?php echo $publicacion->total_reacciones; ?></span>
                            </button>
                            <button class="accion-comentario" data-id="<?php echo $publicacion->id; ?>">
                                <span class="dashicons dashicons-admin-comments"></span>
                                <span class="count"><?php echo $publicacion->total_comentarios; ?></span>
                            </button>
                            <button class="accion-compartir" data-id="<?php echo $publicacion->id; ?>">
                                <span class="dashicons dashicons-share"></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_perfil($datos) {
        $perfil = $datos['perfil'];
        ?>
        <div class="flavor-perfil-completo">
            <div class="perfil-cabecera">
                <div class="perfil-avatar">
                    <?php echo get_avatar(get_current_user_id(), 120); ?>
                    <button class="cambiar-avatar" title="Cambiar foto">
                        <span class="dashicons dashicons-camera"></span>
                    </button>
                </div>
                <div class="perfil-info">
                    <h2><?php echo esc_html($perfil->display_name); ?></h2>
                    <p class="perfil-bio"><?php echo esc_html($perfil->bio ?? 'Sin biografía'); ?></p>
                    <div class="perfil-stats">
                        <span><strong><?php echo $datos['total_publicaciones']; ?></strong> publicaciones</span>
                        <span><strong><?php echo $datos['seguidores']; ?></strong> seguidores</span>
                        <span><strong><?php echo $datos['siguiendo']; ?></strong> siguiendo</span>
                    </div>
                </div>
            </div>

            <!-- Badges -->
            <?php if (!empty($datos['badges'])): ?>
            <div class="perfil-badges">
                <h3>Insignias obtenidas</h3>
                <div class="badges-grid">
                    <?php foreach ($datos['badges'] as $badge): ?>
                        <div class="badge-item" title="<?php echo esc_attr($badge->descripcion); ?>">
                            <span class="badge-icon"><?php echo $badge->icono; ?></span>
                            <span class="badge-nombre"><?php echo esc_html($badge->nombre); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Editar perfil -->
            <div class="perfil-editar">
                <h3>Editar información</h3>
                <form id="form-editar-perfil" method="post">
                    <?php wp_nonce_field('flavor_editar_perfil_social', 'perfil_nonce'); ?>
                    <div class="form-group">
                        <label>Nombre para mostrar</label>
                        <input type="text" name="display_name" value="<?php echo esc_attr($perfil->display_name); ?>">
                    </div>
                    <div class="form-group">
                        <label>Biografía</label>
                        <textarea name="bio" rows="3" maxlength="160"><?php echo esc_textarea($perfil->bio ?? ''); ?></textarea>
                        <small>Máximo 160 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label>Ubicación</label>
                        <input type="text" name="ubicacion" value="<?php echo esc_attr($perfil->ubicacion ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Sitio web</label>
                        <input type="url" name="sitio_web" value="<?php echo esc_url($perfil->sitio_web ?? ''); ?>">
                    </div>
                    <button type="submit" class="flavor-btn flavor-btn-primary">Guardar cambios</button>
                </form>
            </div>
        </div>
        <?php
    }

    private function render_amigos($datos) {
        ?>
        <div class="flavor-conexiones">
            <!-- Solicitudes pendientes -->
            <?php if (!empty($datos['solicitudes'])): ?>
            <div class="conexiones-seccion">
                <h3>Solicitudes de seguimiento</h3>
                <div class="lista-solicitudes">
                    <?php foreach ($datos['solicitudes'] as $solicitud): ?>
                        <div class="solicitud-item" data-id="<?php echo $solicitud->id; ?>">
                            <?php echo get_avatar($solicitud->seguidor_id, 50); ?>
                            <div class="solicitud-info">
                                <strong><?php echo esc_html($solicitud->nombre); ?></strong>
                                <span class="fecha"><?php echo human_time_diff(strtotime($solicitud->created_at)); ?></span>
                            </div>
                            <div class="solicitud-acciones">
                                <button class="flavor-btn flavor-btn-sm flavor-btn-primary aceptar-solicitud">Aceptar</button>
                                <button class="flavor-btn flavor-btn-sm rechazar-solicitud">Rechazar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mis conexiones -->
            <div class="conexiones-seccion">
                <h3>Personas que sigues (<?php echo count($datos['siguiendo_lista']); ?>)</h3>
                <?php if (empty($datos['siguiendo_lista'])): ?>
                    <p class="flavor-empty-state">Aún no sigues a nadie</p>
                <?php else: ?>
                    <div class="lista-conexiones">
                        <?php foreach ($datos['siguiendo_lista'] as $usuario): ?>
                            <div class="conexion-item">
                                <?php echo get_avatar($usuario->seguido_id, 50); ?>
                                <div class="conexion-info">
                                    <strong><?php echo esc_html($usuario->nombre); ?></strong>
                                    <span><?php echo $usuario->publicaciones_count; ?> publicaciones</span>
                                </div>
                                <button class="flavor-btn flavor-btn-sm dejar-seguir" data-id="<?php echo $usuario->seguido_id; ?>">
                                    Dejar de seguir
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sugerencias -->
            <?php if (!empty($datos['sugerencias'])): ?>
            <div class="conexiones-seccion">
                <h3>Personas que quizás conozcas</h3>
                <div class="lista-sugerencias">
                    <?php foreach ($datos['sugerencias'] as $sugerencia): ?>
                        <div class="sugerencia-item">
                            <?php echo get_avatar($sugerencia->ID, 50); ?>
                            <div class="sugerencia-info">
                                <strong><?php echo esc_html($sugerencia->display_name); ?></strong>
                                <span><?php echo $sugerencia->seguidores_comun; ?> conexiones en común</span>
                            </div>
                            <button class="flavor-btn flavor-btn-sm flavor-btn-primary seguir-usuario" data-id="<?php echo $sugerencia->ID; ?>">
                                Seguir
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_actividad($datos) {
        ?>
        <div class="flavor-mi-actividad">
            <h3>Mi actividad reciente</h3>

            <?php if (empty($datos['actividad'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-clock"></span>
                    <p>No hay actividad reciente</p>
                </div>
            <?php else: ?>
                <div class="timeline-actividad">
                    <?php foreach ($datos['actividad'] as $item): ?>
                        <div class="actividad-item">
                            <div class="actividad-icono">
                                <span class="dashicons <?php echo $this->get_icono_actividad($item->tipo); ?>"></span>
                            </div>
                            <div class="actividad-contenido">
                                <p><?php echo $this->get_texto_actividad($item); ?></p>
                                <span class="fecha"><?php echo human_time_diff(strtotime($item->created_at)); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_reputacion($datos) {
        $nivel = $this->calcular_nivel($datos['puntos_reputacion']);
        ?>
        <div class="flavor-reputacion">
            <!-- Nivel actual -->
            <div class="reputacion-nivel">
                <div class="nivel-badge nivel-<?php echo $nivel['slug']; ?>">
                    <span class="nivel-icono"><?php echo $nivel['icono']; ?></span>
                    <span class="nivel-nombre"><?php echo $nivel['nombre']; ?></span>
                </div>
                <div class="nivel-progreso">
                    <div class="progreso-barra">
                        <div class="progreso-fill" style="width: <?php echo $nivel['progreso']; ?>%"></div>
                    </div>
                    <p><?php echo number_format($datos['puntos_reputacion']); ?> / <?php echo number_format($nivel['siguiente']); ?> puntos para el siguiente nivel</p>
                </div>
            </div>

            <!-- Cómo ganar puntos -->
            <div class="reputacion-info">
                <h3>Cómo ganar puntos</h3>
                <div class="puntos-acciones">
                    <div class="punto-item">
                        <span class="dashicons dashicons-edit"></span>
                        <span>Publicación: +10 pts</span>
                    </div>
                    <div class="punto-item">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <span>Comentario: +5 pts</span>
                    </div>
                    <div class="punto-item">
                        <span class="dashicons dashicons-heart"></span>
                        <span>Recibir reacción: +2 pts</span>
                    </div>
                    <div class="punto-item">
                        <span class="dashicons dashicons-groups"></span>
                        <span>Nuevo seguidor: +15 pts</span>
                    </div>
                </div>
            </div>

            <!-- Historial reciente -->
            <?php if (!empty($datos['historial_puntos'])): ?>
            <div class="reputacion-historial">
                <h3>Últimos puntos ganados</h3>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th>Acción</th>
                            <th>Puntos</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['historial_puntos'] as $registro): ?>
                            <tr>
                                <td><?php echo esc_html($registro->descripcion); ?></td>
                                <td class="puntos-<?php echo $registro->puntos > 0 ? 'positivo' : 'negativo'; ?>">
                                    <?php echo ($registro->puntos > 0 ? '+' : '') . $registro->puntos; ?>
                                </td>
                                <td><?php echo date_i18n('j M Y', strtotime($registro->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Ranking -->
            <?php if (!empty($datos['ranking'])): ?>
            <div class="reputacion-ranking">
                <h3>Ranking de la comunidad</h3>
                <div class="ranking-lista">
                    <?php foreach ($datos['ranking'] as $posicion => $usuario): ?>
                        <div class="ranking-item <?php echo $usuario->ID == get_current_user_id() ? 'es-yo' : ''; ?>">
                            <span class="posicion">#<?php echo $posicion + 1; ?></span>
                            <?php echo get_avatar($usuario->ID, 32); ?>
                            <span class="nombre"><?php echo esc_html($usuario->display_name); ?></span>
                            <span class="puntos"><?php echo number_format($usuario->puntos_reputacion); ?> pts</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
        $tabla_reputacion = $wpdb->prefix . 'flavor_social_reputacion';
        $tabla_badges = $wpdb->prefix . 'flavor_social_usuario_badges';
        $tabla_historial = $wpdb->prefix . 'flavor_social_historial_puntos';
        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        // Estadísticas básicas
        $total_publicaciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_publicaciones WHERE usuario_id = %d AND estado = 'publicado'",
            $user_id
        ));

        $seguidores = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_seguimientos WHERE seguido_id = %d AND estado = 'aceptado'",
            $user_id
        ));

        $siguiendo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_seguimientos WHERE seguidor_id = %d AND estado = 'aceptado'",
            $user_id
        ));

        $puntos_reputacion = $wpdb->get_var($wpdb->prepare(
            "SELECT puntos_total FROM $tabla_reputacion WHERE usuario_id = %d",
            $user_id
        )) ?: 0;

        // Solicitudes pendientes
        $solicitudes_pendientes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_seguimientos WHERE seguido_id = %d AND estado = 'pendiente'",
            $user_id
        ));

        // Publicaciones del feed
        $publicaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name as usuario_nombre,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_reacciones WHERE publicacion_id = p.id) as total_reacciones,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_comentarios WHERE publicacion_id = p.id) as total_comentarios,
                    (SELECT id FROM {$wpdb->prefix}flavor_social_reacciones WHERE publicacion_id = p.id AND usuario_id = %d LIMIT 1) as mi_reaccion
             FROM $tabla_publicaciones p
             JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             WHERE p.usuario_id IN (
                 SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d AND estado = 'aceptado'
             ) OR p.usuario_id = %d
             AND p.estado = 'publicado'
             ORDER BY p.created_at DESC
             LIMIT 20",
            $user_id, $user_id, $user_id
        ));

        // Perfil
        $perfil = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, u.display_name
             FROM $tabla_perfiles p
             JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             WHERE p.usuario_id = %d",
            $user_id
        ));

        if (!$perfil) {
            $user = get_userdata($user_id);
            $perfil = (object)[
                'display_name' => $user->display_name,
                'bio' => '',
                'ubicacion' => '',
                'sitio_web' => ''
            ];
        }

        // Badges
        $badges = $wpdb->get_results($wpdb->prepare(
            "SELECT b.* FROM $tabla_badges ub
             JOIN {$wpdb->prefix}flavor_social_badges b ON ub.badge_id = b.id
             WHERE ub.usuario_id = %d
             ORDER BY ub.obtenido_at DESC",
            $user_id
        ));

        // Lista de siguiendo
        $siguiendo_lista = $wpdb->get_results($wpdb->prepare(
            "SELECT s.seguido_id, u.display_name as nombre,
                    (SELECT COUNT(*) FROM $tabla_publicaciones WHERE usuario_id = s.seguido_id) as publicaciones_count
             FROM $tabla_seguimientos s
             JOIN {$wpdb->users} u ON s.seguido_id = u.ID
             WHERE s.seguidor_id = %d AND s.estado = 'aceptado'
             ORDER BY s.created_at DESC",
            $user_id
        ));

        // Solicitudes recibidas
        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as nombre
             FROM $tabla_seguimientos s
             JOIN {$wpdb->users} u ON s.seguidor_id = u.ID
             WHERE s.seguido_id = %d AND s.estado = 'pendiente'
             ORDER BY s.created_at DESC",
            $user_id
        ));

        // Historial de puntos
        $historial_puntos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_historial WHERE usuario_id = %d ORDER BY created_at DESC LIMIT 10",
            $user_id
        ));

        // Ranking
        $ranking = $wpdb->get_results(
            "SELECT r.usuario_id as ID, r.puntos_total as puntos_reputacion, u.display_name
             FROM $tabla_reputacion r
             JOIN {$wpdb->users} u ON r.usuario_id = u.ID
             ORDER BY r.puntos_total DESC
             LIMIT 10"
        );

        // Actividad reciente
        $actividad = $wpdb->get_results($wpdb->prepare(
            "SELECT 'publicacion' as tipo, id, contenido as descripcion, created_at
             FROM $tabla_publicaciones WHERE usuario_id = %d
             UNION ALL
             SELECT 'comentario' as tipo, id, contenido as descripcion, created_at
             FROM {$wpdb->prefix}flavor_social_comentarios WHERE usuario_id = %d
             ORDER BY created_at DESC LIMIT 20",
            $user_id, $user_id
        ));

        // Sugerencias (usuarios con más seguidores en común)
        $sugerencias = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name, COUNT(*) as seguidores_comun
             FROM {$wpdb->users} u
             JOIN $tabla_seguimientos s1 ON u.ID = s1.seguido_id
             JOIN $tabla_seguimientos s2 ON s1.seguidor_id = s2.seguidor_id
             WHERE s2.seguido_id IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
             AND u.ID != %d
             AND u.ID NOT IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
             GROUP BY u.ID
             ORDER BY seguidores_comun DESC
             LIMIT 5",
            $user_id, $user_id, $user_id
        ));

        return [
            'total_publicaciones' => $total_publicaciones ?: 0,
            'seguidores' => $seguidores ?: 0,
            'siguiendo' => $siguiendo ?: 0,
            'puntos_reputacion' => $puntos_reputacion,
            'solicitudes_pendientes' => $solicitudes_pendientes ?: 0,
            'publicaciones' => $publicaciones ?: [],
            'perfil' => $perfil,
            'badges' => $badges ?: [],
            'siguiendo_lista' => $siguiendo_lista ?: [],
            'solicitudes' => $solicitudes ?: [],
            'historial_puntos' => $historial_puntos ?: [],
            'ranking' => $ranking ?: [],
            'actividad' => $actividad ?: [],
            'sugerencias' => $sugerencias ?: [],
        ];
    }

    private function calcular_nivel($puntos) {
        $niveles = [
            ['slug' => 'nuevo', 'nombre' => 'Nuevo', 'min' => 0, 'max' => 100, 'icono' => '🌱'],
            ['slug' => 'activo', 'nombre' => 'Activo', 'min' => 100, 'max' => 500, 'icono' => '🌿'],
            ['slug' => 'contribuidor', 'nombre' => 'Contribuidor', 'min' => 500, 'max' => 1500, 'icono' => '🌳'],
            ['slug' => 'experto', 'nombre' => 'Experto', 'min' => 1500, 'max' => 5000, 'icono' => '⭐'],
            ['slug' => 'lider', 'nombre' => 'Líder', 'min' => 5000, 'max' => 15000, 'icono' => '🏆'],
            ['slug' => 'embajador', 'nombre' => 'Embajador', 'min' => 15000, 'max' => 50000, 'icono' => '👑'],
            ['slug' => 'leyenda', 'nombre' => 'Leyenda', 'min' => 50000, 'max' => PHP_INT_MAX, 'icono' => '💎'],
        ];

        foreach ($niveles as $nivel) {
            if ($puntos >= $nivel['min'] && $puntos < $nivel['max']) {
                $progreso = (($puntos - $nivel['min']) / ($nivel['max'] - $nivel['min'])) * 100;
                return array_merge($nivel, [
                    'progreso' => min(100, $progreso),
                    'siguiente' => $nivel['max']
                ]);
            }
        }

        return $niveles[0];
    }

    private function get_icono_actividad($tipo) {
        $iconos = [
            'publicacion' => 'dashicons-edit',
            'comentario' => 'dashicons-admin-comments',
            'reaccion' => 'dashicons-heart',
            'seguimiento' => 'dashicons-groups',
        ];
        return $iconos[$tipo] ?? 'dashicons-marker';
    }

    private function get_texto_actividad($item) {
        switch ($item->tipo) {
            case 'publicacion':
                return 'Publicaste: "' . wp_trim_words($item->descripcion, 10) . '"';
            case 'comentario':
                return 'Comentaste: "' . wp_trim_words($item->descripcion, 10) . '"';
            default:
                return $item->descripcion;
        }
    }
}

Flavor_Red_Social_Dashboard_Tab::get_instance();
