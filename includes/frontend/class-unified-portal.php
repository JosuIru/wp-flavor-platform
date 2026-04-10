<?php
/**
 * Portal Unificado - Sistema de visualización de ecosistema
 *
 * Muestra una vista integrada de todos los módulos activos del usuario,
 * organizados jerárquicamente (Base > Verticales > Transversales).
 *
 * @package FlavorPlatform
 * @since 4.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Unified_Portal {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Layouts disponibles
     */
    const LAYOUTS = [
        'legacy'      => 'Legacy (Original)',
        'ecosystem'   => 'Ecosistema (Jerárquico)',
        'cards'       => 'Cards (Grid modular)',
        'sidebar'     => 'Sidebar (Panel lateral)',
        'compact'     => 'Compacto (Lista)',
        'dashboard'   => 'Dashboard (Widgets)',
    ];

    /**
     * Datos del usuario actual
     */
    private $user_data = [];

    /**
     * Módulos agrupados por tipo
     */
    private $modules_by_type = [];

    /**
     * Obtener instancia
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
        add_shortcode('flavor_portal_unificado', [$this, 'render_portal']);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        add_action('wp_ajax_flavor_portal_refresh', [$this, 'ajax_refresh_data']);
        add_action('wp_ajax_flavor_portal_save_layout', [$this, 'ajax_save_user_layout']);
        add_action('wp_ajax_flavor_portal_reset_layout', [$this, 'ajax_reset_user_layout']);

        // Limpiar preferencias de usuario cuando se cambia la configuración global
        add_action('update_option_flavor_design_settings', [$this, 'on_global_settings_change'], 10, 2);
    }

    /**
     * Obtiene el layout configurado (prioriza preferencia del usuario)
     */
    public function get_configured_layout() {
        // Primero verificar preferencia del usuario
        if (is_user_logged_in()) {
            $user_layout = get_user_meta(get_current_user_id(), 'flavor_portal_layout', true);
            if (!empty($user_layout) && array_key_exists($user_layout, self::LAYOUTS)) {
                return $user_layout;
            }
        }

        // Fallback a configuración global
        $settings = get_option('flavor_design_settings', []);
        return $settings['portal_layout'] ?? 'ecosystem';
    }

    /**
     * Guarda la preferencia de layout del usuario
     */
    public function ajax_save_user_layout() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
        }

        check_ajax_referer('flavor_unified_portal', 'nonce');

        $layout = sanitize_key($_POST['layout'] ?? '');

        if (empty($layout) || !array_key_exists($layout, self::LAYOUTS)) {
            wp_send_json_error(['message' => __('Layout no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'flavor_portal_layout', $layout);

        wp_send_json_success([
            'layout' => $layout,
            'message' => __('Preferencia guardada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * Restablece la preferencia de layout del usuario actual a la configuración global
     */
    public function ajax_reset_user_layout() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
        }

        check_ajax_referer('flavor_unified_portal', 'nonce');

        $user_id = get_current_user_id();
        delete_user_meta($user_id, 'flavor_portal_layout');

        // Obtener el layout global que ahora se usará
        $settings = get_option('flavor_design_settings', []);
        $global_layout = $settings['portal_layout'] ?? 'ecosystem';

        wp_send_json_success([
            'layout' => $global_layout,
            'message' => __('Usando configuración global', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * Callback cuando se cambian los ajustes globales de diseño
     * Si el admin cambia portal_layout, limpiamos las preferencias de usuario
     *
     * @param array $old_value Valor anterior
     * @param array $new_value Nuevo valor
     */
    public function on_global_settings_change($old_value, $new_value) {
        $old_layout = $old_value['portal_layout'] ?? 'ecosystem';
        $new_layout = $new_value['portal_layout'] ?? 'ecosystem';

        // Solo limpiar si realmente cambió el layout
        if ($old_layout !== $new_layout) {
            // Limpiar la preferencia del usuario actual (el admin que hizo el cambio)
            if (is_user_logged_in()) {
                delete_user_meta(get_current_user_id(), 'flavor_portal_layout');
            }
        }
    }

    /**
     * Obtiene la preferencia de layout del usuario actual
     */
    public function get_user_layout_preference() {
        if (!is_user_logged_in()) {
            return null;
        }
        return get_user_meta(get_current_user_id(), 'flavor_portal_layout', true) ?: null;
    }

    /**
     * Obtiene los layouts disponibles
     */
    public static function get_available_layouts() {
        return self::LAYOUTS;
    }

    /**
     * Obtiene el icono para un layout
     */
    private function get_layout_icon($layout) {
        $icons = [
            'legacy' => '📋',
            'ecosystem' => '🌳',
            'cards' => '🃏',
            'sidebar' => '📑',
            'compact' => '📝',
            'dashboard' => '📊',
        ];
        return $icons[$layout] ?? '📋';
    }

    /**
     * Encola assets si el shortcode está presente
     */
    public function maybe_enqueue_assets() {
        global $post;

        if (!$post) {
            return;
        }

        $has_shortcode = has_shortcode($post->post_content, 'flavor_portal_unificado') ||
                         has_shortcode($post->post_content, 'flavor_mi_portal');

        if ($has_shortcode) {
            $sufijo_minificado = (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';

            wp_enqueue_style(
                'flavor-unified-portal',
                FLAVOR_PLATFORM_URL . "assets/css/layouts/unified-portal{$sufijo_minificado}.css",
                ['fl-design-tokens'],
                FLAVOR_PLATFORM_VERSION
            );

            wp_enqueue_script(
                'flavor-unified-portal',
                FLAVOR_PLATFORM_URL . "assets/js/unified-portal{$sufijo_minificado}.js",
                ['jquery'],
                FLAVOR_PLATFORM_VERSION,
                true
            );

            wp_localize_script('flavor-unified-portal', 'flavorUnifiedPortal', [
                'ajaxUrl'     => admin_url('admin-ajax.php'),
                'nonce'       => wp_create_nonce('flavor_unified_portal'),
                'userId'      => get_current_user_id(),
                'settingsUrl' => Flavor_Platform_Helpers::get_action_url('configuracion', ''),
                'i18n'        => [
                    'loading'                 => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'error'                   => __('Error al cargar datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'noModules'               => __('No hay módulos activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'layoutSaved'             => __('Vista guardada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'notificationsUnavailable' => __('Las notificaciones no están disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'nodes'                   => __('nodos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'communities'             => __('comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ]);
        }
    }

    /**
     * Renderiza el portal unificado
     */
    public function render_portal($atts = []) {
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $atts = shortcode_atts([
            'layout'        => '', // Si vacío, usa el configurado
            'show_network'  => 'yes',
            'show_stats'    => 'yes',
            'show_actions'  => 'yes',
            'columns'       => '3',
        ], $atts);

        // Determinar layout
        $layout = !empty($atts['layout']) ? $atts['layout'] : $this->get_configured_layout();

        // Cargar datos
        $this->load_user_data();
        $this->load_modules_data();

        // Renderizar según layout
        ob_start();

        echo '<div class="flavor-unified-portal" data-layout="' . esc_attr($layout) . '">';

        // Header común
        $this->render_header($atts);

        // Paneles de prioridad: señales del nodo y próximas acciones
        $this->render_priority_panels();

        // Contenido según layout
        switch ($layout) {
            case 'cards':
                $this->render_layout_cards($atts);
                break;
            case 'sidebar':
                $this->render_layout_sidebar($atts);
                break;
            case 'compact':
                $this->render_layout_compact($atts);
                break;
            case 'dashboard':
                $this->render_layout_dashboard($atts);
                break;
            case 'ecosystem':
            default:
                $this->render_layout_ecosystem($atts);
                break;
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Carga datos del usuario
     */
    private function load_user_data() {
        $user = wp_get_current_user();

        $this->user_data = [
            'id'           => $user->ID,
            'name'         => $user->display_name,
            'email'        => $user->user_email,
            'avatar'       => get_avatar_url($user->ID, ['size' => 80]),
            'greeting'     => $this->get_greeting(),
            'member_since' => $user->user_registered,
        ];
    }

    /**
     * Obtiene saludo según hora
     */
    private function get_greeting() {
        $hora = (int) current_time('H');
        if ($hora < 12) {
            return __('Buenos días', FLAVOR_PLATFORM_TEXT_DOMAIN);
        } elseif ($hora < 20) {
            return __('Buenas tardes', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }
        return __('Buenas noches', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Carga datos de módulos agrupados por tipo
     */
    private function load_modules_data() {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            $this->modules_by_type = ['base' => [], 'vertical' => [], 'transversal' => [], 'service' => []];
            return;
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $active_modules = $loader->get_loaded_modules();

        $this->modules_by_type = [
            'base'        => [],
            'vertical'    => [],
            'transversal' => [],
            'service'     => [], // Módulos de servicio/contenido (podcast, radio, biblioteca, etc.)
        ];

        // Módulos considerados de servicio/contenido
        $service_modules = ['podcast', 'radio', 'biblioteca', 'multimedia', 'cursos', 'documentacion_legal'];

        foreach ($active_modules as $module_id => $module) {
            $metadata = $module->get_ecosystem_metadata();
            $role = $metadata['module_role'] ?? 'vertical';

            $module_data = [
                'id'          => $module_id,
                'name'        => $module->get_name(),
                'description' => $module->get_description(),
                'icon'        => method_exists($module, 'get_icon') ? $module->get_icon() : 'dashicons-admin-generic',
                'color'       => method_exists($module, 'get_color') ? $module->get_color() : '#6b7280',
                'url'         => $this->get_module_url($module_id),
                'stats'       => $this->get_module_stats($module),
                'metadata'    => $metadata,
            ];

            // Obtener satélites si es módulo base
            if ($role === 'base') {
                $module_data['satellites'] = $this->get_satellites_for_base($module_id, $active_modules);
            }

            // Obtener módulos afectados si es transversal
            if ($role === 'transversal') {
                $module_data['affects'] = $this->get_affected_modules($metadata);
            }

            // Clasificar módulos de servicio separadamente
            if (in_array($module_id, $service_modules, true)) {
                $this->modules_by_type['service'][$module_id] = $module_data;
            } else {
                $this->modules_by_type[$role][$module_id] = $module_data;
            }
        }
    }

    /**
     * Obtiene satélites de un módulo base
     */
    private function get_satellites_for_base($base_id, $all_modules) {
        $satellites = [];

        foreach ($all_modules as $module_id => $module) {
            $metadata = $module->get_ecosystem_metadata();
            $parent = $metadata['dashboard_parent_module'] ?? '';

            if ($parent === $base_id && $module_id !== $base_id) {
                $satellites[] = [
                    'id'   => $module_id,
                    'name' => $module->get_name(),
                    'icon' => method_exists($module, 'get_icon') ? $module->get_icon() : 'dashicons-admin-generic',
                    'url'  => $this->get_module_url($module_id),
                ];
            }
        }

        return $satellites;
    }

    /**
     * Obtiene módulos afectados por un transversal
     */
    private function get_affected_modules($metadata) {
        $affected = [];

        $relations = [
            'ecosystem_governs_modules',
            'ecosystem_measures_modules',
            'ecosystem_teaches_modules',
        ];

        foreach ($relations as $relation) {
            if (!empty($metadata[$relation])) {
                $affected = array_merge($affected, (array) $metadata[$relation]);
            }
        }

        return array_unique($affected);
    }

    /**
     * Obtiene URL de un módulo
     */
    private function get_module_url($module_id) {
        // Convertir module_id a slug URL (guiones bajos → guiones)
        $slug = str_replace('_', '-', $module_id);

        // Buscar si existe una página WordPress con ese slug
        $page = get_page_by_path($slug);
        if ($page) {
            return get_permalink($page->ID);
        }

        // Usar ruta dinámica del portal: /mi-portal/{modulo}/
        return Flavor_Platform_Helpers::get_action_url(str_replace('-', '_', $slug), '');
    }

    /**
     * Obtiene estadísticas de un módulo
     */
    private function get_module_stats($module) {
        // Intentar obtener stats del módulo si tiene el método
        if (method_exists($module, 'get_user_stats')) {
            return $module->get_user_stats(get_current_user_id());
        }

        return [];
    }

    /**
     * Obtiene datos de la red
     */
    private function get_network_data() {
        if (!class_exists('Flavor_Network_Manager')) {
            return null;
        }

        $network = Flavor_Network_Manager::get_instance();

        return [
            'nodes_count'       => method_exists($network, 'get_nodes_count') ? $network->get_nodes_count() : 0,
            'communities_count' => method_exists($network, 'get_communities_count') ? $network->get_communities_count() : 0,
            'users_count'       => method_exists($network, 'get_network_users_count') ? $network->get_network_users_count() : 0,
            'is_connected'      => method_exists($network, 'is_node_connected') ? $network->is_node_connected() : false,
        ];
    }

    /**
     * Renderiza header común
     */
    private function render_header($atts) {
        $network = ($atts['show_network'] === 'yes') ? $this->get_network_data() : null;
        ?>
        <header class="fup-header">
            <div class="fup-header__user">
                <img src="<?php echo esc_url($this->user_data['avatar']); ?>"
                     alt="<?php echo esc_attr($this->user_data['name']); ?>"
                     class="fup-header__avatar">
                <div class="fup-header__greeting">
                    <span class="fup-header__saludo"><?php echo esc_html($this->user_data['greeting']); ?>,</span>
                    <h1 class="fup-header__name"><?php echo esc_html($this->user_data['name']); ?></h1>
                </div>
            </div>

            <?php if ($network && $network['is_connected']) : ?>
            <div class="fup-header__network">
                <span class="fup-header__network-status"></span>
                <span class="fup-header__network-label"><?php _e('Red conectada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="fup-header__network-stats">
                    <?php printf(
                        __('%d nodos · %d comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $network['nodes_count'],
                        $network['communities_count']
                    ); ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="fup-header__actions">
                <!-- Selector de layout -->
                <?php
                $current_layout = $this->get_configured_layout();
                $user_preference = $this->get_user_layout_preference();
                $global_settings = get_option('flavor_design_settings', []);
                $global_layout = $global_settings['portal_layout'] ?? 'ecosystem';
                $using_personal = !empty($user_preference) && $user_preference !== $global_layout;
                ?>
                <div class="fup-layout-selector<?php echo $using_personal ? ' has-personal-preference' : ''; ?>">
                    <button type="button" class="fup-btn fup-btn--icon fup-layout-selector__toggle" title="<?php esc_attr_e('Cambiar vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-layout"></span>
                        <?php if ($using_personal) : ?>
                        <span class="fup-layout-selector__indicator" title="<?php esc_attr_e('Usando vista personal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                        <?php endif; ?>
                    </button>
                    <div class="fup-layout-selector__dropdown">
                        <span class="fup-layout-selector__label"><?php _e('Vista del portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php
                        $layouts_sin_legacy = array_filter(self::LAYOUTS, function($key) {
                            return $key !== 'legacy';
                        }, ARRAY_FILTER_USE_KEY);
                        foreach ($layouts_sin_legacy as $key => $label) :
                            $is_active = ($key === $current_layout);
                        ?>
                        <button type="button"
                                class="fup-layout-selector__option<?php echo $is_active ? ' is-active' : ''; ?>"
                                data-layout="<?php echo esc_attr($key); ?>">
                            <span class="fup-layout-selector__icon"><?php echo $this->get_layout_icon($key); ?></span>
                            <span class="fup-layout-selector__name"><?php echo esc_html($label); ?></span>
                            <?php if ($is_active) : ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                        <?php if ($using_personal) : ?>
                        <hr class="fup-layout-selector__divider">
                        <button type="button" class="fup-layout-selector__reset" data-action="reset-layout">
                            <span class="dashicons dashicons-image-rotate"></span>
                            <span><?php _e('Usar configuración global', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="button" class="fup-btn fup-btn--icon" data-action="refresh" title="<?php esc_attr_e('Actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
                <button type="button" class="fup-btn fup-btn--icon" data-action="notifications" title="<?php esc_attr_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-bell"></span>
                </button>
            </div>
        </header>
        <?php
    }

    /**
     * Renderiza los paneles de prioridad: señales del nodo y próximas acciones
     *
     * Muestra información relevante de todos los módulos activos:
     * - Notificaciones urgentes (avisos, incidencias, cuotas)
     * - Acciones próximas (eventos, reservas, votaciones, devoluciones)
     */
    private function render_priority_panels() {
        if (!is_user_logged_in()) {
            return;
        }

        // Obtener las señales y acciones usando Flavor_Portal_Shortcodes
        $notifications_html = '';
        $actions_html = '';

        if (class_exists('Flavor_Portal_Shortcodes')) {
            $portal_shortcodes = Flavor_Portal_Shortcodes::get_instance();

            if (method_exists($portal_shortcodes, 'render_shared_notifications_bar')) {
                $notifications_html = (string) $portal_shortcodes->render_shared_notifications_bar();
            }

            if (method_exists($portal_shortcodes, 'render_shared_upcoming_actions')) {
                $actions_html = (string) $portal_shortcodes->render_shared_upcoming_actions();
            }
        }

        ?>
        <div class="fup-priority-panels">
            <div class="fup-panel fup-panel--signals">
                <div class="fup-panel__header">
                    <span class="fup-panel__icon">📡</span>
                    <h3 class="fup-panel__title"><?php esc_html_e('Señales del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="fup-panel__content">
                    <?php if (!empty($notifications_html)): ?>
                        <?php echo $notifications_html; ?>
                    <?php else: ?>
                        <div class="fup-panel__empty">
                            <span class="fup-panel__empty-icon">✨</span>
                            <p><?php esc_html_e('Sin señales pendientes. Tu nodo está al día.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="fup-panel fup-panel--actions">
                <div class="fup-panel__header">
                    <span class="fup-panel__icon">⚡</span>
                    <h3 class="fup-panel__title"><?php esc_html_e('Próximas acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="fup-panel__content">
                    <?php if (!empty($actions_html)): ?>
                        <?php echo $actions_html; ?>
                    <?php else: ?>
                        <div class="fup-panel__empty">
                            <span class="fup-panel__empty-icon">🎯</span>
                            <p><?php esc_html_e('No hay acciones inmediatas programadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * LAYOUT: Ecosistema (Jerárquico)
     * Muestra la jerarquía Base > Satélites > Transversales
     */
    private function render_layout_ecosystem($atts) {
        ?>
        <div class="fup-ecosystem">
            <?php if (!empty($this->modules_by_type['transversal'])) : ?>
            <!-- Capa Transversal (arriba, cruza todo) -->
            <section class="fup-section fup-section--transversal">
                <header class="fup-section__header">
                    <h2 class="fup-section__title">
                        <span class="dashicons dashicons-networking"></span>
                        <?php _e('Capas Transversales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h2>
                    <p class="fup-section__subtitle"><?php _e('Gobernanza, medición y aprendizaje que cruzan todo el ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </header>
                <div class="fup-transversal-bar">
                    <?php foreach ($this->modules_by_type['transversal'] as $module) : ?>
                    <a href="<?php echo esc_url($module['url']); ?>" class="fup-transversal-item" style="--module-color: <?php echo esc_attr($module['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($module['icon']); ?>"></span>
                        <span class="fup-transversal-item__name"><?php echo esc_html($module['name']); ?></span>
                        <?php if (!empty($module['stats'])) : ?>
                        <span class="fup-transversal-item__stat"><?php echo esc_html(reset($module['stats'])); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Módulos Base con sus Satélites -->
            <section class="fup-section fup-section--bases">
                <header class="fup-section__header">
                    <h2 class="fup-section__title">
                        <span class="dashicons dashicons-groups"></span>
                        <?php _e('Mis Espacios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h2>
                    <p class="fup-section__subtitle"><?php _e('Comunidades, colectivos y organizaciones donde participas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </header>

                <?php if (!empty($this->modules_by_type['base'])) : ?>
                <div class="fup-bases-grid">
                    <?php foreach ($this->modules_by_type['base'] as $base) : ?>
                    <article class="fup-base-card" style="--module-color: <?php echo esc_attr($base['color']); ?>">
                        <header class="fup-base-card__header">
                            <div class="fup-base-card__icon">
                                <span class="dashicons <?php echo esc_attr($base['icon']); ?>"></span>
                            </div>
                            <div class="fup-base-card__info">
                                <h3 class="fup-base-card__name"><?php echo esc_html($base['name']); ?></h3>
                                <p class="fup-base-card__desc"><?php echo esc_html(wp_trim_words($base['description'], 10)); ?></p>
                            </div>
                        </header>

                        <?php if (!empty($base['satellites'])) : ?>
                        <div class="fup-base-card__satellites">
                            <span class="fup-base-card__satellites-label"><?php _e('Módulos activos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <ul class="fup-satellites-list">
                                <?php foreach ($base['satellites'] as $satellite) : ?>
                                <li>
                                    <a href="<?php echo esc_url($satellite['url']); ?>" class="fup-satellite-link">
                                        <span class="dashicons <?php echo esc_attr($satellite['icon']); ?>"></span>
                                        <?php echo esc_html($satellite['name']); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <footer class="fup-base-card__footer">
                            <a href="<?php echo esc_url($base['url']); ?>" class="fup-btn fup-btn--primary">
                                <?php _e('Abrir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </a>
                        </footer>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="fup-empty-state">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php _e('No estás participando en ningún espacio todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo home_url('/comunidades/'); ?>" class="fup-btn fup-btn--primary">
                        <?php _e('Explorar comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <?php endif; ?>
            </section>

            <!-- Módulos Verticales Independientes -->
            <?php
            $independent_verticals = array_filter($this->modules_by_type['vertical'], function($m) {
                $parent = $m['metadata']['dashboard_parent_module'] ?? '';
                return empty($parent) || $parent === 'self';
            });

            if (!empty($independent_verticals)) :
            ?>
            <section class="fup-section fup-section--tools">
                <header class="fup-section__header">
                    <h2 class="fup-section__title">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Herramientas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h2>
                    <p class="fup-section__subtitle"><?php _e('Acceso rápido a servicios y herramientas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </header>
                <div class="fup-tools-grid">
                    <?php foreach ($independent_verticals as $tool) : ?>
                    <a href="<?php echo esc_url($tool['url']); ?>" class="fup-tool-card" style="--module-color: <?php echo esc_attr($tool['color']); ?>">
                        <span class="fup-tool-card__icon dashicons <?php echo esc_attr($tool['icon']); ?>"></span>
                        <span class="fup-tool-card__name"><?php echo esc_html($tool['name']); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Otros Espacios Activos (Servicios de contenido) -->
            <?php if (!empty($this->modules_by_type['service'])) : ?>
            <section class="fup-section fup-section--services">
                <header class="fup-section__header">
                    <h2 class="fup-section__title">
                        <span class="dashicons dashicons-format-audio"></span>
                        <?php _e('Otros espacios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h2>
                    <p class="fup-section__subtitle"><?php _e('Contenidos, recursos y servicios de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </header>
                <div class="fup-services-grid">
                    <?php foreach ($this->modules_by_type['service'] as $service) : ?>
                    <a href="<?php echo esc_url($service['url']); ?>" class="fup-service-card" style="--module-color: <?php echo esc_attr($service['color']); ?>">
                        <div class="fup-service-card__icon">
                            <span class="dashicons <?php echo esc_attr($service['icon']); ?>"></span>
                        </div>
                        <div class="fup-service-card__content">
                            <h3 class="fup-service-card__name"><?php echo esc_html($service['name']); ?></h3>
                            <p class="fup-service-card__desc"><?php echo esc_html(wp_trim_words($service['description'], 10)); ?></p>
                        </div>
                        <span class="fup-service-card__arrow dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * LAYOUT: Cards (Grid modular)
     * Todas las cards al mismo nivel en grid
     */
    private function render_layout_cards($atts) {
        $all_modules = array_merge(
            $this->modules_by_type['base'],
            $this->modules_by_type['vertical'],
            $this->modules_by_type['transversal'],
            $this->modules_by_type['service']
        );
        ?>
        <div class="fup-cards">
            <div class="fup-cards-grid" style="--columns: <?php echo esc_attr($atts['columns']); ?>">
                <?php foreach ($all_modules as $module) : ?>
                <a href="<?php echo esc_url($module['url']); ?>" class="fup-card" style="--module-color: <?php echo esc_attr($module['color']); ?>">
                    <div class="fup-card__icon">
                        <span class="dashicons <?php echo esc_attr($module['icon']); ?>"></span>
                    </div>
                    <div class="fup-card__content">
                        <h3 class="fup-card__title"><?php echo esc_html($module['name']); ?></h3>
                        <p class="fup-card__desc"><?php echo esc_html(wp_trim_words($module['description'], 8)); ?></p>
                    </div>
                    <span class="fup-card__arrow dashicons dashicons-arrow-right-alt2"></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * LAYOUT: Sidebar (Panel lateral)
     */
    private function render_layout_sidebar($atts) {
        $recent_activity = $this->get_recent_activity_summary();
        $highlighted_content = $this->get_highlighted_content();
        ?>
        <div class="fup-sidebar-layout">
            <aside class="fup-sidebar">
                <nav class="fup-sidebar-nav">
                    <?php foreach (['base' => __('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'vertical' => __('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'transversal' => __('Transversales', FLAVOR_PLATFORM_TEXT_DOMAIN)] as $type => $label) : ?>
                    <?php if (!empty($this->modules_by_type[$type])) : ?>
                    <div class="fup-sidebar-group">
                        <h3 class="fup-sidebar-group__title"><?php echo esc_html($label); ?></h3>
                        <ul class="fup-sidebar-list">
                            <?php foreach ($this->modules_by_type[$type] as $module) : ?>
                            <li>
                                <a href="<?php echo esc_url($module['url']); ?>" class="fup-sidebar-link" style="--module-color: <?php echo esc_attr($module['color']); ?>">
                                    <span class="dashicons <?php echo esc_attr($module['icon']); ?>"></span>
                                    <span><?php echo esc_html($module['name']); ?></span>
                                    <?php if (!empty($module['stats']['pending'])) : ?>
                                    <span class="fup-sidebar-badge"><?php echo esc_html($module['stats']['pending']); ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </aside>
            <main class="fup-main-content">
                <!-- Contenido destacado: publicaciones, anuncios, novedades -->
                <?php if (!empty($highlighted_content)) : ?>
                <section class="fup-content-feed">
                    <h3 class="fup-section__title">
                        <span class="dashicons dashicons-megaphone"></span>
                        <?php _e('Novedades del ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="fup-feed-list">
                        <?php foreach (array_slice($highlighted_content, 0, 6) as $item) : ?>
                        <article class="fup-feed-item" data-type="<?php echo esc_attr($item['type']); ?>">
                            <div class="fup-feed-item__header">
                                <span class="fup-feed-item__icon"><?php echo $item['icon']; ?></span>
                                <span class="fup-feed-item__source"><?php echo esc_html($item['source']); ?></span>
                                <time class="fup-feed-item__time"><?php echo esc_html($item['time_ago']); ?></time>
                            </div>
                            <div class="fup-feed-item__content">
                                <?php if (!empty($item['title'])) : ?>
                                <h4 class="fup-feed-item__title">
                                    <?php if (!empty($item['url'])) : ?>
                                    <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
                                    <?php else : ?>
                                    <?php echo esc_html($item['title']); ?>
                                    <?php endif; ?>
                                </h4>
                                <?php endif; ?>
                                <?php if (!empty($item['excerpt'])) : ?>
                                <p class="fup-feed-item__excerpt"><?php echo esc_html($item['excerpt']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($item['meta'])) : ?>
                            <div class="fup-feed-item__meta">
                                <?php echo esc_html($item['meta']); ?>
                            </div>
                            <?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Actividad reciente de la comunidad -->
                <?php if (!empty($recent_activity)) : ?>
                <section class="fup-sidebar-activity">
                    <h3 class="fup-section__title">
                        <span class="dashicons dashicons-backup"></span>
                        <?php _e('Actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <ul class="fup-sidebar-activity__list">
                        <?php foreach (array_slice($recent_activity, 0, 5) as $activity) : ?>
                        <li class="fup-sidebar-activity__item">
                            <span class="fup-sidebar-activity__icon"><?php echo $activity['icon'] ?? '📝'; ?></span>
                            <div class="fup-sidebar-activity__content">
                                <span class="fup-sidebar-activity__text"><?php echo esc_html($activity['text']); ?></span>
                                <span class="fup-sidebar-activity__time"><?php echo esc_html($activity['time_ago']); ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <!-- Mensaje si no hay contenido -->
                <?php if (empty($highlighted_content) && empty($recent_activity)) : ?>
                <div class="fup-content-empty">
                    <span class="fup-content-empty__icon">🌱</span>
                    <h3><?php _e('Tu ecosistema está listo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Explora los módulos del menú lateral para comenzar a participar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <?php endif; ?>
            </main>
        </div>
        <?php
    }

    /**
     * Obtiene contenido destacado del ecosistema (anuncios, publicaciones, novedades)
     */
    private function get_highlighted_content() {
        $content = [];
        $user_id = get_current_user_id();

        global $wpdb;

        // ========================================
        // AVISOS MUNICIPALES - Más recientes
        // ========================================
        $avisos_table = $wpdb->prefix . 'flavor_avisos';
        if ($wpdb->get_var("SHOW TABLES LIKE '$avisos_table'") === $avisos_table) {
            $avisos = $wpdb->get_results(
                "SELECT titulo, contenido, fecha_publicacion, categoria
                 FROM $avisos_table
                 WHERE estado = 'publicado'
                 AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())
                 ORDER BY fecha_publicacion DESC LIMIT 2",
                ARRAY_A
            );
            foreach ($avisos as $aviso) {
                $content[] = [
                    'type' => 'aviso',
                    'icon' => '📢',
                    'source' => __('Aviso oficial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'title' => $aviso['titulo'],
                    'excerpt' => wp_trim_words($aviso['contenido'], 15, '...'),
                    'url' => Flavor_Platform_Helpers::get_action_url('avisos_municipales', ''),
                    'time' => strtotime($aviso['fecha_publicacion']),
                    'time_ago' => $this->time_ago($aviso['fecha_publicacion']),
                    'meta' => !empty($aviso['categoria']) ? $aviso['categoria'] : '',
                ];
            }
        }

        // ========================================
        // RED SOCIAL - Publicaciones destacadas
        // ========================================
        $social_table = $wpdb->prefix . 'flavor_social_publicaciones';
        if ($wpdb->get_var("SHOW TABLES LIKE '$social_table'") === $social_table) {
            $publicaciones = $wpdb->get_results(
                "SELECT p.id, p.contenido, p.fecha_creacion, p.autor_id,
                        (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_reacciones WHERE publicacion_id = p.id) as reacciones
                 FROM $social_table p
                 WHERE p.estado = 'publicado'
                 AND p.fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY reacciones DESC, p.fecha_creacion DESC LIMIT 3",
                ARRAY_A
            );
            foreach ($publicaciones as $pub) {
                $autor = get_userdata((int) $pub['autor_id']);
                $nombre_autor = $autor ? $autor->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
                $content[] = [
                    'type' => 'social',
                    'icon' => '💬',
                    'source' => $nombre_autor,
                    'title' => '',
                    'excerpt' => wp_trim_words($pub['contenido'], 20, '...'),
                    'url' => Flavor_Platform_Helpers::get_action_url('red_social', '') . '?pub=' . $pub['id'],
                    'time' => strtotime($pub['fecha_creacion']),
                    'time_ago' => $this->time_ago($pub['fecha_creacion']),
                    'meta' => $pub['reacciones'] > 0 ? sprintf(_n('%d reacción', '%d reacciones', (int) $pub['reacciones'], FLAVOR_PLATFORM_TEXT_DOMAIN), (int) $pub['reacciones']) : '',
                ];
            }
        }

        // ========================================
        // EVENTOS - Próximos eventos
        // ========================================
        $eventos_table = $wpdb->prefix . 'flavor_eventos';
        if ($wpdb->get_var("SHOW TABLES LIKE '$eventos_table'") === $eventos_table) {
            $eventos = $wpdb->get_results(
                "SELECT titulo, descripcion, fecha, lugar
                 FROM $eventos_table
                 WHERE estado = 'publicado'
                 AND fecha >= CURDATE()
                 ORDER BY fecha ASC LIMIT 2",
                ARRAY_A
            );
            foreach ($eventos as $evento) {
                $content[] = [
                    'type' => 'evento',
                    'icon' => '📅',
                    'source' => __('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'title' => $evento['titulo'],
                    'excerpt' => !empty($evento['descripcion']) ? wp_trim_words($evento['descripcion'], 12, '...') : '',
                    'url' => Flavor_Platform_Helpers::get_action_url('eventos', ''),
                    'time' => strtotime($evento['fecha']),
                    'time_ago' => date_i18n(get_option('date_format'), strtotime($evento['fecha'])),
                    'meta' => !empty($evento['lugar']) ? '📍 ' . $evento['lugar'] : '',
                ];
            }
        }

        // ========================================
        // FOROS - Temas activos
        // ========================================
        $foros_temas = $wpdb->prefix . 'flavor_foro_temas';
        if ($wpdb->get_var("SHOW TABLES LIKE '$foros_temas'") === $foros_temas) {
            $temas = $wpdb->get_results(
                "SELECT t.titulo, t.fecha_creacion, t.autor_id,
                        (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_foro_respuestas WHERE tema_id = t.id) as respuestas
                 FROM $foros_temas t
                 WHERE t.estado = 'publicado'
                 ORDER BY t.fecha_actualizacion DESC LIMIT 2",
                ARRAY_A
            );
            foreach ($temas as $tema) {
                $autor = get_userdata((int) $tema['autor_id']);
                $content[] = [
                    'type' => 'foro',
                    'icon' => '🗣️',
                    'source' => __('Foro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'title' => $tema['titulo'],
                    'excerpt' => '',
                    'url' => Flavor_Platform_Helpers::get_action_url('foros', ''),
                    'time' => strtotime($tema['fecha_creacion']),
                    'time_ago' => $this->time_ago($tema['fecha_creacion']),
                    'meta' => $tema['respuestas'] > 0 ? sprintf(_n('%d respuesta', '%d respuestas', (int) $tema['respuestas'], FLAVOR_PLATFORM_TEXT_DOMAIN), (int) $tema['respuestas']) : __('Sin respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        // ========================================
        // PODCAST - Episodios recientes
        // ========================================
        $podcast_table = $wpdb->prefix . 'flavor_podcast_episodios';
        if ($wpdb->get_var("SHOW TABLES LIKE '$podcast_table'") === $podcast_table) {
            $episodios = $wpdb->get_results(
                "SELECT titulo, descripcion, fecha_publicacion, duracion
                 FROM $podcast_table
                 WHERE estado = 'publicado'
                 ORDER BY fecha_publicacion DESC LIMIT 1",
                ARRAY_A
            );
            foreach ($episodios as $ep) {
                $content[] = [
                    'type' => 'podcast',
                    'icon' => '🎙️',
                    'source' => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'title' => $ep['titulo'],
                    'excerpt' => !empty($ep['descripcion']) ? wp_trim_words($ep['descripcion'], 10, '...') : '',
                    'url' => Flavor_Platform_Helpers::get_action_url('podcast', ''),
                    'time' => strtotime($ep['fecha_publicacion']),
                    'time_ago' => $this->time_ago($ep['fecha_publicacion']),
                    'meta' => !empty($ep['duracion']) ? '⏱️ ' . $ep['duracion'] . ' min' : '',
                ];
            }
        }

        // Ordenar por fecha (más recientes primero)
        usort($content, function($a, $b) {
            return ($b['time'] ?? 0) - ($a['time'] ?? 0);
        });

        return $content;
    }

    /**
     * Convierte datetime o timestamp a "hace X tiempo"
     *
     * @param string|int $datetime String de fecha o timestamp
     * @return string Tiempo relativo formateado
     */
    private function time_ago($datetime) {
        // Si es un timestamp (número), usarlo directamente
        // Si es string, convertirlo
        $time = is_numeric($datetime) ? (int) $datetime : strtotime($datetime);
        $now = current_time('timestamp');
        $diff = $now - $time;

        if ($diff < 0) {
            // Fecha futura
            return date_i18n(get_option('date_format'), $time);
        } elseif ($diff < 60) {
            return __('Hace un momento', FLAVOR_PLATFORM_TEXT_DOMAIN);
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return sprintf(_n('Hace %d minuto', 'Hace %d minutos', $mins, FLAVOR_PLATFORM_TEXT_DOMAIN), $mins);
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return sprintf(_n('Hace %d hora', 'Hace %d horas', $hours, FLAVOR_PLATFORM_TEXT_DOMAIN), $hours);
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return sprintf(_n('Hace %d día', 'Hace %d días', $days, FLAVOR_PLATFORM_TEXT_DOMAIN), $days);
        } else {
            return date_i18n(get_option('date_format'), $time);
        }
    }

    /**
     * Obtiene resumen de acciones pendientes para el usuario
     */
    private function get_pending_actions_summary() {
        $actions = [];
        $user_id = get_current_user_id();

        if (!$user_id) {
            return $actions;
        }

        global $wpdb;

        // Eventos próximos (próximos 7 días)
        $eventos_table = $wpdb->prefix . 'flavor_eventos';
        $asistentes_table = $wpdb->prefix . 'flavor_eventos_asistentes';
        if ($wpdb->get_var("SHOW TABLES LIKE '$eventos_table'") === $eventos_table) {
            $eventos = $wpdb->get_results($wpdb->prepare(
                "SELECT e.titulo, e.fecha FROM $eventos_table e
                 INNER JOIN $asistentes_table a ON e.id = a.evento_id
                 WHERE a.usuario_id = %d AND a.estado = 'confirmado'
                 AND e.fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 ORDER BY e.fecha ASC LIMIT 2",
                $user_id
            ), ARRAY_A);

            foreach ($eventos as $evento) {
                $actions[] = [
                    'icon' => '📅',
                    'text' => $evento['titulo'],
                    'url' => Flavor_Platform_Helpers::get_action_url('eventos', ''),
                    'module' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'priority' => 'normal',
                ];
            }
        }

        // Reservas pendientes
        $reservas_table = $wpdb->prefix . 'flavor_reservas';
        if ($wpdb->get_var("SHOW TABLES LIKE '$reservas_table'") === $reservas_table) {
            $reservas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $reservas_table
                 WHERE usuario_id = %d AND estado = 'pendiente'",
                $user_id
            ));

            if ($reservas > 0) {
                $actions[] = [
                    'icon' => '📆',
                    'text' => sprintf(_n('%d reserva pendiente', '%d reservas pendientes', $reservas, FLAVOR_PLATFORM_TEXT_DOMAIN), $reservas),
                    'url' => Flavor_Platform_Helpers::get_action_url('reservas', ''),
                    'module' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'priority' => 'high',
                ];
            }
        }

        // Trámites en proceso
        $tramites_table = $wpdb->prefix . 'tramites';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tramites_table'") === $tramites_table) {
            $tramites = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tramites_table
                 WHERE usuario_id = %d AND estado IN ('pendiente', 'en_revision')",
                $user_id
            ));

            if ($tramites > 0) {
                $actions[] = [
                    'icon' => '📋',
                    'text' => sprintf(_n('%d trámite activo', '%d trámites activos', $tramites, FLAVOR_PLATFORM_TEXT_DOMAIN), $tramites),
                    'url' => Flavor_Platform_Helpers::get_action_url('tramites', ''),
                    'module' => __('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'priority' => 'normal',
                ];
            }
        }

        // Votaciones abiertas
        $votaciones_table = $wpdb->prefix . 'votaciones';
        if ($wpdb->get_var("SHOW TABLES LIKE '$votaciones_table'") === $votaciones_table) {
            $votaciones = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $votaciones_table WHERE estado = 'activa' AND fecha_fin >= CURDATE()"
            );

            if ($votaciones > 0) {
                $actions[] = [
                    'icon' => '🗳️',
                    'text' => sprintf(_n('%d votación abierta', '%d votaciones abiertas', $votaciones, FLAVOR_PLATFORM_TEXT_DOMAIN), $votaciones),
                    'url' => Flavor_Platform_Helpers::get_action_url('participacion', ''),
                    'module' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'priority' => 'high',
                ];
            }
        }

        return $actions;
    }

    /**
     * Obtiene resumen de actividad reciente
     */
    private function get_recent_activity_summary() {
        $activity = [];
        $user_id = get_current_user_id();

        if (!$user_id) {
            return $activity;
        }

        global $wpdb;

        // Combinar actividad de varios módulos
        $activity_items = [];

        // ========================================
        // RED SOCIAL - Publicaciones recientes
        // ========================================
        $social_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        if ($wpdb->get_var("SHOW TABLES LIKE '$social_publicaciones'") === $social_publicaciones) {
            // Publicaciones recientes de la comunidad
            $publicaciones = $wpdb->get_results(
                "SELECT contenido, fecha_creacion as fecha, autor_id
                 FROM $social_publicaciones
                 WHERE estado = 'publicado'
                 AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 3 DAY)
                 ORDER BY fecha_creacion DESC LIMIT 3",
                ARRAY_A
            );
            foreach ($publicaciones as $pub) {
                $autor = get_userdata((int) $pub['autor_id']);
                $nombre_autor = $autor ? $autor->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
                $activity_items[] = [
                    'icon' => '🌐',
                    'text' => sprintf(__('%s publicó: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $nombre_autor, wp_trim_words($pub['contenido'], 6, '...')),
                    'time' => strtotime($pub['fecha']),
                ];
            }

            // Likes/reacciones recibidas
            $social_reacciones = $wpdb->prefix . 'flavor_social_reacciones';
            if ($wpdb->get_var("SHOW TABLES LIKE '$social_reacciones'") === $social_reacciones) {
                $reacciones = $wpdb->get_results($wpdb->prepare(
                    "SELECT r.tipo, r.fecha_creacion as fecha, p.contenido
                     FROM $social_reacciones r
                     INNER JOIN $social_publicaciones p ON r.publicacion_id = p.id
                     WHERE p.autor_id = %d
                     AND r.usuario_id != %d
                     AND r.fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)
                     ORDER BY r.fecha_creacion DESC LIMIT 2",
                    $user_id,
                    $user_id
                ), ARRAY_A);
                foreach ($reacciones as $reaccion) {
                    $activity_items[] = [
                        'icon' => '❤️',
                        'text' => __('Tu publicación recibió una reacción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'time' => strtotime($reaccion['fecha']),
                    ];
                }
            }
        }

        // ========================================
        // CHAT GRUPOS - Mensajes recientes
        // ========================================
        $chat_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $chat_miembros = $wpdb->prefix . 'flavor_chat_miembros';
        if ($wpdb->get_var("SHOW TABLES LIKE '$chat_mensajes'") === $chat_mensajes &&
            $wpdb->get_var("SHOW TABLES LIKE '$chat_miembros'") === $chat_miembros) {
            // Mensajes en grupos donde participo
            $mensajes = $wpdb->get_results($wpdb->prepare(
                "SELECT m.contenido, m.fecha_creacion as fecha, g.nombre as grupo_nombre
                 FROM $chat_mensajes m
                 INNER JOIN $chat_miembros mb ON m.grupo_id = mb.grupo_id
                 INNER JOIN {$wpdb->prefix}flavor_chat_grupos g ON m.grupo_id = g.id
                 WHERE mb.usuario_id = %d
                 AND m.autor_id != %d
                 AND m.fecha_creacion > DATE_SUB(NOW(), INTERVAL 2 DAY)
                 ORDER BY m.fecha_creacion DESC LIMIT 3",
                $user_id,
                $user_id
            ), ARRAY_A);
            foreach ($mensajes as $msg) {
                $activity_items[] = [
                    'icon' => '💭',
                    'text' => sprintf(__('Nuevo mensaje en %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $msg['grupo_nombre']),
                    'time' => strtotime($msg['fecha']),
                ];
            }
        }

        // ========================================
        // FOROS - Respuestas recientes
        // ========================================
        $foros_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';
        if ($wpdb->get_var("SHOW TABLES LIKE '$foros_respuestas'") === $foros_respuestas) {
            $respuestas = $wpdb->get_results(
                "SELECT contenido as texto, fecha_creacion as fecha
                 FROM $foros_respuestas
                 WHERE fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY fecha_creacion DESC LIMIT 3",
                ARRAY_A
            );
            foreach ($respuestas as $r) {
                $activity_items[] = [
                    'icon' => '💬',
                    'text' => __('Nueva respuesta en foro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'time' => strtotime($r['fecha']),
                ];
            }
        }

        // ========================================
        // EVENTOS - Inscripciones recientes
        // ========================================
        $eventos_asistentes = $wpdb->prefix . 'flavor_eventos_asistentes';
        if ($wpdb->get_var("SHOW TABLES LIKE '$eventos_asistentes'") === $eventos_asistentes) {
            $inscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT fecha_inscripcion as fecha
                 FROM $eventos_asistentes
                 WHERE usuario_id = %d AND fecha_inscripcion > DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY fecha_inscripcion DESC LIMIT 2",
                $user_id
            ), ARRAY_A);
            foreach ($inscripciones as $i) {
                $activity_items[] = [
                    'icon' => '📅',
                    'text' => __('Te inscribiste a un evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'time' => strtotime($i['fecha']),
                ];
            }
        }

        // ========================================
        // MARKETPLACE - Anuncios recientes
        // ========================================
        $marketplace = $wpdb->prefix . 'flavor_marketplace_anuncios';
        if ($wpdb->get_var("SHOW TABLES LIKE '$marketplace'") === $marketplace) {
            $anuncios = $wpdb->get_results(
                "SELECT titulo, fecha_creacion as fecha
                 FROM $marketplace
                 WHERE estado = 'publicado'
                 AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 3 DAY)
                 ORDER BY fecha_creacion DESC LIMIT 2",
                ARRAY_A
            );
            foreach ($anuncios as $anuncio) {
                $activity_items[] = [
                    'icon' => '🛒',
                    'text' => sprintf(__('Nuevo: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), wp_trim_words($anuncio['titulo'], 5, '...')),
                    'time' => strtotime($anuncio['fecha']),
                ];
            }
        }

        // Ordenar por fecha y formatear
        usort($activity_items, function($a, $b) {
            return $b['time'] - $a['time'];
        });

        foreach (array_slice($activity_items, 0, 6) as $item) {
            $activity[] = [
                'icon' => $item['icon'],
                'text' => $item['text'],
                'time_ago' => $this->time_ago($item['time']),
            ];
        }

        return $activity;
    }

    /**
     * LAYOUT: Compacto (Lista)
     */
    private function render_layout_compact($atts) {
        ?>
        <div class="fup-compact">
            <?php foreach (['transversal', 'base', 'vertical'] as $type) : ?>
            <?php if (!empty($this->modules_by_type[$type])) : ?>
            <div class="fup-compact-section">
                <ul class="fup-compact-list">
                    <?php foreach ($this->modules_by_type[$type] as $module) : ?>
                    <li class="fup-compact-item">
                        <a href="<?php echo esc_url($module['url']); ?>" style="--module-color: <?php echo esc_attr($module['color']); ?>">
                            <span class="dashicons <?php echo esc_attr($module['icon']); ?>"></span>
                            <span class="fup-compact-item__name"><?php echo esc_html($module['name']); ?></span>
                            <span class="fup-compact-item__type"><?php echo esc_html(ucfirst($type)); ?></span>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * LAYOUT: Dashboard (Widgets)
     */
    private function render_layout_dashboard($atts) {
        ?>
        <div class="fup-dashboard">
            <!-- Stats rápidas -->
            <?php if ($atts['show_stats'] === 'yes') : ?>
            <section class="fup-dash-stats">
                <?php
                $stats = $this->get_aggregated_stats();
                foreach ($stats as $stat) :
                ?>
                <div class="fup-dash-stat">
                    <span class="fup-dash-stat__value"><?php echo esc_html($stat['value']); ?></span>
                    <span class="fup-dash-stat__label"><?php echo esc_html($stat['label']); ?></span>
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <!-- Grid de widgets -->
            <div class="fup-dash-grid">
                <?php foreach ($this->modules_by_type['base'] as $base) : ?>
                <article class="fup-dash-widget fup-dash-widget--large" style="--module-color: <?php echo esc_attr($base['color']); ?>">
                    <header class="fup-dash-widget__header">
                        <span class="dashicons <?php echo esc_attr($base['icon']); ?>"></span>
                        <h3><?php echo esc_html($base['name']); ?></h3>
                    </header>
                    <div class="fup-dash-widget__content">
                        <?php if (!empty($base['satellites'])) : ?>
                        <div class="fup-dash-widget__satellites">
                            <?php foreach (array_slice($base['satellites'], 0, 4) as $sat) : ?>
                            <a href="<?php echo esc_url($sat['url']); ?>" class="fup-mini-link">
                                <span class="dashicons <?php echo esc_attr($sat['icon']); ?>"></span>
                                <?php echo esc_html($sat['name']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <footer class="fup-dash-widget__footer">
                        <a href="<?php echo esc_url($base['url']); ?>"><?php _e('Ver todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →</a>
                    </footer>
                </article>
                <?php endforeach; ?>

                <?php foreach ($this->modules_by_type['vertical'] as $module) : ?>
                <article class="fup-dash-widget" style="--module-color: <?php echo esc_attr($module['color']); ?>">
                    <header class="fup-dash-widget__header">
                        <span class="dashicons <?php echo esc_attr($module['icon']); ?>"></span>
                        <h3><?php echo esc_html($module['name']); ?></h3>
                    </header>
                    <footer class="fup-dash-widget__footer">
                        <a href="<?php echo esc_url($module['url']); ?>"><?php _e('Abrir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →</a>
                    </footer>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene estadísticas agregadas
     */
    private function get_aggregated_stats() {
        return [
            [
                'value' => count($this->modules_by_type['base']),
                'label' => __('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            [
                'value' => count($this->modules_by_type['vertical']),
                'label' => __('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            [
                'value' => count($this->modules_by_type['transversal']),
                'label' => __('Transversales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];
    }

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_login_required() {
        ob_start();
        ?>
        <div class="fup-login-required">
            <div class="fup-login-required__content">
                <span class="dashicons dashicons-lock"></span>
                <h2><?php _e('Acceso restringido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p><?php _e('Necesitas iniciar sesión para acceder a tu portal.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="fup-btn fup-btn--primary">
                    <?php _e('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Refrescar datos
     */
    public function ajax_refresh_data() {
        check_ajax_referer('flavor_unified_portal', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $this->load_user_data();
        $this->load_modules_data();

        wp_send_json_success([
            'user'    => $this->user_data,
            'modules' => $this->modules_by_type,
            'network' => $this->get_network_data(),
        ]);
    }
}

// Inicializar
Flavor_Unified_Portal::get_instance();
