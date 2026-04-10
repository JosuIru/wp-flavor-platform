<?php
/**
 * Tab de Dashboard para Comunidades
 *
 * Muestra las comunidades del usuario, su actividad reciente y notificaciones.
 *
 * @package FlavorPlatform
 * @subpackage Comunidades
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar las tabs del dashboard de usuario para el módulo Comunidades
 */
class Flavor_Comunidades_Dashboard_Tab {

    /**
     * Instancia singleton
     *
     * @var Flavor_Comunidades_Dashboard_Tab|null
     */
    private static $instance = null;

    /**
     * Referencia al módulo de comunidades
     *
     * @var Flavor_Platform_Module_Interface|null
     */
    private $module = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Comunidades_Dashboard_Tab
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);
        add_action('wp_ajax_comunidades_dashboard_actividad', [$this, 'ajax_cargar_actividad']);
        add_action('wp_ajax_comunidades_dashboard_notificaciones', [$this, 'ajax_cargar_notificaciones']);
    }

    /**
     * Establece la referencia al módulo
     *
     * @param Flavor_Platform_Module_Interface $module Instancia del módulo
     */
    public function set_module($module) {
        $this->module = $module;
    }

    /**
     * Registra las tabs del dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs con las de comunidades añadidas
     */
    public function registrar_tabs($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        $tabs['comunidades-mis-comunidades'] = [
            'titulo'      => __('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'       => 'dashicons-groups',
            'callback'    => [$this, 'render_tab_mis_comunidades'],
            'orden'       => 30,
            'badge'       => $this->obtener_contador_comunidades(),
            'descripcion' => __('Comunidades donde eres miembro', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        $tabs['comunidades-mi-actividad'] = [
            'titulo'      => __('Mi Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'       => 'dashicons-backup',
            'callback'    => [$this, 'render_tab_mi_actividad'],
            'orden'       => 31,
            'badge'       => null,
            'descripcion' => __('Tu actividad reciente en comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        $tabs['comunidades-notificaciones'] = [
            'titulo'      => __('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'       => 'dashicons-bell',
            'callback'    => [$this, 'render_tab_notificaciones'],
            'orden'       => 32,
            'badge'       => $this->obtener_contador_notificaciones_no_leidas(),
            'descripcion' => __('Notificaciones de tus comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $tabs;
    }

    /**
     * Obtiene el contador de comunidades del usuario
     *
     * @return int|null
     */
    private function obtener_contador_comunidades() {
        global $wpdb;
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return null;
        }

        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_miembros)) {
            return null;
        }

        $contador = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_miembros WHERE user_id = %d AND estado = 'activo'",
            $usuario_actual_id
        ));

        return $contador > 0 ? $contador : null;
    }

    /**
     * Obtiene el contador de notificaciones no leídas
     *
     * @return int|null
     */
    private function obtener_contador_notificaciones_no_leidas() {
        global $wpdb;
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return null;
        }

        $tabla_notificaciones = $wpdb->prefix . 'flavor_notificaciones';

        // Si existe la tabla de notificaciones centralizada
        if (Flavor_Platform_Helpers::tabla_existe($tabla_notificaciones)) {
            $tipos_comunidad = ['nueva_publicacion', 'nuevo_evento', 'nuevo_miembro', 'recurso_compartido', 'mencion', 'crosspost'];
            $tipos_placeholder = implode("','", array_map('esc_sql', $tipos_comunidad));

            $contador = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_notificaciones
                 WHERE user_id = %d AND leida = 0 AND tipo IN ('$tipos_placeholder')",
                $usuario_actual_id
            ));
        } else {
            // Usar meta de usuario como fallback
            $notificaciones_meta = get_user_meta($usuario_actual_id, 'flavor_comunidades_notificaciones_no_leidas', true);
            $contador = is_numeric($notificaciones_meta) ? (int) $notificaciones_meta : 0;
        }

        return $contador > 0 ? $contador : null;
    }

    /**
     * Renderiza la tab de Mis Comunidades
     */
    public function render_tab_mis_comunidades() {
        global $wpdb;
        $usuario_actual_id = get_current_user_id();

        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_miembros)) {
            echo '<div class="flavor-dashboard-empty">';
            echo '<span class="dashicons dashicons-warning"></span>';
            echo '<p>' . __('El módulo de comunidades no está configurado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            echo '</div>';
            return;
        }

        // Obtener comunidades donde el usuario es miembro
        $comunidades_del_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.rol AS mi_rol, m.joined_at AS fecha_union
             FROM $tabla_comunidades c
             INNER JOIN $tabla_miembros m ON c.id = m.comunidad_id
             WHERE m.user_id = %d AND m.estado = 'activo'
             ORDER BY m.joined_at DESC",
            $usuario_actual_id
        ));

        ?>
        <div class="flavor-dashboard-comunidades">
            <div class="flavor-dashboard-section-header">
                <h3><?php _e('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('comunidades', '')); ?>" class="flavor-btn-secondary flavor-btn-sm">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Explorar más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($comunidades_del_usuario)) : ?>
                <div class="flavor-dashboard-empty">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php _e('Aún no perteneces a ninguna comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('comunidades', '')); ?>" class="flavor-btn-primary">
                        <?php _e('Explorar comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="flavor-comunidades-grid">
                    <?php foreach ($comunidades_del_usuario as $comunidad) : ?>
                        <?php
                        $creador_info = get_userdata($comunidad->creador_id);
                        $nombre_creador = $creador_info ? $creador_info->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
                        $rol_badge_class = 'flavor-rol-' . sanitize_html_class($comunidad->mi_rol);
                        $imagen_comunidad = !empty($comunidad->imagen) ? $comunidad->imagen : '';
                        ?>
                        <div class="flavor-comunidad-card" data-comunidad-id="<?php echo esc_attr($comunidad->id); ?>">
                            <?php if ($imagen_comunidad) : ?>
                                <div class="flavor-comunidad-imagen" style="background-image: url('<?php echo esc_url($imagen_comunidad); ?>');"></div>
                            <?php else : ?>
                                <div class="flavor-comunidad-imagen flavor-comunidad-imagen-placeholder">
                                    <span class="dashicons dashicons-groups"></span>
                                </div>
                            <?php endif; ?>

                            <div class="flavor-comunidad-content">
                                <div class="flavor-comunidad-header">
                                    <h4 class="flavor-comunidad-nombre">
                                        <a href="<?php echo esc_url(add_query_arg('comunidad_id', $comunidad->id, Flavor_Platform_Helpers::get_action_url('comunidades', 'detalle'))); ?>">
                                            <?php echo esc_html($comunidad->nombre); ?>
                                        </a>
                                    </h4>
                                    <span class="flavor-comunidad-rol <?php echo esc_attr($rol_badge_class); ?>">
                                        <?php echo esc_html(ucfirst($comunidad->mi_rol)); ?>
                                    </span>
                                </div>

                                <p class="flavor-comunidad-descripcion">
                                    <?php echo esc_html(wp_trim_words($comunidad->descripcion, 15, '...')); ?>
                                </p>

                                <div class="flavor-comunidad-meta">
                                    <span class="flavor-comunidad-tipo" title="<?php esc_attr_e('Tipo de comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-<?php echo $comunidad->tipo === 'abierta' ? 'unlock' : 'lock'; ?>"></span>
                                        <?php echo esc_html(ucfirst($comunidad->tipo)); ?>
                                    </span>
                                    <span class="flavor-comunidad-miembros" title="<?php esc_attr_e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <?php echo esc_html($comunidad->miembros_count); ?>
                                    </span>
                                    <span class="flavor-comunidad-categoria" title="<?php esc_attr_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-category"></span>
                                        <?php echo esc_html(ucfirst($comunidad->categoria)); ?>
                                    </span>
                                </div>

                                <div class="flavor-comunidad-footer">
                                    <span class="flavor-comunidad-fecha-union">
                                        <?php
                                        printf(
                                            __('Miembro desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                            date_i18n(get_option('date_format'), strtotime($comunidad->fecha_union))
                                        );
                                        ?>
                                    </span>
                                    <a href="<?php echo esc_url(add_query_arg('comunidad_id', $comunidad->id, Flavor_Platform_Helpers::get_action_url('comunidades', 'detalle'))); ?>" class="flavor-btn-link">
                                        <?php _e('Ver comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php
                // Estadísticas rápidas del usuario
                $estadisticas = $this->obtener_estadisticas_usuario($usuario_actual_id);
                if ($estadisticas) :
                ?>
                    <div class="flavor-comunidades-stats">
                        <h4><?php _e('Tu participación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <div class="flavor-stats-grid-mini">
                            <div class="flavor-stat-item">
                                <span class="flavor-stat-number"><?php echo esc_html($estadisticas['comunidades_total']); ?></span>
                                <span class="flavor-stat-label"><?php _e('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="flavor-stat-item">
                                <span class="flavor-stat-number"><?php echo esc_html($estadisticas['publicaciones_total']); ?></span>
                                <span class="flavor-stat-label"><?php _e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="flavor-stat-item">
                                <span class="flavor-stat-number"><?php echo esc_html($estadisticas['roles_admin']); ?></span>
                                <span class="flavor-stat-label"><?php _e('Como Admin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <style>
            .flavor-dashboard-comunidades { padding: 10px 0; }
            .flavor-dashboard-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .flavor-dashboard-section-header h3 { margin: 0; font-size: 18px; }
            .flavor-comunidades-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
            .flavor-comunidad-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; transition: box-shadow 0.2s, transform 0.2s; }
            .flavor-comunidad-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
            .flavor-comunidad-imagen { height: 120px; background-size: cover; background-position: center; background-color: #f0f0f0; }
            .flavor-comunidad-imagen-placeholder { display: flex; align-items: center; justify-content: center; }
            .flavor-comunidad-imagen-placeholder .dashicons { font-size: 48px; color: #ccc; width: 48px; height: 48px; }
            .flavor-comunidad-content { padding: 15px; }
            .flavor-comunidad-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
            .flavor-comunidad-nombre { margin: 0; font-size: 16px; line-height: 1.3; }
            .flavor-comunidad-nombre a { color: #1d2327; text-decoration: none; }
            .flavor-comunidad-nombre a:hover { color: #2271b1; }
            .flavor-comunidad-rol { font-size: 11px; padding: 3px 8px; border-radius: 12px; background: #e0e0e0; color: #666; white-space: nowrap; }
            .flavor-rol-admin { background: #d63638; color: #fff; }
            .flavor-rol-moderador { background: #dba617; color: #fff; }
            .flavor-rol-miembro { background: #2271b1; color: #fff; }
            .flavor-comunidad-descripcion { font-size: 13px; color: #666; margin: 0 0 12px; line-height: 1.5; }
            .flavor-comunidad-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 12px; color: #888; margin-bottom: 12px; }
            .flavor-comunidad-meta span { display: flex; align-items: center; gap: 4px; }
            .flavor-comunidad-meta .dashicons { font-size: 14px; width: 14px; height: 14px; }
            .flavor-comunidad-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #f0f0f0; }
            .flavor-comunidad-fecha-union { font-size: 11px; color: #999; }
            .flavor-btn-link { font-size: 13px; color: #2271b1; text-decoration: none; display: flex; align-items: center; gap: 4px; }
            .flavor-btn-link:hover { color: #135e96; }
            .flavor-btn-link .dashicons { font-size: 16px; width: 16px; height: 16px; }
            .flavor-dashboard-empty { text-align: center; padding: 40px 20px; background: #f8f9fa; border-radius: 12px; }
            .flavor-dashboard-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #ccc; margin-bottom: 15px; }
            .flavor-dashboard-empty p { color: #666; margin: 0 0 20px; }
            .flavor-btn-primary { display: inline-block; padding: 10px 20px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 6px; font-size: 14px; }
            .flavor-btn-primary:hover { background: #135e96; color: #fff; }
            .flavor-btn-secondary { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f0f0f0; color: #1d2327; text-decoration: none; border-radius: 6px; font-size: 13px; }
            .flavor-btn-secondary:hover { background: #e0e0e0; color: #1d2327; }
            .flavor-btn-secondary .dashicons { font-size: 16px; width: 16px; height: 16px; }
            .flavor-btn-sm { padding: 5px 10px; font-size: 12px; }
            .flavor-comunidades-stats { margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 12px; }
            .flavor-comunidades-stats h4 { margin: 0 0 15px; font-size: 14px; color: #666; }
            .flavor-stats-grid-mini { display: flex; gap: 30px; }
            .flavor-stat-item { text-align: center; }
            .flavor-stat-number { display: block; font-size: 24px; font-weight: 600; color: #2271b1; }
            .flavor-stat-label { font-size: 12px; color: #888; }
            @media (max-width: 600px) {
                .flavor-comunidades-grid { grid-template-columns: 1fr; }
                .flavor-dashboard-section-header { flex-direction: column; gap: 10px; align-items: flex-start; }
            }
        </style>
        <?php
    }

    /**
     * Renderiza la tab de Mi Actividad
     */
    public function render_tab_mi_actividad() {
        global $wpdb;
        $usuario_actual_id = get_current_user_id();

        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            echo '<div class="flavor-dashboard-empty">';
            echo '<span class="dashicons dashicons-warning"></span>';
            echo '<p>' . __('El módulo de comunidades no está configurado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            echo '</div>';
            return;
        }

        // Obtener actividad del usuario en todas sus comunidades
        $actividades_del_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, c.nombre AS comunidad_nombre, c.slug AS comunidad_slug, c.imagen AS comunidad_imagen
             FROM $tabla_actividad a
             INNER JOIN $tabla_comunidades c ON a.comunidad_id = c.id
             WHERE a.user_id = %d
             ORDER BY a.created_at DESC
             LIMIT 30",
            $usuario_actual_id
        ));

        // Obtener actividad reciente de las comunidades del usuario (de otros miembros)
        $actividad_comunidades = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, c.nombre AS comunidad_nombre, c.slug AS comunidad_slug, u.display_name AS autor_nombre
             FROM $tabla_actividad a
             INNER JOIN $tabla_comunidades c ON a.comunidad_id = c.id
             INNER JOIN $tabla_miembros m ON m.comunidad_id = c.id AND m.user_id = %d AND m.estado = 'activo'
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.user_id != %d
             ORDER BY a.created_at DESC
             LIMIT 20",
            $usuario_actual_id,
            $usuario_actual_id
        ));

        ?>
        <div class="flavor-dashboard-actividad">
            <!-- Mis publicaciones -->
            <div class="flavor-actividad-section">
                <div class="flavor-dashboard-section-header">
                    <h3><?php _e('Mis publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>

                <?php if (empty($actividades_del_usuario)) : ?>
                    <div class="flavor-dashboard-empty flavor-empty-sm">
                        <span class="dashicons dashicons-edit"></span>
                        <p><?php _e('Aún no has publicado nada en tus comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <?php else : ?>
                    <div class="flavor-actividad-lista">
                        <?php foreach ($actividades_del_usuario as $actividad) : ?>
                            <div class="flavor-actividad-item" data-actividad-id="<?php echo esc_attr($actividad->id); ?>">
                                <div class="flavor-actividad-icono">
                                    <?php echo $this->obtener_icono_tipo_actividad($actividad->tipo); ?>
                                </div>
                                <div class="flavor-actividad-content">
                                    <div class="flavor-actividad-header">
                                        <span class="flavor-actividad-tipo-badge tipo-<?php echo esc_attr($actividad->tipo); ?>">
                                            <?php echo esc_html($this->obtener_label_tipo_actividad($actividad->tipo)); ?>
                                        </span>
                                        <span class="flavor-actividad-fecha">
                                            <?php echo esc_html(human_time_diff(strtotime($actividad->created_at), current_time('timestamp'))); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($actividad->titulo)) : ?>
                                        <h4 class="flavor-actividad-titulo"><?php echo esc_html($actividad->titulo); ?></h4>
                                    <?php endif; ?>
                                    <p class="flavor-actividad-texto">
                                        <?php echo esc_html(wp_trim_words($actividad->contenido, 25, '...')); ?>
                                    </p>
                                    <div class="flavor-actividad-meta">
                                        <a href="<?php echo esc_url(add_query_arg('comunidad_id', $actividad->comunidad_id, Flavor_Platform_Helpers::get_action_url('comunidades', 'detalle'))); ?>" class="flavor-actividad-comunidad">
                                            <span class="dashicons dashicons-groups"></span>
                                            <?php echo esc_html($actividad->comunidad_nombre); ?>
                                        </a>
                                        <span class="flavor-actividad-stats">
                                            <span title="<?php esc_attr_e('Reacciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                <span class="dashicons dashicons-heart"></span>
                                                <?php echo esc_html($actividad->reacciones_count ?? 0); ?>
                                            </span>
                                            <span title="<?php esc_attr_e('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                <span class="dashicons dashicons-admin-comments"></span>
                                                <?php echo esc_html($actividad->comentarios_count ?? 0); ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Feed de comunidades -->
            <div class="flavor-actividad-section">
                <div class="flavor-dashboard-section-header">
                    <h3><?php _e('Actividad reciente de mis comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>

                <?php if (empty($actividad_comunidades)) : ?>
                    <div class="flavor-dashboard-empty flavor-empty-sm">
                        <span class="dashicons dashicons-rss"></span>
                        <p><?php _e('No hay actividad reciente en tus comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <?php else : ?>
                    <div class="flavor-feed-lista">
                        <?php foreach ($actividad_comunidades as $entrada) : ?>
                            <div class="flavor-feed-item">
                                <div class="flavor-feed-avatar">
                                    <?php echo get_avatar($entrada->user_id, 40); ?>
                                </div>
                                <div class="flavor-feed-content">
                                    <div class="flavor-feed-header">
                                        <strong><?php echo esc_html($entrada->autor_nombre ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                        <span class="flavor-feed-accion">
                                            <?php echo esc_html($this->obtener_texto_accion($entrada->tipo)); ?>
                                        </span>
                                        <a href="<?php echo esc_url(add_query_arg('comunidad_id', $entrada->comunidad_id, Flavor_Platform_Helpers::get_action_url('comunidades', 'detalle'))); ?>">
                                            <?php echo esc_html($entrada->comunidad_nombre); ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($entrada->titulo)) : ?>
                                        <p class="flavor-feed-titulo"><?php echo esc_html($entrada->titulo); ?></p>
                                    <?php endif; ?>
                                    <span class="flavor-feed-fecha">
                                        <?php echo esc_html(human_time_diff(strtotime($entrada->created_at), current_time('timestamp'))); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .flavor-dashboard-actividad { display: flex; flex-direction: column; gap: 30px; padding: 10px 0; }
            .flavor-actividad-section { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 20px; }
            .flavor-actividad-lista { display: flex; flex-direction: column; gap: 15px; }
            .flavor-actividad-item { display: flex; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; transition: background 0.2s; }
            .flavor-actividad-item:hover { background: #f0f2f4; }
            .flavor-actividad-icono { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #e0e0e0; border-radius: 50%; flex-shrink: 0; }
            .flavor-actividad-icono .dashicons { font-size: 20px; width: 20px; height: 20px; color: #666; }
            .flavor-actividad-content { flex: 1; min-width: 0; }
            .flavor-actividad-header { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
            .flavor-actividad-tipo-badge { font-size: 10px; padding: 2px 8px; border-radius: 10px; background: #e0e0e0; color: #666; text-transform: uppercase; }
            .flavor-actividad-tipo-badge.tipo-publicacion { background: #2271b1; color: #fff; }
            .flavor-actividad-tipo-badge.tipo-evento { background: #d63638; color: #fff; }
            .flavor-actividad-tipo-badge.tipo-anuncio { background: #dba617; color: #fff; }
            .flavor-actividad-tipo-badge.tipo-encuesta { background: #00a32a; color: #fff; }
            .flavor-actividad-fecha { font-size: 11px; color: #999; margin-left: auto; }
            .flavor-actividad-titulo { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: #1d2327; }
            .flavor-actividad-texto { margin: 0 0 8px; font-size: 13px; color: #666; line-height: 1.4; }
            .flavor-actividad-meta { display: flex; align-items: center; justify-content: space-between; font-size: 12px; }
            .flavor-actividad-comunidad { color: #2271b1; text-decoration: none; display: flex; align-items: center; gap: 4px; }
            .flavor-actividad-comunidad:hover { color: #135e96; }
            .flavor-actividad-comunidad .dashicons { font-size: 14px; width: 14px; height: 14px; }
            .flavor-actividad-stats { display: flex; gap: 12px; color: #888; }
            .flavor-actividad-stats span { display: flex; align-items: center; gap: 3px; }
            .flavor-actividad-stats .dashicons { font-size: 14px; width: 14px; height: 14px; }
            .flavor-feed-lista { display: flex; flex-direction: column; gap: 12px; }
            .flavor-feed-item { display: flex; gap: 12px; padding: 12px; border-bottom: 1px solid #f0f0f0; }
            .flavor-feed-item:last-child { border-bottom: none; }
            .flavor-feed-avatar img { border-radius: 50%; }
            .flavor-feed-content { flex: 1; }
            .flavor-feed-header { font-size: 13px; line-height: 1.4; }
            .flavor-feed-header a { color: #2271b1; text-decoration: none; }
            .flavor-feed-header a:hover { text-decoration: underline; }
            .flavor-feed-accion { color: #888; }
            .flavor-feed-titulo { margin: 4px 0 0; font-size: 13px; color: #1d2327; }
            .flavor-feed-fecha { font-size: 11px; color: #999; }
            .flavor-empty-sm { padding: 25px; }
            .flavor-empty-sm .dashicons { font-size: 36px; width: 36px; height: 36px; }
        </style>
        <?php
    }

    /**
     * Renderiza la tab de Notificaciones
     */
    public function render_tab_notificaciones() {
        global $wpdb;
        $usuario_actual_id = get_current_user_id();

        $tabla_notificaciones = $wpdb->prefix . 'flavor_notificaciones';

        // Obtener notificaciones de comunidades
        $notificaciones = [];

        if (Flavor_Platform_Helpers::tabla_existe($tabla_notificaciones)) {
            $tipos_comunidad = ['nueva_publicacion', 'nuevo_evento', 'nuevo_miembro', 'recurso_compartido', 'mencion', 'crosspost', 'comunidad_relacionada', 'evento_red'];
            $tipos_placeholder = implode("','", array_map('esc_sql', $tipos_comunidad));

            $notificaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_notificaciones
                 WHERE user_id = %d AND tipo IN ('$tipos_placeholder')
                 ORDER BY created_at DESC
                 LIMIT 50",
                $usuario_actual_id
            ));
        }

        // Fallback: obtener actividad reciente como notificaciones
        if (empty($notificaciones)) {
            $notificaciones = $this->generar_notificaciones_desde_actividad($usuario_actual_id);
        }

        ?>
        <div class="flavor-dashboard-notificaciones">
            <div class="flavor-dashboard-section-header">
                <h3><?php _e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <?php if (!empty($notificaciones)) : ?>
                    <button type="button" class="flavor-btn-secondary flavor-btn-sm" id="marcar-todas-leidas">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Marcar todas como leídas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (empty($notificaciones)) : ?>
                <div class="flavor-dashboard-empty">
                    <span class="dashicons dashicons-bell"></span>
                    <p><?php _e('No tienes notificaciones de comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else : ?>
                <div class="flavor-notificaciones-lista">
                    <?php foreach ($notificaciones as $notificacion) :
                        $es_leida = isset($notificacion->leida) ? (bool) $notificacion->leida : false;
                        $clase_leida = $es_leida ? 'leida' : 'no-leida';
                    ?>
                        <div class="flavor-notificacion-item <?php echo esc_attr($clase_leida); ?>" data-id="<?php echo esc_attr($notificacion->id ?? 0); ?>">
                            <div class="flavor-notificacion-icono">
                                <?php echo $this->obtener_icono_notificacion($notificacion->tipo ?? 'general'); ?>
                            </div>
                            <div class="flavor-notificacion-content">
                                <h4 class="flavor-notificacion-titulo">
                                    <?php echo esc_html($notificacion->titulo ?? __('Notificación', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </h4>
                                <p class="flavor-notificacion-mensaje">
                                    <?php echo esc_html($notificacion->mensaje ?? $notificacion->contenido ?? ''); ?>
                                </p>
                                <span class="flavor-notificacion-fecha">
                                    <?php
                                    $fecha_notificacion = $notificacion->created_at ?? $notificacion->fecha ?? '';
                                    if ($fecha_notificacion) {
                                        echo esc_html(human_time_diff(strtotime($fecha_notificacion), current_time('timestamp')));
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php if (!empty($notificacion->link) || !empty($notificacion->url)) : ?>
                                <a href="<?php echo esc_url($notificacion->link ?? $notificacion->url); ?>" class="flavor-notificacion-link">
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                </a>
                            <?php endif; ?>
                            <?php if (!$es_leida) : ?>
                                <span class="flavor-notificacion-indicador"></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Preferencias de notificaciones -->
            <div class="flavor-notificaciones-preferencias">
                <details>
                    <summary><?php _e('Preferencias de notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                    <div class="flavor-preferencias-form">
                        <?php
                        $preferencias = get_user_meta($usuario_actual_id, 'flavor_notificaciones_comunidades', true);
                        $preferencias = is_array($preferencias) ? $preferencias : [];

                        $opciones_notificacion = [
                            'nueva_publicacion' => __('Nuevas publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'nuevo_evento'      => __('Nuevos eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'nuevo_miembro'     => __('Nuevos miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'mencion'           => __('Menciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'crosspost'         => __('Contenido compartido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'contenido_federado' => __('Contenido de la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        ];

                        foreach ($opciones_notificacion as $clave => $etiqueta) :
                            $activa = !isset($preferencias[$clave]) || $preferencias[$clave] !== false;
                        ?>
                            <label class="flavor-preferencia-item">
                                <input type="checkbox" name="preferencias[<?php echo esc_attr($clave); ?>]" <?php checked($activa); ?>>
                                <?php echo esc_html($etiqueta); ?>
                            </label>
                        <?php endforeach; ?>

                        <button type="button" class="flavor-btn-primary flavor-btn-sm" id="guardar-preferencias">
                            <?php _e('Guardar preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </details>
            </div>
        </div>

        <style>
            .flavor-dashboard-notificaciones { padding: 10px 0; }
            .flavor-notificaciones-lista { display: flex; flex-direction: column; gap: 10px; background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; }
            .flavor-notificacion-item { display: flex; align-items: center; gap: 15px; padding: 15px; border-bottom: 1px solid #f0f0f0; position: relative; transition: background 0.2s; }
            .flavor-notificacion-item:last-child { border-bottom: none; }
            .flavor-notificacion-item.no-leida { background: #f0f7ff; }
            .flavor-notificacion-item:hover { background: #f8f9fa; }
            .flavor-notificacion-icono { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #e8e8e8; border-radius: 50%; flex-shrink: 0; font-size: 20px; }
            .flavor-notificacion-content { flex: 1; min-width: 0; }
            .flavor-notificacion-titulo { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: #1d2327; }
            .flavor-notificacion-mensaje { margin: 0 0 4px; font-size: 13px; color: #666; line-height: 1.4; }
            .flavor-notificacion-fecha { font-size: 11px; color: #999; }
            .flavor-notificacion-link { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f0f0f0; border-radius: 50%; color: #666; text-decoration: none; flex-shrink: 0; transition: background 0.2s; }
            .flavor-notificacion-link:hover { background: #2271b1; color: #fff; }
            .flavor-notificacion-indicador { position: absolute; top: 15px; right: 15px; width: 8px; height: 8px; background: #2271b1; border-radius: 50%; }
            .flavor-notificaciones-preferencias { margin-top: 25px; background: #f8f9fa; border-radius: 12px; padding: 15px; }
            .flavor-notificaciones-preferencias summary { cursor: pointer; font-weight: 500; color: #1d2327; }
            .flavor-preferencias-form { margin-top: 15px; display: flex; flex-direction: column; gap: 10px; }
            .flavor-preferencia-item { display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; }
            .flavor-preferencia-item input { margin: 0; }
            #guardar-preferencias { margin-top: 10px; align-self: flex-start; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var $notice = $('<div class="flavor-dashboard-notice"></div>').insertBefore('.flavor-preferencias-grid').hide();

            function mostrarAviso(mensaje, tipo) {
                $notice.removeClass('success error').addClass(tipo || 'success').text(mensaje).show();
            }

            // Marcar todas como leídas
            $('#marcar-todas-leidas').on('click', function() {
                $.post(ajaxurl, {
                    action: 'comunidades_dashboard_notificaciones',
                    subaction: 'marcar_todas',
                    nonce: '<?php echo wp_create_nonce('comunidades_dashboard_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('.flavor-notificacion-item').removeClass('no-leida').addClass('leida');
                        $('.flavor-notificacion-indicador').remove();
                    }
                });
            });

            // Guardar preferencias
            $('#guardar-preferencias').on('click', function() {
                var preferencias = {};
                $('.flavor-preferencia-item input').each(function() {
                    var nombre = $(this).attr('name').replace('preferencias[', '').replace(']', '');
                    preferencias[nombre] = $(this).is(':checked') ? 'true' : 'false';
                });

                $.post(ajaxurl, {
                    action: 'comunidades_dashboard_notificaciones',
                    subaction: 'guardar_preferencias',
                    preferencias: preferencias,
                    nonce: '<?php echo wp_create_nonce('comunidades_dashboard_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        mostrarAviso('<?php echo esc_js(__('Preferencias guardadas', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'success');
                    }
                });
            });
        });
        </script>
        <style>
            .flavor-dashboard-notice {
                margin: 0 0 12px;
                padding: 12px 14px;
                border-radius: 8px;
                font-size: 14px;
            }
            .flavor-dashboard-notice.success { background: #dcfce7; color: #166534; }
            .flavor-dashboard-notice.error { background: #fee2e2; color: #991b1b; }
        </style>
        <?php
    }

    /**
     * Obtiene estadísticas del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array|null
     */
    private function obtener_estadisticas_usuario($usuario_id) {
        global $wpdb;

        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_miembros)) {
            return null;
        }

        $comunidades_total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_miembros WHERE user_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        $publicaciones_total = 0;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            $publicaciones_total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_actividad WHERE user_id = %d",
                $usuario_id
            ));
        }

        $roles_admin = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_miembros WHERE user_id = %d AND estado = 'activo' AND rol = 'admin'",
            $usuario_id
        ));

        return [
            'comunidades_total'   => $comunidades_total,
            'publicaciones_total' => $publicaciones_total,
            'roles_admin'         => $roles_admin,
        ];
    }

    /**
     * Genera notificaciones desde la actividad reciente (fallback)
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function generar_notificaciones_desde_actividad($usuario_id) {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [];
        }

        // Obtener actividad reciente de las comunidades del usuario (excluyendo su propia actividad)
        $actividades = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id, a.tipo, a.titulo, a.contenido, a.created_at AS fecha,
                    c.nombre AS comunidad_nombre, c.id AS comunidad_id,
                    u.display_name AS autor_nombre
             FROM $tabla_actividad a
             INNER JOIN $tabla_comunidades c ON a.comunidad_id = c.id
             INNER JOIN $tabla_miembros m ON m.comunidad_id = c.id AND m.user_id = %d AND m.estado = 'activo'
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.user_id != %d
             ORDER BY a.created_at DESC
             LIMIT 20",
            $usuario_id,
            $usuario_id
        ));

        $notificaciones_generadas = [];
        foreach ($actividades as $actividad) {
            $notificaciones_generadas[] = (object) [
                'id'         => $actividad->id,
                'tipo'       => $actividad->tipo,
                'titulo'     => sprintf(
                    __('Nueva %s en %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $this->obtener_label_tipo_actividad($actividad->tipo),
                    $actividad->comunidad_nombre
                ),
                'mensaje'    => !empty($actividad->titulo) ? $actividad->titulo : wp_trim_words($actividad->contenido, 10, '...'),
                'created_at' => $actividad->fecha,
                'link'       => add_query_arg('comunidad_id', $actividad->comunidad_id, Flavor_Platform_Helpers::get_action_url('comunidades', 'detalle')),
                'leida'      => false,
            ];
        }

        return $notificaciones_generadas;
    }

    /**
     * Obtiene el icono para un tipo de actividad
     *
     * @param string $tipo Tipo de actividad
     * @return string HTML del icono
     */
    private function obtener_icono_tipo_actividad($tipo) {
        $iconos = [
            'publicacion' => 'edit',
            'evento'      => 'calendar-alt',
            'anuncio'     => 'megaphone',
            'encuesta'    => 'chart-bar',
            'compartido'  => 'share',
            'comentario'  => 'admin-comments',
        ];

        $icono = isset($iconos[$tipo]) ? $iconos[$tipo] : 'marker';
        return '<span class="dashicons dashicons-' . esc_attr($icono) . '"></span>';
    }

    /**
     * Obtiene el label para un tipo de actividad
     *
     * @param string $tipo Tipo de actividad
     * @return string
     */
    private function obtener_label_tipo_actividad($tipo) {
        $labels = [
            'publicacion' => __('Publicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'evento'      => __('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'anuncio'     => __('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'encuesta'    => __('Encuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'compartido'  => __('Compartido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comentario'  => __('Comentario', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return isset($labels[$tipo]) ? $labels[$tipo] : ucfirst($tipo);
    }

    /**
     * Obtiene el texto de acción para el feed
     *
     * @param string $tipo Tipo de actividad
     * @return string
     */
    private function obtener_texto_accion($tipo) {
        $acciones = [
            'publicacion' => __('publicó en', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'evento'      => __('creó un evento en', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'anuncio'     => __('publicó un anuncio en', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'encuesta'    => __('creó una encuesta en', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'compartido'  => __('compartió contenido en', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comentario'  => __('comentó en', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return isset($acciones[$tipo]) ? $acciones[$tipo] : __('participó en', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Obtiene el icono para un tipo de notificación
     *
     * @param string $tipo Tipo de notificación
     * @return string Emoji o icono
     */
    private function obtener_icono_notificacion($tipo) {
        $iconos = [
            'nueva_publicacion'    => '📝',
            'nuevo_evento'         => '📅',
            'nuevo_miembro'        => '👋',
            'recurso_compartido'   => '📦',
            'mencion'              => '💬',
            'crosspost'            => '🔄',
            'contenido_federado'   => '🌐',
            'comunidad_relacionada' => '🔗',
            'evento_red'           => '🎉',
            'publicacion'          => '📝',
            'evento'               => '📅',
            'anuncio'              => '📢',
            'encuesta'             => '📊',
        ];

        return isset($iconos[$tipo]) ? $iconos[$tipo] : '🔔';
    }

    /**
     * AJAX: Cargar más actividad
     */
    public function ajax_cargar_actividad() {
        check_ajax_referer('comunidades_dashboard_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $pagina = isset($_POST['pagina']) ? max(1, intval($_POST['pagina'])) : 1;
        $por_pagina = 10;
        $offset = ($pagina - 1) * $por_pagina;

        global $wpdb;
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $actividades = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, c.nombre AS comunidad_nombre
             FROM $tabla_actividad a
             INNER JOIN $tabla_comunidades c ON a.comunidad_id = c.id
             WHERE a.user_id = %d
             ORDER BY a.created_at DESC
             LIMIT %d OFFSET %d",
            $usuario_id,
            $por_pagina,
            $offset
        ));

        wp_send_json_success([
            'actividades' => $actividades,
            'hay_mas'     => count($actividades) >= $por_pagina,
        ]);
    }

    /**
     * AJAX: Manejar notificaciones
     */
    public function ajax_cargar_notificaciones() {
        check_ajax_referer('comunidades_dashboard_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $subaccion = isset($_POST['subaction']) ? sanitize_text_field($_POST['subaction']) : '';

        switch ($subaccion) {
            case 'marcar_todas':
                // Marcar todas las notificaciones como leídas
                global $wpdb;
                $tabla_notificaciones = $wpdb->prefix . 'flavor_notificaciones';

                if (Flavor_Platform_Helpers::tabla_existe($tabla_notificaciones)) {
                    $tipos_comunidad = ['nueva_publicacion', 'nuevo_evento', 'nuevo_miembro', 'recurso_compartido', 'mencion', 'crosspost'];
                    $tipos_placeholder = implode("','", array_map('esc_sql', $tipos_comunidad));

                    $wpdb->query($wpdb->prepare(
                        "UPDATE $tabla_notificaciones SET leida = 1 WHERE user_id = %d AND tipo IN ('$tipos_placeholder')",
                        $usuario_id
                    ));
                }

                // Resetear contador en meta
                update_user_meta($usuario_id, 'flavor_comunidades_notificaciones_no_leidas', 0);

                wp_send_json_success(['message' => __('Notificaciones marcadas como leídas', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
                break;

            case 'guardar_preferencias':
                $preferencias_raw = isset($_POST['preferencias']) ? $_POST['preferencias'] : [];
                $preferencias = [];

                $tipos_permitidos = [
                    'nueva_publicacion',
                    'nuevo_evento',
                    'nuevo_miembro',
                    'mencion',
                    'crosspost',
                    'contenido_federado',
                ];

                foreach ($tipos_permitidos as $tipo) {
                    $preferencias[$tipo] = isset($preferencias_raw[$tipo]) && $preferencias_raw[$tipo] === 'true';
                }

                update_user_meta($usuario_id, 'flavor_notificaciones_comunidades', $preferencias);

                wp_send_json_success([
                    'message'      => __('Preferencias guardadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'preferencias' => $preferencias,
                ]);
                break;

            default:
                wp_send_json_error(['message' => __('Acción no válida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }
}
