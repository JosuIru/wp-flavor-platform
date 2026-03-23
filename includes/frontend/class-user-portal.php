<?php
/**
 * Portal de Usuario Unificado
 *
 * Vista centralizada donde el usuario accede a TODO su contenido
 * de todos los módulos activos con interconexión y acciones rápidas.
 *
 * @package FlavorChatIA
 * @subpackage Frontend
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Portal de Usuario Unificado
 */
class Flavor_User_Portal {

    /**
     * Instancia singleton
     *
     * @var Flavor_User_Portal|null
     */
    private static $instance = null;

    /**
     * Módulos con contenido personal
     *
     * @var array
     */
    private $personal_modules = [];

    /**
     * Widgets activos del usuario
     *
     * @var array
     */
    private $active_widgets = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_User_Portal
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Cargar perfiles de portal
        require_once FLAVOR_CHAT_IA_PATH . 'includes/frontend/class-portal-profiles.php';

        // Shortcode principal
        add_shortcode('flavor_my_portal', [$this, 'render_portal']);
        add_shortcode('flavor_user_hub', [$this, 'render_portal']); // Alias

        // Detectar shortcode
        add_action('wp', [$this, 'detect_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX endpoints
        add_action('wp_ajax_flavor_portal_get_module_data', [$this, 'ajax_get_module_data']);
        add_action('wp_ajax_flavor_portal_search', [$this, 'ajax_universal_search']);
        add_action('wp_ajax_flavor_portal_quick_action', [$this, 'ajax_quick_action']);
        add_action('wp_ajax_flavor_portal_get_notifications', [$this, 'ajax_get_notifications']);
        add_action('wp_ajax_flavor_portal_mark_notification_read', [$this, 'ajax_mark_notification_read']);
        add_action('wp_ajax_flavor_portal_save_widget_prefs', [$this, 'ajax_save_widget_preferences']);

        // Hook para que módulos registren su contenido personal
        add_action('init', [$this, 'register_personal_modules'], 25);
    }

    /**
     * Detecta si el shortcode está en la página
     */
    public function detect_shortcode() {
        global $post;
        if ($post && (has_shortcode($post->post_content, 'flavor_my_portal') ||
                      has_shortcode($post->post_content, 'flavor_user_hub'))) {
            $this->shortcode_present = true;
        }
    }

    /**
     * Encola assets solo si el shortcode está presente
     */
    public function enqueue_assets() {
        if (!isset($this->shortcode_present) || !$this->shortcode_present) {
            return;
        }

        $plugin_url = FLAVOR_CHAT_IA_URL;
        $version = FLAVOR_CHAT_IA_VERSION;

        // CSS del portal
        wp_enqueue_style(
            'flavor-user-portal',
            $plugin_url . 'assets/css/layouts/user-portal.css',
            ['fl-design-tokens'],
            $version
        );

        // CSS de componentes mejorados
        wp_enqueue_style(
            'flavor-dashboard-enhanced',
            $plugin_url . 'assets/css/dashboard-components-enhanced.css',
            [],
            $version
        );

        // JS del portal
        wp_enqueue_script(
            'flavor-user-portal',
            $plugin_url . 'assets/js/user-portal.js',
            ['jquery'],
            $version,
            true
        );

        // JS de componentes
        wp_enqueue_script(
            'flavor-dashboard-components',
            $plugin_url . 'assets/js/dashboard-components.js',
            ['jquery'],
            $version,
            true
        );

        // Localización
        wp_localize_script('flavor-user-portal', 'flavorPortal', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_portal_nonce'),
            'user_id' => get_current_user_id(),
            'strings' => [
                'loading' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al cargar datos', 'flavor-chat-ia'),
                'no_results' => __('No se encontraron resultados', 'flavor-chat-ia'),
                'search_placeholder' => __('Buscar en todos los módulos...', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Registra módulos con contenido personal
     */
    public function register_personal_modules() {
        // Módulos con contenido personal del usuario
        // NOTA: Todas las URLs ahora usan Flavor_Chat_Helpers::get_action_url() para mantener
        // el patrón canónico /mi-portal/MODULO/ACCION/
        $this->personal_modules = apply_filters('flavor_portal_personal_modules', [
            'socios' => [
                'label' => __('Socios', 'flavor-chat-ia'),
                'icon' => 'groups',
                'stats_callback' => [$this, 'get_socios_stats'],
                'widget_callback' => [$this, 'get_socios_widget'],
                'actions' => [
                    ['label' => __('Mi Cuota', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('socios', 'mi-cuota')],
                    ['label' => __('Renovar', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('socios', 'renovar')],
                ],
            ],
            'eventos' => [
                'label' => __('Eventos', 'flavor-chat-ia'),
                'icon' => 'calendar',
                'stats_callback' => [$this, 'get_eventos_stats'],
                'widget_callback' => [$this, 'get_eventos_widget'],
                'actions' => [
                    ['label' => __('Mis Inscripciones', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'mis-inscripciones')],
                    ['label' => __('Explorar Eventos', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'listado')],
                ],
            ],
            'grupos-consumo' => [
                'label' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'icon' => 'shopping_cart',
                'stats_callback' => [$this, 'get_grupos_consumo_stats'],
                'widget_callback' => [$this, 'get_grupos_consumo_widget'],
                'actions' => [
                    ['label' => __('Mis Pedidos', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'mis-pedidos')],
                    ['label' => __('Hacer Pedido', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'nuevo-pedido')],
                ],
            ],
            'marketplace' => [
                'label' => __('Marketplace', 'flavor-chat-ia'),
                'icon' => 'store',
                'stats_callback' => [$this, 'get_marketplace_stats'],
                'widget_callback' => [$this, 'get_marketplace_widget'],
                'actions' => [
                    ['label' => __('Mis Compras', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('marketplace', 'mis-compras')],
                    ['label' => __('Favoritos', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('marketplace', 'favoritos')],
                ],
            ],
            'reservas' => [
                'label' => __('Reservas', 'flavor-chat-ia'),
                'icon' => 'event_available',
                'stats_callback' => [$this, 'get_reservas_stats'],
                'widget_callback' => [$this, 'get_reservas_widget'],
                'actions' => [
                    ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('reservas', 'mis-reservas')],
                    ['label' => __('Nueva Reserva', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('reservas', 'nueva')],
                ],
            ],
            'cursos' => [
                'label' => __('Cursos', 'flavor-chat-ia'),
                'icon' => 'school',
                'stats_callback' => [$this, 'get_cursos_stats'],
                'widget_callback' => [$this, 'get_cursos_widget'],
                'actions' => [
                    ['label' => __('Mis Cursos', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('cursos', 'mis-cursos')],
                    ['label' => __('Certificados', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('cursos', 'certificados')],
                ],
            ],
            'biblioteca' => [
                'label' => __('Biblioteca', 'flavor-chat-ia'),
                'icon' => 'local_library',
                'stats_callback' => [$this, 'get_biblioteca_stats'],
                'widget_callback' => [$this, 'get_biblioteca_widget'],
                'actions' => [
                    ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('biblioteca', 'mis-prestamos')],
                    ['label' => __('Reservas', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('biblioteca', 'mis-reservas')],
                ],
            ],
            'banco-tiempo' => [
                'label' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'icon' => 'schedule',
                'stats_callback' => [$this, 'get_banco_tiempo_stats'],
                'widget_callback' => [$this, 'get_banco_tiempo_widget'],
                'actions' => [
                    ['label' => __('Mi Saldo', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('banco-tiempo', 'mi-saldo')],
                    ['label' => __('Intercambios', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('banco-tiempo', 'mis-intercambios')],
                ],
            ],
            'incidencias' => [
                'label' => __('Incidencias', 'flavor-chat-ia'),
                'icon' => 'report_problem',
                'stats_callback' => [$this, 'get_incidencias_stats'],
                'widget_callback' => [$this, 'get_incidencias_widget'],
                'actions' => [
                    ['label' => __('Mis Incidencias', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('incidencias', 'mis-incidencias')],
                    ['label' => __('Reportar', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('incidencias', 'nueva')],
                ],
            ],
            'foros' => [
                'label' => __('Foros', 'flavor-chat-ia'),
                'icon' => 'forum',
                'stats_callback' => [$this, 'get_foros_stats'],
                'widget_callback' => [$this, 'get_foros_widget'],
                'actions' => [
                    ['label' => __('Mis Temas', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('foros', 'mis-temas')],
                    ['label' => __('Nuevo Tema', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('foros', 'nuevo')],
                ],
            ],
        ]);

        // Filtrar solo módulos activos
        $active_modules = get_option('flavor_active_modules', []);
        $this->personal_modules = array_filter($this->personal_modules, function($key) use ($active_modules) {
            return in_array($key, $active_modules);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Renderiza el portal de usuario
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del portal
     */
    public function render_portal($atts = []) {
        // Verificar login
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        // Obtener configuración del perfil activo
        $portal_profiles = Flavor_Portal_Profiles::get_instance();
        $portal_config = $portal_profiles->get_active_portal_config();

        // Merge con atributos del shortcode (shortcode puede override)
        $atts = shortcode_atts([
            'layout' => $portal_config['layout'],
            'show_stats' => $portal_config['secciones']['stats']['mostrar'] ? 'yes' : 'no',
            'show_notifications' => $portal_config['notificaciones'] ? 'yes' : 'no',
            'show_search' => $portal_config['busqueda'] ? 'yes' : 'no',
        ], $atts);

        // Cargar componentes mejorados
        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-dashboard-components.php';
        $DC = 'Flavor_Dashboard_Components';

        ob_start();
        ?>
        <div class="flavor-user-portal flavor-portal--<?php echo esc_attr($portal_config['profile_slug']); ?>"
             data-layout="<?php echo esc_attr($atts['layout']); ?>">

            <!-- Hero adaptativo según perfil -->
            <?php if (isset($portal_config['secciones']['hero'])): ?>
                <?php echo $portal_profiles->render_hero($portal_config['secciones']['hero'], $user); ?>
            <?php endif; ?>

            <!-- Acciones Rápidas (solo si el perfil las tiene) -->
            <?php if (isset($portal_config['secciones']['acciones_rapidas'])): ?>
                <?php echo $portal_profiles->render_acciones_rapidas($portal_config['secciones']['acciones_rapidas']); ?>
            <?php endif; ?>

            <?php if ($atts['show_notifications'] === 'yes'): ?>
            <!-- Notificaciones Cross-Module -->
            <div id="portal-notifications" class="portal-notifications">
                <?php echo $this->render_notifications($user_id); ?>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_search'] === 'yes'): ?>
            <!-- Búsqueda Universal -->
            <div class="portal-search-container">
                <div class="portal-search">
                    <span class="dashicons dashicons-search"></span>
                    <input
                        type="text"
                        id="portal-search-input"
                        placeholder="<?php _e('Buscar...', 'flavor-chat-ia'); ?>"
                        autocomplete="off"
                    >
                    <div id="portal-search-results" class="portal-search-results" style="display:none;"></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_stats'] === 'yes'): ?>
            <!-- Estadísticas (solo las relevantes del perfil) -->
            <div class="portal-stats">
                <?php echo $this->render_profile_stats($user_id, $portal_config['secciones']['stats'], $DC); ?>
            </div>
            <?php endif; ?>

            <!-- Módulos Personales (filtrados por perfil) -->
            <div class="portal-modules portal-modules--<?php echo esc_attr($atts['layout']); ?>">
                <?php echo $this->render_profile_modules($user_id, $portal_config['secciones']['widgets'], $DC); ?>
            </div>

            <!-- Actividad Reciente (si el perfil la tiene) -->
            <?php if (isset($portal_config['secciones']['actividad']) && $portal_config['secciones']['actividad']['mostrar']): ?>
            <div class="portal-activity">
                <?php echo $this->render_recent_activity($user_id, $portal_config['secciones']['actividad'], $DC); ?>
            </div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_login_required() {
        $login_url = wp_login_url(get_permalink());
        $register_url = wp_registration_url();

        ob_start();
        ?>
        <div class="flavor-login-required">
            <div class="login-box">
                <span class="dashicons dashicons-lock"></span>
                <h3><?php _e('Acceso Restringido', 'flavor-chat-ia'); ?></h3>
                <p><?php _e('Debes iniciar sesión para acceder a tu portal personal', 'flavor-chat-ia'); ?></p>
                <div class="login-actions">
                    <a href="<?php echo esc_url($login_url); ?>" class="button button-primary">
                        <?php _e('Iniciar Sesión', 'flavor-chat-ia'); ?>
                    </a>
                    <?php if (get_option('users_can_register')): ?>
                    <a href="<?php echo esc_url($register_url); ?>" class="button button-secondary">
                        <?php _e('Registrarse', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza notificaciones cross-module
     */
    private function render_notifications($user_id) {
        $notifications = $this->get_user_notifications($user_id, 5);

        if (empty($notifications)) {
            return '';
        }

        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-dashboard-components.php';
        $DC = 'Flavor_Dashboard_Components';

        $html = '<div class="portal-notifications-list">';
        foreach ($notifications as $notif) {
            $type = isset($notif['type']) ? $notif['type'] : 'info';
            $html .= $DC::alert(
                '<strong>' . esc_html($notif['title']) . '</strong><br>' .
                '<small>' . esc_html($notif['message']) . '</small>',
                $type,
                true
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza estadísticas globales del usuario
     */
    private function render_global_stats($user_id, $DC) {
        $stats = [];

        // Obtener stats de cada módulo activo
        foreach ($this->personal_modules as $module_slug => $module_config) {
            if (isset($module_config['stats_callback']) && is_callable($module_config['stats_callback'])) {
                $module_stats = call_user_func($module_config['stats_callback'], $user_id);
                if (!empty($module_stats)) {
                    $stats = array_merge($stats, $module_stats);
                }
            }
        }

        // Si no hay stats, no mostrar nada
        if (empty($stats)) {
            return '';
        }

        return $DC::stats_grid($stats, 4);
    }

    /**
     * Renderiza stats filtradas por perfil
     */
    private function render_profile_stats($user_id, $stats_config, $DC) {
        $stats = [];
        $limite = isset($stats_config['limite']) ? $stats_config['limite'] : 4;
        $modulos = isset($stats_config['modulos']) ? $stats_config['modulos'] : 'auto';

        // Si es 'auto', usar todos los módulos personales
        if ($modulos === 'auto') {
            $modulos_filtro = array_keys($this->personal_modules);
        } else {
            $modulos_filtro = is_array($modulos) ? $modulos : [];
        }

        // Obtener stats solo de módulos filtrados
        foreach ($modulos_filtro as $module_slug) {
            if (!isset($this->personal_modules[$module_slug])) {
                continue;
            }

            $module_config = $this->personal_modules[$module_slug];
            if (isset($module_config['stats_callback']) && is_callable($module_config['stats_callback'])) {
                $module_stats = call_user_func($module_config['stats_callback'], $user_id);
                if (!empty($module_stats)) {
                    $stats = array_merge($stats, $module_stats);
                }
            }

            // Respetar el límite
            if (count($stats) >= $limite) {
                break;
            }
        }

        // Limitar número de stats
        $stats = array_slice($stats, 0, $limite);

        if (empty($stats)) {
            return '';
        }

        return $DC::stats_grid($stats, min($limite, 4));
    }

    /**
     * Renderiza módulos filtrados por perfil
     */
    private function render_profile_modules($user_id, $widgets_config, $DC) {
        $html = '';
        $orden = isset($widgets_config['orden']) ? $widgets_config['orden'] : 'auto';
        $mostrar_por_defecto = isset($widgets_config['mostrar_por_defecto']) ? $widgets_config['mostrar_por_defecto'] : 4;
        $colapsables = isset($widgets_config['colapsables']) ? $widgets_config['colapsables'] : true;

        // Si es 'auto', usar todos los módulos personales
        if ($orden === 'auto') {
            $modulos_ordenados = array_keys($this->personal_modules);
        } else {
            $modulos_ordenados = is_array($orden) ? $orden : [];
        }

        $contador = 0;
        foreach ($modulos_ordenados as $module_slug) {
            if (!isset($this->personal_modules[$module_slug])) {
                continue;
            }

            $module_config = $this->personal_modules[$module_slug];
            $widget_html = '';

            if (isset($module_config['widget_callback']) && is_callable($module_config['widget_callback'])) {
                $widget_html = call_user_func($module_config['widget_callback'], $user_id);
            }

            // Acciones rápidas
            $actions_html = '';
            if (!empty($module_config['actions'])) {
                $actions_html = '<div class="portal-module-actions">';
                foreach ($module_config['actions'] as $action) {
                    $actions_html .= sprintf(
                        '<a href="%s" class="portal-action-link">%s</a>',
                        esc_url($action['url']),
                        esc_html($action['label'])
                    );
                }
                $actions_html .= '</div>';
            }

            // Determinar si debe estar colapsado
            $contador++;
            $collapsed = ($contador > $mostrar_por_defecto);

            $html .= $DC::section(
                '<span class="dashicons dashicons-' . esc_attr($module_config['icon']) . '"></span> ' .
                esc_html($module_config['label']),
                $widget_html . $actions_html,
                [
                    'icon' => $module_config['icon'],
                    'collapsible' => $colapsables,
                    'collapsed' => $collapsed,
                ]
            );
        }

        return $html;
    }

    /**
     * Renderiza módulos personales del usuario
     */
    private function render_personal_modules($user_id, $atts, $DC) {
        $html = '';

        foreach ($this->personal_modules as $module_slug => $module_config) {
            $widget_html = '';

            if (isset($module_config['widget_callback']) && is_callable($module_config['widget_callback'])) {
                $widget_html = call_user_func($module_config['widget_callback'], $user_id);
            }

            // Acciones rápidas
            $actions_html = '';
            if (!empty($module_config['actions'])) {
                $actions_html = '<div class="portal-module-actions">';
                foreach ($module_config['actions'] as $action) {
                    $actions_html .= sprintf(
                        '<a href="%s" class="portal-action-link">%s</a>',
                        esc_url($action['url']),
                        esc_html($action['label'])
                    );
                }
                $actions_html .= '</div>';
            }

            $html .= $DC::section(
                '<span class="dashicons dashicons-' . esc_attr($module_config['icon']) . '"></span> ' .
                esc_html($module_config['label']),
                $widget_html . $actions_html,
                [
                    'icon' => $module_config['icon'],
                    'collapsible' => true,
                    'collapsed' => false,
                ]
            );
        }

        return $html;
    }

    /**
     * Renderiza actividad reciente cross-module
     */
    private function render_recent_activity($user_id, $activity_config, $DC) {
        $limite = isset($activity_config['limite']) ? $activity_config['limite'] : 10;
        $activities = $this->get_user_recent_activity($user_id, $limite);

        if (empty($activities)) {
            return '';
        }

        $table_data = [];
        foreach ($activities as $activity) {
            $table_data[] = [
                'module' => '<span class="dashicons dashicons-' . esc_attr($activity['icon']) . '"></span> ' .
                            esc_html($activity['module']),
                'action' => esc_html($activity['action']),
                'date' => date_i18n('d/m/Y H:i', strtotime($activity['date'])),
            ];
        }

        return $DC::data_table([
            'title' => __('Actividad Reciente', 'flavor-chat-ia'),
            'icon' => 'dashicons-clock',
            'columns' => [
                'module' => __('Módulo', 'flavor-chat-ia'),
                'action' => __('Acción', 'flavor-chat-ia'),
                'date' => __('Fecha', 'flavor-chat-ia'),
            ],
            'data' => $table_data,
            'compact' => true,
            'striped' => true,
        ]);
    }

    /**
     * Obtiene notificaciones del usuario de todos los módulos
     */
    private function get_user_notifications($user_id, $limit = 10) {
        $notifications = [];

        // Hook para que módulos añadan sus notificaciones
        $notifications = apply_filters('flavor_portal_user_notifications', $notifications, $user_id, $limit);

        // Ordenar por fecha descendente
        usort($notifications, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($notifications, 0, $limit);
    }

    /**
     * Obtiene actividad reciente del usuario
     */
    private function get_user_recent_activity($user_id, $limit = 20) {
        $activities = [];

        // Hook para que módulos añadan su actividad
        $activities = apply_filters('flavor_portal_user_activity', $activities, $user_id, $limit);

        // Ordenar por fecha descendente
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activities, 0, $limit);
    }

    // ========================================================================
    // CALLBACKS DE STATS Y WIDGETS POR MÓDULO
    // ========================================================================

    /**
     * Stats del módulo Socios
     */
    public function get_socios_stats($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d",
            $user_id
        ));

        if (!$socio) {
            return [];
        }

        return [[
            'value' => ucfirst($socio->estado),
            'label' => __('Estado Socio', 'flavor-chat-ia'),
            'icon' => 'dashicons-id',
            'color' => $socio->estado === 'activo' ? 'success' : 'warning',
        ]];
    }

    /**
     * Widget del módulo Socios
     */
    public function get_socios_widget($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d",
            $user_id
        ));

        if (!$socio) {
            return '<p>' . __('No eres socio aún', 'flavor-chat-ia') . '</p>';
        }

        return sprintf(
            '<div class="portal-widget-content">
                <p><strong>%s:</strong> %s</p>
                <p><strong>%s:</strong> %s</p>
                <p><strong>%s:</strong> %s</p>
            </div>',
            __('Número', 'flavor-chat-ia'),
            esc_html($socio->numero_socio),
            __('Tipo', 'flavor-chat-ia'),
            esc_html($socio->tipo_socio),
            __('Fecha alta', 'flavor-chat-ia'),
            date_i18n('d/m/Y', strtotime($socio->fecha_alta))
        );
    }

    /**
     * Stats del módulo Eventos
     */
    public function get_eventos_stats($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos_inscripciones';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
            $user_id
        ));

        $proximos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla i
             INNER JOIN {$wpdb->prefix}flavor_eventos e ON i.evento_id = e.id
             WHERE i.usuario_id = %d AND e.fecha_inicio >= NOW()",
            $user_id
        ));

        return [
            [
                'value' => number_format_i18n($total),
                'label' => __('Eventos Inscritos', 'flavor-chat-ia'),
                'icon' => 'dashicons-calendar',
                'color' => 'info',
            ],
            [
                'value' => number_format_i18n($proximos),
                'label' => __('Próximos', 'flavor-chat-ia'),
                'icon' => 'dashicons-arrow-right',
                'color' => 'warning',
            ],
        ];
    }

    /**
     * Widget del módulo Eventos
     */
    public function get_eventos_widget($user_id) {
        global $wpdb;

        $proximos = $wpdb->get_results($wpdb->prepare(
            "SELECT e.* FROM {$wpdb->prefix}flavor_eventos e
             INNER JOIN {$wpdb->prefix}flavor_eventos_inscripciones i ON e.id = i.evento_id
             WHERE i.usuario_id = %d AND e.fecha_inicio >= NOW()
             ORDER BY e.fecha_inicio ASC
             LIMIT 3",
            $user_id
        ));

        if (empty($proximos)) {
            return '<p>' . __('No tienes eventos próximos', 'flavor-chat-ia') . '</p>';
        }

        $html = '<ul class="portal-widget-list">';
        foreach ($proximos as $evento) {
            $html .= sprintf(
                '<li><strong>%s</strong><br><small>%s</small></li>',
                esc_html($evento->titulo),
                date_i18n('d/m/Y H:i', strtotime($evento->fecha_inicio))
            );
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Stats del módulo Grupos de Consumo
     */
    public function get_grupos_consumo_stats($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_pedidos';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $total_pedidos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
            $user_id
        ));

        $total_gastado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cantidad * precio_unitario) FROM $tabla
             WHERE usuario_id = %d AND estado = 'completado'",
            $user_id
        ));

        return [
            [
                'value' => number_format_i18n($total_pedidos),
                'label' => __('Pedidos', 'flavor-chat-ia'),
                'icon' => 'dashicons-cart',
                'color' => 'eco',
            ],
            [
                'value' => number_format_i18n($total_gastado, 2) . ' €',
                'label' => __('Total Gastado', 'flavor-chat-ia'),
                'icon' => 'dashicons-money',
                'color' => 'success',
            ],
        ];
    }

    /**
     * Widget del módulo Grupos de Consumo
     */
    public function get_grupos_consumo_widget($user_id) {
        global $wpdb;

        $ultimos_pedidos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_gc_pedidos
             WHERE usuario_id = %d
             ORDER BY fecha_pedido DESC
             LIMIT 3",
            $user_id
        ));

        if (empty($ultimos_pedidos)) {
            return '<p>' . __('No has realizado pedidos', 'flavor-chat-ia') . '</p>';
        }

        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-dashboard-components.php';
        $DC = 'Flavor_Dashboard_Components';

        $html = '<ul class="portal-widget-list">';
        foreach ($ultimos_pedidos as $pedido) {
            $estado_colors = [
                'pendiente' => 'warning',
                'confirmado' => 'info',
                'completado' => 'success',
                'cancelado' => 'error',
            ];
            $html .= sprintf(
                '<li>
                    <strong>Pedido #%s</strong> %s<br>
                    <small>%s - %s €</small>
                </li>',
                $pedido->id,
                $DC::badge($pedido->estado, $estado_colors[$pedido->estado] ?? 'secondary'),
                date_i18n('d/m/Y', strtotime($pedido->fecha_pedido)),
                number_format_i18n($pedido->cantidad * $pedido->precio_unitario, 2)
            );
        }
        $html .= '</ul>';

        return $html;
    }

    // Continuar con otros módulos (marketplace, reservas, cursos, etc.)
    // Por brevedad, solo muestro algunos ejemplos

    /**
     * Stats del módulo Reservas
     */
    public function get_reservas_stats($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $activas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla
             WHERE usuario_id = %d AND estado = 'confirmada' AND fecha_reserva >= NOW()",
            $user_id
        ));

        return [[
            'value' => number_format_i18n($activas),
            'label' => __('Reservas Activas', 'flavor-chat-ia'),
            'icon' => 'dashicons-calendar-alt',
            'color' => 'purple',
        ]];
    }

    /**
     * Widget del módulo Reservas
     */
    public function get_reservas_widget($user_id) {
        global $wpdb;

        $proximas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_reservas
             WHERE usuario_id = %d AND fecha_reserva >= NOW()
             ORDER BY fecha_reserva ASC
             LIMIT 3",
            $user_id
        ));

        if (empty($proximas)) {
            return '<p>' . __('No tienes reservas próximas', 'flavor-chat-ia') . '</p>';
        }

        $html = '<ul class="portal-widget-list">';
        foreach ($proximas as $reserva) {
            $html .= sprintf(
                '<li><strong>%s</strong><br><small>%s</small></li>',
                esc_html($reserva->recurso_nombre),
                date_i18n('d/m/Y H:i', strtotime($reserva->fecha_reserva))
            );
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Stats del módulo Marketplace
     */
    public function get_marketplace_stats($user_id) {
        $mis_anuncios = count(get_posts([
            'post_type' => 'marketplace_item',
            'author' => $user_id,
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => -1,
        ]));

        $publicados = count(get_posts([
            'post_type' => 'marketplace_item',
            'author' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]));

        return [
            [
                'value' => number_format_i18n($mis_anuncios),
                'label' => __('Mis Anuncios', 'flavor-chat-ia'),
                'icon' => 'dashicons-megaphone',
                'color' => 'info',
            ],
            [
                'value' => number_format_i18n($publicados),
                'label' => __('Publicados', 'flavor-chat-ia'),
                'icon' => 'dashicons-yes',
                'color' => 'success',
            ],
        ];
    }

    /**
     * Widget del módulo Marketplace
     */
    public function get_marketplace_widget($user_id) {
        $anuncios = get_posts([
            'post_type' => 'marketplace_item',
            'author' => $user_id,
            'post_status' => ['publish', 'pending'],
            'posts_per_page' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($anuncios)) {
            return '<p>' . __('No tienes anuncios publicados', 'flavor-chat-ia') . '</p>';
        }

        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-dashboard-components.php';
        $DC = 'Flavor_Dashboard_Components';

        $html = '<ul class="portal-widget-list">';
        foreach ($anuncios as $anuncio) {
            $tipo_term = get_the_terms($anuncio->ID, 'marketplace_tipo');
            $tipo = $tipo_term && !is_wp_error($tipo_term) ? $tipo_term[0]->name : '';

            $estado_colors = [
                'publish' => 'success',
                'pending' => 'warning',
                'draft' => 'secondary',
            ];

            $html .= sprintf(
                '<li>
                    <strong>%s</strong> %s<br>
                    <small>%s - %s</small>
                </li>',
                esc_html($anuncio->post_title),
                $DC::badge($anuncio->post_status, $estado_colors[$anuncio->post_status] ?? 'secondary'),
                esc_html($tipo),
                date_i18n('d/m/Y', strtotime($anuncio->post_date))
            );
        }
        $html .= '</ul>';

        return $html;
    }
    /**
     * Stats del módulo Cursos
     */
    public function get_cursos_stats($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos_matriculas';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $activos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE alumno_id = %d AND estado = 'activo'",
            $user_id
        ));

        $completados = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE alumno_id = %d AND estado = 'completado'",
            $user_id
        ));

        return [
            [
                'value' => number_format_i18n($activos),
                'label' => __('Cursos Activos', 'flavor-chat-ia'),
                'icon' => 'dashicons-book',
                'color' => 'info',
            ],
            [
                'value' => number_format_i18n($completados),
                'label' => __('Completados', 'flavor-chat-ia'),
                'icon' => 'dashicons-awards',
                'color' => 'success',
            ],
        ];
    }

    /**
     * Widget del módulo Cursos
     */
    public function get_cursos_widget($user_id) {
        global $wpdb;

        $cursos_activos = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.progreso
             FROM {$wpdb->prefix}flavor_cursos c
             INNER JOIN {$wpdb->prefix}flavor_cursos_matriculas m ON c.id = m.curso_id
             WHERE m.alumno_id = %d AND m.estado = 'activo'
             ORDER BY m.fecha_matricula DESC
             LIMIT 3",
            $user_id
        ));

        if (empty($cursos_activos)) {
            return '<p>' . __('No estás matriculado en ningún curso', 'flavor-chat-ia') . '</p>';
        }

        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-dashboard-components.php';
        $DC = 'Flavor_Dashboard_Components';

        $html = '<div class="portal-widget-content">';
        foreach ($cursos_activos as $curso) {
            $progreso = isset($curso->progreso) ? intval($curso->progreso) : 0;
            $html .= '<div style="margin-bottom: 15px;">';
            $html .= '<strong>' . esc_html($curso->titulo) . '</strong><br>';
            $html .= $DC::progress_bar($progreso, 100, sprintf(__('%d%% completado', 'flavor-chat-ia'), $progreso), 'info');
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }
    public function get_biblioteca_stats($user_id) { return []; }
    public function get_biblioteca_widget($user_id) { return ''; }
    public function get_banco_tiempo_stats($user_id) { return []; }
    public function get_banco_tiempo_widget($user_id) { return ''; }
    /**
     * Stats del módulo Incidencias
     */
    public function get_incidencias_stats($user_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return [];
        }

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE reportado_por = %d",
            $user_id
        ));

        $abiertas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE reportado_por = %d AND estado IN ('abierta', 'en_progreso')",
            $user_id
        ));

        $resueltas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE reportado_por = %d AND estado = 'resuelta'",
            $user_id
        ));

        return [
            [
                'value' => number_format_i18n($total),
                'label' => __('Mis Incidencias', 'flavor-chat-ia'),
                'icon' => 'dashicons-warning',
                'color' => 'info',
            ],
            [
                'value' => number_format_i18n($abiertas),
                'label' => __('Abiertas', 'flavor-chat-ia'),
                'icon' => 'dashicons-flag',
                'color' => 'warning',
            ],
            [
                'value' => number_format_i18n($resueltas),
                'label' => __('Resueltas', 'flavor-chat-ia'),
                'icon' => 'dashicons-yes-alt',
                'color' => 'success',
            ],
        ];
    }

    /**
     * Widget del módulo Incidencias
     */
    public function get_incidencias_widget($user_id) {
        global $wpdb;

        $incidencias_recientes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_incidencias
             WHERE reportado_por = %d
             ORDER BY fecha_reporte DESC
             LIMIT 3",
            $user_id
        ));

        if (empty($incidencias_recientes)) {
            return '<p>' . __('No has reportado incidencias', 'flavor-chat-ia') . '</p>';
        }

        require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/class-dashboard-components.php';
        $DC = 'Flavor_Dashboard_Components';

        $estado_labels = [
            'abierta' => __('Abierta', 'flavor-chat-ia'),
            'en_progreso' => __('En Progreso', 'flavor-chat-ia'),
            'resuelta' => __('Resuelta', 'flavor-chat-ia'),
            'cerrada' => __('Cerrada', 'flavor-chat-ia'),
        ];

        $estado_colors = [
            'abierta' => 'error',
            'en_progreso' => 'warning',
            'resuelta' => 'success',
            'cerrada' => 'secondary',
        ];

        $prioridad_icons = [
            'alta' => '🔴',
            'media' => '🟠',
            'baja' => '🟢',
        ];

        $html = '<ul class="portal-widget-list">';
        foreach ($incidencias_recientes as $incidencia) {
            $icono = isset($prioridad_icons[$incidencia->prioridad]) ? $prioridad_icons[$incidencia->prioridad] : '';
            $html .= sprintf(
                '<li>
                    %s <strong>%s</strong> %s<br>
                    <small>%s</small>
                </li>',
                $icono,
                esc_html($incidencia->titulo),
                $DC::badge(
                    $estado_labels[$incidencia->estado] ?? $incidencia->estado,
                    $estado_colors[$incidencia->estado] ?? 'secondary'
                ),
                date_i18n('d/m/Y', strtotime($incidencia->fecha_reporte))
            );
        }
        $html .= '</ul>';

        return $html;
    }
    public function get_foros_stats($user_id) { return []; }
    public function get_foros_widget($user_id) { return []; }

    // ========================================================================
    // AJAX ENDPOINTS
    // ========================================================================

    /**
     * AJAX: Obtiene datos de un módulo
     */
    public function ajax_get_module_data() {
        check_ajax_referer('flavor_portal_nonce', 'nonce');

        $module_slug = isset($_POST['module']) ? sanitize_text_field($_POST['module']) : '';
        $user_id = get_current_user_id();

        if (empty($module_slug) || !$user_id) {
            wp_send_json_error(['message' => __('Parámetros inválidos', 'flavor-chat-ia')]);
        }

        // Obtener datos del módulo
        $data = apply_filters("flavor_portal_module_data_{$module_slug}", [], $user_id);

        wp_send_json_success($data);
    }

    /**
     * AJAX: Búsqueda universal en todos los módulos
     */
    public function ajax_universal_search() {
        check_ajax_referer('flavor_portal_nonce', 'nonce');

        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $user_id = get_current_user_id();

        if (empty($search_term) || !$user_id) {
            wp_send_json_error(['message' => __('Término de búsqueda requerido', 'flavor-chat-ia')]);
        }

        $results = [];

        // Hook para que cada módulo añada sus resultados
        $results = apply_filters('flavor_portal_search_results', $results, $search_term, $user_id);

        wp_send_json_success($results);
    }

    /**
     * AJAX: Ejecuta acción rápida inter-módulo
     */
    public function ajax_quick_action() {
        check_ajax_referer('flavor_portal_nonce', 'nonce');

        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $module = isset($_POST['module']) ? sanitize_text_field($_POST['module']) : '';
        $data = isset($_POST['data']) ? wp_unslash($_POST['data']) : [];

        $result = apply_filters("flavor_portal_quick_action_{$action}", false, $module, $data, get_current_user_id());

        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(['message' => __('Acción no disponible', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Obtiene notificaciones
     */
    public function ajax_get_notifications() {
        check_ajax_referer('flavor_portal_nonce', 'nonce');

        $user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;

        $notifications = $this->get_user_notifications($user_id, $limit);

        wp_send_json_success($notifications);
    }

    /**
     * AJAX: Marca notificación como leída
     */
    public function ajax_mark_notification_read() {
        check_ajax_referer('flavor_portal_nonce', 'nonce');

        $notif_id = isset($_POST['notif_id']) ? intval($_POST['notif_id']) : 0;
        $user_id = get_current_user_id();

        if (!$notif_id || !$user_id) {
            wp_send_json_error();
        }

        // Hook para que el módulo correspondiente marque como leída
        do_action('flavor_portal_mark_notification_read', $notif_id, $user_id);

        wp_send_json_success();
    }

    /**
     * AJAX: Guarda preferencias de widgets del usuario
     */
    public function ajax_save_widget_preferences() {
        check_ajax_referer('flavor_portal_nonce', 'nonce');

        $user_id = get_current_user_id();
        $preferences = isset($_POST['preferences']) ? wp_unslash($_POST['preferences']) : [];

        if (!$user_id) {
            wp_send_json_error();
        }

        update_user_meta($user_id, 'flavor_portal_widget_prefs', $preferences);

        wp_send_json_success();
    }
}

// Inicializar
Flavor_User_Portal::get_instance();
