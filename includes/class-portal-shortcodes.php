<?php
/**
 * Shortcodes del Portal del Cliente
 *
 * Proporciona shortcodes para el portal de servicios y dashboard personalizado
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para shortcodes del portal del cliente
 */
class Flavor_Portal_Shortcodes {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la URL actual para redirects de login en shortcodes del portal.
     */
    private function get_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }

    /**
     * Obtiene la instancia singleton
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
        $this->register_shortcodes();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_ajax_flavor_toggle_portal_tool_favorite', [$this, 'ajax_toggle_portal_tool_favorite']);
    }

    /**
     * Encola estilos del portal
     */
    public function enqueue_styles() {
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        // Design tokens base
        wp_enqueue_style(
            'fl-design-tokens',
            FLAVOR_PLATFORM_URL . 'assets/css/core/design-tokens.css',
            [],
            FLAVOR_PLATFORM_VERSION
        );

        wp_enqueue_style(
            'flavor-portal',
            FLAVOR_PLATFORM_URL . 'assets/css/layouts/portal' . $suffix . '.css',
            ['fl-design-tokens'],
            FLAVOR_PLATFORM_VERSION
        );

        // CSS unificado para mejoras visuales
        wp_enqueue_style(
            'flavor-portal-unified',
            FLAVOR_PLATFORM_URL . 'assets/css/layouts/client-dashboard-unified.css',
            ['flavor-portal'],
            FLAVOR_PLATFORM_VERSION
        );

        wp_enqueue_script(
            'flavor-portal-tools',
            FLAVOR_PLATFORM_URL . 'assets/js/portal-tools.js',
            [],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-portal-tools', 'flavorPortalTools', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_portal_tools'),
            'strings' => [
                'saving' => __('Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'saveError' => __('No se pudo actualizar la herramienta favorita.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'addFavorite' => __('Añadir a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'removeFavorite' => __('Quitar de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'addedToFavorites' => __('Añadido a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'removedFromFavorites' => __('Quitado de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('flavor_servicios', [$this, 'render_servicios']);
        add_shortcode('flavor_mi_portal', [$this, 'render_mi_portal']);
        add_shortcode('flavor_modulos_grid', [$this, 'render_modulos_grid']);
        add_shortcode('flavor_dashboard_stats', [$this, 'render_dashboard_stats']);
    }

    /**
     * Renderiza la landing de servicios
     */
    public function render_servicios($atts) {
        $atts = shortcode_atts([
            'titulo' => __('Servicios de la Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitulo' => __('Descubre todo lo que tu comunidad tiene para ofrecer', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'mostrar_stats' => 'yes',
            'columnas' => '3',
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-servicios-landing">
            <!-- Hero Section -->
            <div class="flavor-hero flavor-hero--servicios">
                <div class="flavor-hero__content">
                    <h1 class="flavor-hero__title"><?php echo esc_html($atts['titulo']); ?></h1>
                    <p class="flavor-hero__subtitle"><?php echo esc_html($atts['subtitulo']); ?></p>

                    <?php if ($atts['mostrar_stats'] === 'yes') : ?>
                        <?php echo $this->render_stats(); ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Grid de Módulos -->
            <div class="flavor-servicios__grid">
                <?php echo $this->render_modulos_grid(['tipo' => 'servicios', 'columnas' => $atts['columnas']]); ?>
            </div>

            <?php if (!is_user_logged_in()) : ?>
                <!-- CTA de Registro -->
                <div class="flavor-servicios__cta">
                    <div class="flavor-cta-box">
                        <h2><?php _e('¿Quieres acceder a más servicios?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <p><?php _e('Regístrate para participar activamente en tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <a href="<?php echo wp_registration_url(); ?>" class="flavor-button flavor-button--primary">
                            <?php _e('Crear Cuenta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <a href="<?php echo wp_login_url(); ?>" class="flavor-button flavor-button--secondary">
                            <?php _e('Iniciar Sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el dashboard personalizado mejorado
     */
    public function render_mi_portal($atts) {
        // Requerir login
        if (!is_user_logged_in()) {
            return $this->render_login_gate();
        }

        // Verificar si debe usar el Portal Unificado según configuración
        $design_settings = get_option('flavor_design_settings', []);
        $portal_layout = $design_settings['portal_layout'] ?? 'ecosystem'; // Por defecto usa el nuevo portal

        // Si el layout NO es 'legacy', delegar al Portal Unificado
        if ($portal_layout !== 'legacy' && class_exists('Flavor_Unified_Portal')) {
            $unified_portal = Flavor_Unified_Portal::get_instance();
            $columns = is_array($atts) && isset($atts['columnas']) ? $atts['columnas'] : '3';
            return $unified_portal->render_portal([
                'layout'       => $portal_layout,
                'show_network' => 'yes',
                'show_stats'   => 'yes',
                'show_actions' => 'yes',
                'columns'      => $columns,
            ]);
        }

        // Layout legacy: usar implementación original
        $atts = shortcode_atts([
            'mostrar_actividad' => 'yes',
            'mostrar_notificaciones' => 'yes',
            'mostrar_breadcrumbs' => 'yes',
            'columnas' => '4',
        ], $atts);

        $current_user = wp_get_current_user();
        $ecosystem_overview = $this->get_portal_ecosystem_overview();

        ob_start();
        ?>
        <div class="flavor-mi-portal flavor-portal-hub">
            <?php if ($atts['mostrar_breadcrumbs'] === 'yes' && class_exists('Flavor_Breadcrumbs')) : ?>
                <?php echo Flavor_Breadcrumbs::render(); ?>
            <?php endif; ?>

            <!-- Hero Header -->
            <div class="flavor-portal__hero">
                <div class="flavor-portal__hero-content">
                    <div class="flavor-portal__greeting">
                        <div class="flavor-portal__eyebrow">
                            <?php _e('Nodo activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                        <h1 class="flavor-portal__title">
                            <?php
                            $hora = (int) current_time('H');
                            if ($hora < 12) {
                                printf(__('Buenos días, %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($current_user->display_name));
                            } elseif ($hora < 20) {
                                printf(__('Buenas tardes, %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($current_user->display_name));
                            } else {
                                printf(__('Buenas noches, %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($current_user->display_name));
                            }
                            ?>
                        </h1>
                        <p class="flavor-portal__subtitle">
                            <?php _e('Tu ecosistema activo y espacio de coordinación comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </div>

                    <div class="flavor-portal__header-actions">
                        <?php echo $this->render_header_actions(); ?>
                    </div>
                </div>
            </div>

            <?php if ($atts['mostrar_notificaciones'] === 'yes') : ?>
                <!-- Notificaciones Destacadas -->
                <?php $notifications_bar = $this->render_notifications_bar(); ?>
                <?php if (!empty($notifications_bar)) : ?>
                    <div class="flavor-portal__notifications-bar">
                        <div class="flavor-portal__notifications-head">
                            <h2 class="flavor-portal__notifications-title"><?php _e('Señales del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                            <p class="flavor-portal__notifications-subtitle"><?php _e('Alertas, avisos y movimientos relevantes que conviene atender antes de seguir.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                        <?php echo $notifications_bar; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($ecosystem_overview)) : ?>
                <div class="flavor-portal__section flavor-portal__section--ecosystems">
                    <div class="flavor-section-header">
                        <div>
                            <h2 class="flavor-section-title"><?php _e('Ecosistemas activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                            <p class="flavor-portal__section-subtitle">
                                <?php _e('Organizados por base comunitaria, satélites operativos y capas transversales.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                    </div>
                    <?php echo $this->render_portal_ecosystem_overview($ecosystem_overview); ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard de Estadísticas -->
            <div class="flavor-portal__section">
                <div class="flavor-section-header">
                    <div>
                        <h2 class="flavor-section-title"><?php _e('Resumen del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <p class="flavor-portal__section-subtitle">
                            <?php _e('Una vista rápida de tu actividad y participación reciente en el portal.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </div>
                    <a href="<?php echo Flavor_Platform_Helpers::get_action_url('servicios', ''); ?>" class="flavor-link-all">
                        <?php _e('Explorar servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                    </a>
                </div>
                <?php echo $this->render_dashboard_stats(['columnas' => $atts['columnas'], 'mostrar_titulo' => 'no']); ?>
            </div>

            <div class="flavor-portal__layout">
                <!-- Columna Principal -->
                <div class="flavor-portal__main">
                    <!-- Accesos Rápidos Mejorados -->
                    <div class="flavor-portal__section">
                        <h2 class="flavor-portal__section-title">
                            <span class="flavor-title-icon">⚡</span>
                            <?php esc_html_e('Herramientas de hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h2>
                        <p class="flavor-portal__section-subtitle">
                            <?php esc_html_e('Operar, coordinar y entender tu nodo desde un conjunto corto de herramientas priorizadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                        <?php echo $this->render_tool_severity_legend(); ?>
                        <?php echo $this->render_portal_priority_filter(); ?>
                        <?php echo $this->render_tool_focus_strip(); ?>
                        <?php echo $this->render_quick_actions_enhanced(); ?>
                    </div>

                    <?php if ($atts['mostrar_actividad'] === 'yes') : ?>
                        <!-- Feed de Actividad -->
                        <div class="flavor-portal__section">
                            <h2 class="flavor-portal__section-title">
                                <span class="flavor-title-icon">📋</span>
                                <?php _e('Actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="flavor-portal__section-subtitle">
                                <?php _e('Movimiento reciente en tus espacios, servicios y relaciones comunitarias.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                            <?php echo $this->render_activity_feed(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <aside class="flavor-portal__sidebar">
                    <!-- Widget de Perfil -->
                    <div class="flavor-portal__widget flavor-portal__widget--profile">
                        <div class="flavor-portal__widget-header">
                            <h3 class="flavor-portal__widget-title">
                                <?php _e('Tu base personal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <p class="flavor-portal__widget-subtitle">
                                <?php _e('Tu identidad, acceso y punto de partida dentro del portal.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                        <?php echo $this->render_profile_widget($current_user); ?>
                    </div>

                    <!-- Próximas Acciones -->
                    <div class="flavor-portal__widget">
                        <div class="flavor-portal__widget-header">
                            <h3 class="flavor-portal__widget-title">
                                <?php _e('Siguiente foco', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <p class="flavor-portal__widget-subtitle">
                                <?php _e('Eventos, reservas y compromisos cercanos que requieren atención.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                        <?php echo $this->render_upcoming_actions(); ?>
                    </div>

                    <!-- Enlaces Útiles -->
                    <div class="flavor-portal__widget">
                        <div class="flavor-portal__widget-header">
                            <h3 class="flavor-portal__widget-title">
                                <?php _e('Navegación útil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <p class="flavor-portal__widget-subtitle">
                                <?php _e('Accesos estables para orientarte y volver a los puntos clave del nodo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                        <?php echo $this->render_useful_links(); ?>
                    </div>
                </aside>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza pantalla de login requerido
     */
    private function render_login_gate() {
        ob_start();
        ?>
        <div class="flavor-login-gate">
            <div class="flavor-login-gate__card">
                <div class="flavor-login-gate__icon">🔐</div>
                <h2 class="flavor-login-gate__title">
                    <?php _e('Acceso a Mi Portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="flavor-login-gate__text">
                    <?php _e('Inicia sesión para acceder a tu panel de control personalizado y gestionar todos tus servicios comunitarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
                <div class="flavor-login-gate__actions">
                    <a href="<?php echo esc_url(wp_login_url($this->get_current_request_url())); ?>" class="flavor-button flavor-button--primary">
                        <?php _e('Iniciar Sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <?php if (get_option('users_can_register')) : ?>
                        <a href="<?php echo wp_registration_url(); ?>" class="flavor-button flavor-button--secondary">
                            <?php _e('Crear Cuenta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <style>
        .flavor-login-gate {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            padding: 40px 20px;
        }
        .flavor-login-gate__card {
            max-width: 500px;
            text-align: center;
            background: white;
            padding: 48px 40px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .flavor-login-gate__icon {
            font-size: 64px;
            margin-bottom: 24px;
        }
        .flavor-login-gate__title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 16px;
        }
        .flavor-login-gate__text {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.6;
            margin: 0 0 32px;
        }
        .flavor-login-gate__actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza acciones del header
     */
    private function render_header_actions() {
        ob_start();
        ?>
        <div class="flavor-header-actions">
            <a href="<?php echo Flavor_Platform_Helpers::get_action_url('', ''); ?>" class="flavor-header-action">
                <span class="flavor-header-action__icon">🧭</span>
                <span class="flavor-header-action__text"><?php _e('Ver ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo home_url('/servicios/'); ?>" class="flavor-header-action">
                <span class="flavor-header-action__icon">🔍</span>
                <span class="flavor-header-action__text"><?php _e('Explorar servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo admin_url('profile.php'); ?>" class="flavor-header-action">
                <span class="flavor-header-action__icon">👤</span>
                <span class="flavor-header-action__text"><?php _e('Abrir perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza barra de notificaciones
     * Integrado con Flavor_Notification_Center
     */
    private function render_notifications_bar() {
        $notifications = $this->get_user_notifications();

        if (empty($notifications)) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-notifications-bar">
            <?php foreach (array_slice($notifications, 0, 3) as $notification) : ?>
                <div class="flavor-notification-item flavor-notification-item--<?php echo esc_attr($notification['type']); ?>" data-severity="<?php echo esc_attr($notification['severity_slug'] ?? 'stable'); ?>">
                    <span class="flavor-notification-item__icon"><?php echo $notification['icon']; ?></span>
                    <div class="flavor-notification-item__content">
                        <div class="flavor-notification-item__meta">
                            <span class="flavor-notification-item__severity flavor-notification-item__severity--<?php echo esc_attr($notification['severity_slug'] ?? 'stable'); ?>" <?php if (!empty($notification['severity_reason'])) : ?>title="<?php echo esc_attr($notification['severity_reason']); ?>"<?php endif; ?>>
                                <?php echo esc_html($notification['severity_label'] ?? __('Estable', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </span>
                        </div>
                        <span class="flavor-notification-item__text"><?php echo esc_html($notification['text']); ?></span>
                    </div>
                    <?php if (!empty($notification['link'])) : ?>
                        <a href="<?php echo esc_url($notification['link']); ?>" class="flavor-notification-item__action">
                            <?php _e('Abrir señal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Expone las señales del nodo para otras vistas del portal sin duplicar lógica.
     */
    public function render_shared_notifications_bar() {
        if (!is_user_logged_in()) {
            return '';
        }

        return $this->render_notifications_bar();
    }

    /**
     * Obtiene notificaciones del usuario desde el sistema real
     */
    private function get_user_notifications() {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return [];
        }

        $notifications = [];
        $native_notifications = $this->get_portal_native_notifications($user_id);
        $native_modules = [];

        foreach ($native_notifications as $notification) {
            $notifications[] = $notification;
            if (!empty($notification['module_id'])) {
                $native_modules[] = sanitize_key((string) $notification['module_id']);
            }
        }

        $native_modules = array_values(array_unique(array_filter($native_modules)));

        // Verificar si existe el centro de notificaciones
        if ( ! class_exists( 'Flavor_Notification_Center' ) ) {
            usort($notifications, [$this, 'compare_portal_notifications']);
            return array_slice($notifications, 0, 5);
        }

        $notification_center = Flavor_Notification_Center::get_instance();
        $raw_notifications = $notification_center->get_notifications( $user_id, [
            'unread_only' => false,
            'limit'       => 5,
        ] );

        if ( empty( $raw_notifications ) ) {
            usort($notifications, [$this, 'compare_portal_notifications']);
            return array_slice($notifications, 0, 5);
        }

        // Mapeo de tipos a iconos
        $type_icons = [
            'info'    => 'ℹ️',
            'success' => '✅',
            'warning' => '⚠️',
            'error'   => '❌',
        ];

        foreach ( $raw_notifications as $notification ) {
            $type = isset( $notification['type'] ) ? $notification['type'] : 'info';
            $severity_slug = $this->get_portal_notification_severity($type);
            $severity_label = $this->get_tool_severity_label($severity_slug);
            $severity_reason = '';
            $module_id = $this->infer_notification_module_id($notification);

            if ($module_id !== '' && in_array($module_id, $native_modules, true)) {
                continue;
            }

            if ($module_id !== '') {
                $native_severity = $this->get_module_tool_native_severity($module_id);
                if (!empty($native_severity['slug'])) {
                    $severity_slug = $native_severity['slug'];
                    $severity_label = $native_severity['label'];
                    $severity_reason = $native_severity['reason'];
                }
            }

            $notifications[] = [
                'type' => $type,
                'icon' => isset( $type_icons[ $type ] ) ? $type_icons[ $type ] : 'ℹ️',
                'text' => isset( $notification['title'] ) ? $notification['title'] : '',
                'link' => isset( $notification['link'] ) ? $notification['link'] : '',
                'module_id' => $module_id,
                'severity_slug' => $severity_slug,
                'severity_label' => $severity_label,
                'severity_reason' => $severity_reason,
            ];
        }

        usort($notifications, [$this, 'compare_portal_notifications']);
        return array_slice($notifications, 0, 5);
    }

    /**
     * Ordena señales del nodo por prioridad semántica.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    private function compare_portal_notifications($a, $b) {
        $severity_weight = [
            'attention' => 300,
            'followup' => 200,
            'stable' => 100,
        ];

        $module_weight = [
            'avisos_municipales' => 90,
            'incidencias' => 85,
            'energia_comunitaria' => 80,
            'participacion' => 75,
            'socios' => 70,
            'eventos' => 65,
            'reservas' => 60,
            'tramites' => 55,
            'grupos_consumo' => 50,
            'notificaciones' => 45,
            'anuncios' => 40,
        ];

        $type_weight = [
            'error' => 30,
            'warning' => 20,
            'info' => 10,
            'success' => 0,
        ];

        $score_a = ($severity_weight[sanitize_key((string) ($a['severity_slug'] ?? 'stable'))] ?? 0)
            + ($module_weight[sanitize_key((string) ($a['module_id'] ?? ''))] ?? 0)
            + ($type_weight[sanitize_key((string) ($a['type'] ?? 'info'))] ?? 0);

        $score_b = ($severity_weight[sanitize_key((string) ($b['severity_slug'] ?? 'stable'))] ?? 0)
            + ($module_weight[sanitize_key((string) ($b['module_id'] ?? ''))] ?? 0)
            + ($type_weight[sanitize_key((string) ($b['type'] ?? 'info'))] ?? 0);

        if ($score_a === $score_b) {
            return strcmp((string) ($a['text'] ?? ''), (string) ($b['text'] ?? ''));
        }

        return $score_b <=> $score_a;
    }

    /**
     * Renderiza accesos rápidos mejorados
     */
    private function render_quick_actions_enhanced() {
        $accesos = $this->get_quick_actions_smart();

        if (empty($accesos)) {
            return '<p class="flavor-no-content">' . __('Todavía no hay herramientas priorizadas para este nodo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-quick-actions-grid">
            <?php foreach ($accesos as $acceso) : ?>
                <article class="flavor-quick-action-card <?php echo !empty($acceso['is_favorite']) ? 'is-favorite' : ''; ?>" data-severity="<?php echo esc_attr($acceso['severity_slug'] ?? 'stable'); ?>">
                    <?php echo $this->render_tool_favorite_button($acceso); ?>
                    <a href="<?php echo esc_url($acceso['url']); ?>" class="flavor-quick-action-card__link">
                    <?php if (!empty($acceso['kind_label']) || !empty($acceso['context_label'])) : ?>
                        <div class="flavor-quick-action-card__meta">
                            <?php if (!empty($acceso['kind_label'])) : ?>
                                <span class="flavor-quick-action-card__badge"><?php echo esc_html($acceso['kind_label']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($acceso['severity_label'])) : ?>
                                <span class="flavor-quick-action-card__severity flavor-quick-action-card__severity--<?php echo esc_attr($acceso['severity_slug'] ?? 'stable'); ?>" <?php if (!empty($acceso['severity_reason'])) : ?>title="<?php echo esc_attr($acceso['severity_reason']); ?>"<?php endif; ?>>
                                    <?php echo esc_html($acceso['severity_label']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($acceso['context_label'])) : ?>
                                <span class="flavor-quick-action-card__context"><?php echo esc_html($acceso['context_label']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="flavor-quick-action-card__icon">
                        <?php echo $acceso['icon']; ?>
                    </div>
                    <div class="flavor-quick-action-card__content">
                        <h4 class="flavor-quick-action-card__title"><?php echo esc_html($acceso['title']); ?></h4>
                        <?php if (!empty($acceso['description'])) : ?>
                            <p class="flavor-quick-action-card__description"><?php echo esc_html($acceso['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="flavor-quick-action-card__arrow">→</span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el botón de favorito de una herramienta.
     *
     * @param array $tool
     * @return string
     */
    private function render_tool_favorite_button($tool) {
        if (empty($tool['id'])) {
            return '';
        }

        ob_start();
        ?>
        <button type="button"
                class="flavor-tool-favorite-toggle <?php echo !empty($tool['is_favorite']) ? 'is-active' : ''; ?>"
                data-tool-id="<?php echo esc_attr($tool['id']); ?>"
                aria-pressed="<?php echo !empty($tool['is_favorite']) ? 'true' : 'false'; ?>"
                title="<?php echo esc_attr(!empty($tool['is_favorite']) ? __('Quitar de favoritas', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Guardar en favoritas', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>">
            <span class="flavor-tool-favorite-toggle__icon" aria-hidden="true">★</span>
            <span class="screen-reader-text">
                <?php echo esc_html(!empty($tool['is_favorite']) ? __('Quitar de favoritas', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Guardar en favoritas', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
            </span>
        </button>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una banda corta con herramientas favoritas.
     *
     * @return string
     */
    private function render_tool_focus_strip() {
        $favoritas = $this->get_quick_actions_smart([
            'limit' => 3,
            'favorites_only' => true,
        ]);

        if (empty($favoritas)) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-tool-focus">
            <div class="flavor-tool-focus__header">
                <h3 class="flavor-tool-focus__title"><?php esc_html_e('Favoritas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="flavor-tool-focus__subtitle"><?php esc_html_e('Herramientas con más retorno inmediato para moverte por tu ecosistema activo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="flavor-tool-focus__grid">
                <?php foreach ($favoritas as $herramienta) : ?>
                    <a href="<?php echo esc_url($herramienta['url']); ?>" class="flavor-tool-focus__item">
                        <span class="flavor-tool-focus__icon"><?php echo $herramienta['icon']; ?></span>
                        <span class="flavor-tool-focus__text">
                            <strong><?php echo esc_html($herramienta['title']); ?></strong>
                            <?php if (!empty($herramienta['kind_label'])) : ?>
                                <small><?php echo esc_html($herramienta['kind_label']); ?></small>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una leyenda breve de prioridad para herramientas.
     *
     * @return string
     */
    private function render_tool_severity_legend() {
        ob_start();
        ?>
        <div class="flavor-tool-severity-legend" aria-label="<?php esc_attr_e('Leyenda de prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <span class="flavor-tool-severity-legend__label"><?php esc_html_e('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <span class="flavor-tool-severity-legend__item">
                <span class="flavor-tool-severity-legend__dot flavor-tool-severity-legend__dot--attention" aria-hidden="true"></span>
                <?php esc_html_e('Atención', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <span class="flavor-tool-severity-legend__item">
                <span class="flavor-tool-severity-legend__dot flavor-tool-severity-legend__dot--followup" aria-hidden="true"></span>
                <?php esc_html_e('Seguimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <span class="flavor-tool-severity-legend__item">
                <span class="flavor-tool-severity-legend__dot flavor-tool-severity-legend__dot--stable" aria-hidden="true"></span>
                <?php esc_html_e('Estable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza filtro rápido de prioridad para la home del portal.
     *
     * @return string
     */
    private function render_portal_priority_filter() {
        ob_start();
        ?>
        <div class="flavor-priority-filter" role="group" aria-label="<?php esc_attr_e('Filtrar por prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <span class="flavor-priority-filter__label"><?php esc_html_e('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <button type="button" class="flavor-priority-filter__btn is-active" data-priority="all" aria-pressed="true"><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="flavor-priority-filter__btn" data-priority="attention" aria-pressed="false"><?php esc_html_e('Atención', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="flavor-priority-filter__btn" data-priority="followup" aria-pressed="false"><?php esc_html_e('Seguimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="flavor-priority-filter__btn" data-priority="stable" aria-pressed="false"><?php esc_html_e('Estable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene una lectura ecosistémica simplificada para la home de mi portal.
     *
     * @return array
     */
    private function get_portal_ecosystem_overview() {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $loaded_modules = $loader->get_loaded_modules();

        if (empty($loaded_modules) || !is_array($loaded_modules)) {
            return [];
        }

        $modules_index = [];

        foreach ($loaded_modules as $module_id => $module) {
            if (!is_object($module) || !method_exists($module, 'get_name')) {
                continue;
            }

            $ecosystem = method_exists($module, 'get_ecosystem_metadata')
                ? (array) $module->get_ecosystem_metadata()
                : [];
            $dashboard = method_exists($module, 'get_dashboard_metadata')
                ? (array) $module->get_dashboard_metadata()
                : [];

            $modules_index[$module_id] = [
                'id' => $module_id,
                'name' => $module->get_name(),
                'description' => method_exists($module, 'get_description') ? $module->get_description() : '',
                'icon' => method_exists($module, 'get_icon') ? $module->get_icon() : 'dashicons-admin-plugins',
                'color' => method_exists($module, 'get_color') ? $module->get_color() : '#3b82f6',
                'url' => Flavor_Platform_Helpers::get_action_url($module_id, ''),
                'role' => $ecosystem['module_role'] ?? 'vertical',
                'ecosystem' => $ecosystem,
                'dashboard' => $dashboard,
            ];
        }

        $nodes = [];

        foreach ($modules_index as $module_id => $module_meta) {
            if (($module_meta['role'] ?? 'vertical') !== 'base') {
                continue;
            }

            $nodes[$module_id] = [
                'id' => $module_id,
                'name' => $module_meta['name'],
                'description' => $module_meta['description'],
                'icon' => $module_meta['icon'],
                'color' => $module_meta['color'],
                'url' => $module_meta['url'],
                'satellites' => [],
                'transversals' => [],
            ];
        }

        foreach ($modules_index as $module_id => $module_meta) {
            if (($module_meta['role'] ?? 'vertical') !== 'vertical') {
                continue;
            }

            $parent_module = $module_meta['dashboard']['parent_module'] ?? '';
            if (!$parent_module || !isset($nodes[$parent_module])) {
                continue;
            }

            $nodes[$parent_module]['satellites'][$module_id] = [
                'id' => $module_id,
                'name' => $module_meta['name'],
                'url' => $module_meta['url'],
            ];
        }

        foreach ($modules_index as $module_id => $module_meta) {
            if (($module_meta['role'] ?? 'vertical') !== 'transversal') {
                continue;
            }

            $related_modules = array_unique(array_filter(array_merge(
                (array) ($module_meta['ecosystem']['supports_modules'] ?? []),
                (array) ($module_meta['ecosystem']['measures_modules'] ?? []),
                (array) ($module_meta['ecosystem']['governs_modules'] ?? []),
                (array) ($module_meta['ecosystem']['teaches_modules'] ?? []),
                (array) ($module_meta['ecosystem']['base_for_modules'] ?? [])
            )));

            foreach ($nodes as $node_id => &$node) {
                $node_related_ids = array_merge([$node_id], array_keys($node['satellites']));
                if (!array_intersect($related_modules, $node_related_ids)) {
                    continue;
                }

                $node['transversals'][$module_id] = [
                    'id' => $module_id,
                    'name' => $module_meta['name'],
                    'url' => $module_meta['url'],
                ];
            }
            unset($node);
        }

        foreach ($nodes as &$node) {
            $node['satellites'] = array_values($node['satellites']);
            $node['transversals'] = array_values($node['transversals']);
            $node['satellite_count'] = count($node['satellites']);
            $node['transversal_count'] = count($node['transversals']);
        }
        unset($node);

        return array_values($nodes);
    }

    /**
     * Renderiza la lectura ecosistémica principal de mi portal.
     *
     * @param array $ecosystem_overview
     * @return string
     */
    private function render_portal_ecosystem_overview($ecosystem_overview) {
        if (empty($ecosystem_overview)) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-portal-ecosystems">
            <?php foreach ($ecosystem_overview as $node) : ?>
                <article class="flavor-portal-ecosystem-card">
                    <div class="flavor-portal-ecosystem-card__head">
                        <div class="flavor-portal-ecosystem-card__identity">
                            <span class="flavor-portal-ecosystem-card__icon" style="background: <?php echo esc_attr($node['color']); ?>15; color: <?php echo esc_attr($node['color']); ?>;">
                                <span class="dashicons <?php echo esc_attr($node['icon']); ?>"></span>
                            </span>
                            <div>
                                <h3 class="flavor-portal-ecosystem-card__title"><?php echo esc_html($node['name']); ?></h3>
                                <?php if (!empty($node['description'])) : ?>
                                    <p class="flavor-portal-ecosystem-card__description"><?php echo esc_html(wp_trim_words($node['description'], 18)); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flavor-portal-ecosystem-card__stats">
                            <span><?php echo esc_html($node['satellite_count']); ?> <?php esc_html_e('satélites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span><?php echo esc_html($node['transversal_count']); ?> <?php esc_html_e('capas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($node['satellites'])) : ?>
                        <div class="flavor-portal-ecosystem-card__block">
                            <div class="flavor-portal-ecosystem-card__label"><?php esc_html_e('Satélites operativos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                            <div class="flavor-portal-ecosystem-card__tags">
                                <?php foreach ($node['satellites'] as $satellite) : ?>
                                    <a href="<?php echo esc_url($satellite['url']); ?>" class="flavor-portal-ecosystem-card__tag">
                                        <?php echo esc_html($satellite['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($node['transversals'])) : ?>
                        <div class="flavor-portal-ecosystem-card__block">
                            <div class="flavor-portal-ecosystem-card__label"><?php esc_html_e('Capas transversales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                            <div class="flavor-portal-ecosystem-card__tags">
                                <?php foreach ($node['transversals'] as $transversal) : ?>
                                    <a href="<?php echo esc_url($transversal['url']); ?>" class="flavor-portal-ecosystem-card__tag flavor-portal-ecosystem-card__tag--transversal">
                                        <?php echo esc_html($transversal['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="flavor-portal-ecosystem-card__actions">
                        <a href="<?php echo esc_url($node['url']); ?>" class="flavor-button flavor-button--secondary">
                            <?php esc_html_e('Abrir ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene la configuración de acción rápida para un módulo específico.
     *
     * Primero busca en el mapa de configuraciones especiales (módulos con UX personalizada).
     * Si no existe, genera una configuración por defecto usando los metadatos del módulo.
     *
     * @param string $module_id ID del módulo.
     * @param object $instance  Instancia del módulo.
     * @return array Configuración de acción rápida.
     */
    private function get_module_quick_action_config($module_id, $instance) {
        // Mapa de configuraciones especiales para módulos con UX personalizada
        $configuraciones_especiales = [
            'eventos' => [
                'icon' => '📅',
                'title' => __('Activar encuentro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Convoca un evento y mueve la agenda compartida del nodo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => Flavor_Platform_Helpers::get_action_url('eventos', 'crear'),
                'kind' => 'coordinar',
                'contexts' => ['eventos', 'agenda', 'comunidad'],
                'favorite_weight' => 82,
            ],
            'talleres' => [
                'icon' => '🎓',
                'title' => __('Proponer taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Abre un espacio de aprendizaje y circulación de saberes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => Flavor_Platform_Helpers::get_action_url('talleres', 'crear'),
                'kind' => 'coordinar',
                'contexts' => ['aprendizaje', 'saberes', 'comunidad'],
                'favorite_weight' => 68,
            ],
            'ayuda_vecinal' => [
                'icon' => '🤝',
                'title' => __('Pedir ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Activa la red de cuidados cercana cuando hace falta apoyo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => Flavor_Platform_Helpers::get_action_url('ayuda_vecinal', 'solicitar'),
                'kind' => 'operar',
                'contexts' => ['cuidados', 'solidaridad', 'comunidad'],
                'favorite_weight' => 88,
            ],
            'banco_tiempo' => [
                'icon' => '⏰',
                'title' => __('Ofrecer tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Comparte una capacidad concreta dentro de la red de intercambio.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => Flavor_Platform_Helpers::get_action_url('banco_tiempo', 'ofrecer'),
                'kind' => 'operar',
                'contexts' => ['comunidad', 'intercambio', 'cuidados'],
                'favorite_weight' => 80,
            ],
            'grupos_consumo' => [
                'icon' => '🥬',
                'title' => __('Explorar catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Revisa consumo local y ciclos activos de compra compartida.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'productos'),
                'kind' => 'entender',
                'contexts' => ['consumo', 'comunidad', 'sostenibilidad'],
                'favorite_weight' => 76,
            ],
            'incidencias' => [
                'icon' => '🔧',
                'title' => __('Reportar incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Lanza una señal útil para resolver un problema del entorno.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => Flavor_Platform_Helpers::get_action_url('incidencias', 'crear'),
                'kind' => 'operar',
                'contexts' => ['comunidad', 'actividad'],
                'favorite_weight' => 66,
            ],
            'energia_comunitaria' => [
                'icon' => '⚡',
                'title' => __('Registrar producción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Actualiza lecturas y sigue el pulso energético de la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => Flavor_Platform_Helpers::get_action_url('energia_comunitaria', 'registrar-lectura'),
                'kind' => 'operar',
                'contexts' => ['energia', 'sostenibilidad', 'comunidad'],
                'favorite_weight' => 90,
            ],
            'participacion' => [
                'icon' => '🗳️',
                'title' => __('Entrar en decisiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Revisa propuestas y votaciones activas del nodo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => Flavor_Platform_Helpers::get_action_url('participacion', 'votaciones'),
                'kind' => 'coordinar',
                'contexts' => ['participacion', 'gobernanza', 'comunidad'],
                'favorite_weight' => 84,
            ],
            'transparencia' => [
                'icon' => '📘',
                'title' => __('Abrir recursos comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Consulta memoria, actas e indicadores clave del ecosistema.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => Flavor_Platform_Helpers::get_action_url('transparencia', 'presupuesto'),
                'kind' => 'entender',
                'contexts' => ['transparencia', 'gobernanza', 'impacto'],
                'favorite_weight' => 72,
            ],
        ];

        // Si existe configuración especial, usarla
        if (isset($configuraciones_especiales[$module_id])) {
            return $configuraciones_especiales[$module_id];
        }

        // Generar configuración por defecto usando metadatos del módulo
        $module_slug = str_replace('_', '-', sanitize_title($module_id));
        $icono_modulo = '';
        $nombre_modulo = ucfirst(str_replace('_', ' ', $module_id));
        $descripcion_modulo = '';

        if (is_object($instance)) {
            if (method_exists($instance, 'get_icon')) {
                $icono_modulo = $instance->get_icon();
            }
            if (method_exists($instance, 'get_name')) {
                $nombre_modulo = $instance->get_name() ?: $nombre_modulo;
            }
            if (method_exists($instance, 'get_description')) {
                $descripcion_modulo = $instance->get_description();
            }
        }

        // Si el icono es un dashicon, convertirlo a emoji genérico o mantenerlo
        $icono_final = $icono_modulo;
        if (empty($icono_final) || strpos($icono_final, 'dashicons-') === 0) {
            $icono_final = 'dashicons-admin-generic';
        }

        return [
            'icon' => $icono_final,
            'title' => $nombre_modulo,
            'description' => $descripcion_modulo ?: sprintf(
                /* translators: %s: module name */
                __('Accede a las funcionalidades de %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $nombre_modulo
            ),
            'url' => Flavor_Platform_Helpers::get_action_url(str_replace('-', '_', $module_slug), ''),
            'kind' => 'operar',
            'contexts' => ['comunidad'],
            'favorite_weight' => 50,
        ];
    }

    /**
     * Obtiene accesos rápidos inteligentes basados en módulos activos
     */
    private function get_quick_actions_smart($args = []) {
        $args = wp_parse_args($args, [
            'limit' => 6,
            'favorites_only' => false,
        ]);

        $accesos = [];
        $loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;

        if (!$loader) {
            return $accesos;
        }

        $modulos = $loader->get_loaded_modules();
        $module_roles = [];
        $context_scores = $this->get_portal_user_context_scores();
        $user_favorites = $this->get_user_tool_favorites();

        foreach ($modulos as $id => $instance) {
            $ecosystem = is_object($instance) && method_exists($instance, 'get_ecosystem_metadata')
                ? (array) $instance->get_ecosystem_metadata()
                : [];
            $module_roles[$id] = $ecosystem['module_role'] ?? 'vertical';
        }

        // Iterar sobre TODOS los módulos cargados
        foreach ($modulos as $id => $instance) {
            $action = $this->get_module_quick_action_config($id, $instance);
            $action['id'] = 'tool:' . sanitize_key($id);
            $role = $module_roles[$id] ?? 'vertical';
            $score = (int) ($action['favorite_weight'] ?? 50);

            if ($role === 'vertical') {
                $score += 12;
            } elseif ($role === 'base') {
                $score += 6;
            } elseif ($role === 'transversal') {
                $score += 8;
            }

            foreach ((array) ($action['contexts'] ?? []) as $context) {
                $score += (int) ($context_scores[$context] ?? 0);
            }

            $action['score'] = $score;
            $action['kind_label'] = $this->get_tool_kind_label($action['kind'] ?? '');
            $action['context_label'] = $this->get_tool_context_label($action['contexts'] ?? []);
            $action['is_favorite'] = in_array($action['id'], $user_favorites, true);
            $native_severity = $this->get_module_tool_native_severity($id);
            if (!empty($native_severity['slug'])) {
                $action['severity_slug'] = $native_severity['slug'];
                $action['severity_label'] = $native_severity['label'];
                $action['severity_reason'] = $native_severity['reason'];
            } else {
                $action['severity_slug'] = $this->get_tool_severity_slug($action);
                $action['severity_label'] = $this->get_tool_severity_label($action['severity_slug']);
            }
            $accesos[] = $action;
        }

        usort($accesos, static function ($left, $right) use ($user_favorites) {
            $left_index = array_search($left['id'] ?? '', $user_favorites, true);
            $right_index = array_search($right['id'] ?? '', $user_favorites, true);

            if ($left_index !== false || $right_index !== false) {
                if ($left_index === false) {
                    return 1;
                }
                if ($right_index === false) {
                    return -1;
                }
                if ($left_index !== $right_index) {
                    return $left_index <=> $right_index;
                }
            }

            return ($right['score'] ?? 0) <=> ($left['score'] ?? 0);
        });

        if (!empty($args['favorites_only'])) {
            if (!empty($user_favorites)) {
                $accesos = array_filter($accesos, static function ($action) {
                    return !empty($action['is_favorite']);
                });
            } else {
                $accesos = array_filter($accesos, static function ($action) {
                    return ($action['score'] ?? 0) >= 82;
                });
            }
        }

        return array_slice(array_values($accesos), 0, (int) $args['limit']);
    }

    /**
     * Devuelve herramientas favoritas del usuario.
     *
     * @return array
     */
    private function get_user_tool_favorites() {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return [];
        }

        $favorites = get_user_meta($user_id, 'flavor_portal_tool_favorites', true);

        if (!is_array($favorites)) {
            return [];
        }

        return array_values(array_filter(array_map('sanitize_text_field', $favorites)));
    }

    /**
     * Guarda herramientas favoritas del usuario.
     *
     * @param array $favorites
     * @return void
     */
    private function save_user_tool_favorites($favorites) {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return;
        }

        update_user_meta(
            $user_id,
            'flavor_portal_tool_favorites',
            array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) $favorites))))
        );
    }

    /**
     * AJAX: alterna una herramienta favorita del portal.
     *
     * @return void
     */
    public function ajax_toggle_portal_tool_favorite() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
        }

        check_ajax_referer('flavor_portal_tools', 'nonce');

        $tool_id = sanitize_text_field(wp_unslash($_POST['tool_id'] ?? ''));

        if ($tool_id === '') {
            wp_send_json_error(['message' => __('Herramienta no válida.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        $favorites = $this->get_user_tool_favorites();
        $index = array_search($tool_id, $favorites, true);

        if ($index !== false) {
            unset($favorites[$index]);
            $is_favorite = false;
        } else {
            array_unshift($favorites, $tool_id);
            $favorites = array_slice(array_values($favorites), 0, 6);
            $is_favorite = true;
        }

        $this->save_user_tool_favorites($favorites);

        // Obtener datos de la herramienta para actualizar el strip sin reload
        $tool_data = null;
        if ($is_favorite) {
            $tool_data = $this->get_tool_data_for_favorite($tool_id);
        }

        wp_send_json_success([
            'is_favorite' => $is_favorite,
            'tool_data' => $tool_data,
            'favorites' => array_values($favorites),
        ]);
    }

    /**
     * Calcula contextos dominantes del portal actual a partir de módulos activos.
     *
     * @return array<string,int>
     */
    private function get_portal_user_context_scores() {
        $loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;

        if (!$loader) {
            return [];
        }

        $scores = [];
        $loaded_modules = $loader->get_loaded_modules();

        foreach ($loaded_modules as $module) {
            if (!is_object($module) || !method_exists($module, 'get_dashboard_metadata')) {
                continue;
            }

            $dashboard = (array) $module->get_dashboard_metadata();
            $contexts = array_values(array_filter((array) ($dashboard['client_contexts'] ?? [])));

            foreach ($contexts as $index => $context) {
                $context = (string) $context;
                $scores[$context] = ($scores[$context] ?? 0) + max(1, 5 - $index);
            }
        }

        arsort($scores);
        return $scores;
    }

    /**
     * Traduce el tipo de herramienta a una etiqueta corta.
     *
     * @param string $kind
     * @return string
     */
    private function get_tool_kind_label($kind) {
        $labels = [
            'operar' => __('Operar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'coordinar' => __('Coordinar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'entender' => __('Entender', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $labels[$kind] ?? '';
    }

    /**
     * Obtiene una pista corta de contexto para la herramienta.
     *
     * @param array $contexts
     * @return string
     */
    private function get_tool_context_label($contexts) {
        $contexts = array_values(array_filter((array) $contexts));

        if (empty($contexts)) {
            return '';
        }

        $labels = [
            'comunidad' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'energia' => __('Energía', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'gobernanza' => __('Gobernanza', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'participacion' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transparencia' => __('Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'consumo' => __('Consumo local', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sostenibilidad' => __('Sostenibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'aprendizaje' => __('Aprendizaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'saberes' => __('Saberes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'agenda' => __('Agenda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos' => __('Encuentros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'impacto' => __('Impacto', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        $primary = (string) $contexts[0];
        return $labels[$primary] ?? ucfirst(str_replace('_', ' ', $primary));
    }

    /**
     * Calcula severidad de una herramienta del portal.
     *
     * @param array $tool
     * @return string
     */
    private function get_tool_severity_slug($tool) {
        return Flavor_Dashboard_Severity::from_tool((array) $tool);
    }

    /**
     * Traduce la severidad de una herramienta.
     *
     * @param string $severity
     * @return string
     */
    private function get_tool_severity_label($severity) {
        return Flavor_Dashboard_Severity::get_label((string) $severity);
    }

    /**
     * Resuelve una instancia de módulo sin asumir que implemente singleton.
     *
     * @param string $module_id
     * @param string $class_name
     * @return object|null
     */
    private function resolve_portal_module_instance($module_id, $class_name) {
        $module_id = sanitize_key(str_replace('-', '_', (string) $module_id));
        $class_name = (string) $class_name;

        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            if ($loader && method_exists($loader, 'get_loaded_modules')) {
                $loaded_modules = (array) $loader->get_loaded_modules();

                if (isset($loaded_modules[$module_id]) && is_object($loaded_modules[$module_id])) {
                    return $loaded_modules[$module_id];
                }

                foreach ($loaded_modules as $loaded_module_id => $loaded_module) {
                    $normalized_loaded_id = sanitize_key(str_replace('-', '_', (string) $loaded_module_id));
                    if ($normalized_loaded_id === $module_id && is_object($loaded_module)) {
                        return $loaded_module;
                    }
                }
            }
        }

        if ($class_name !== '' && class_exists($class_name) && method_exists($class_name, 'get_instance')) {
            return $class_name::get_instance();
        }

        return null;
    }

    /**
     * Intenta resolver severidad nativa desde el widget del modulo.
     *
     * @param string $module_id
     * @return array{slug:string,label:string,reason:string}|array{}
     */
    private function get_module_tool_native_severity($module_id) {
        static $cache = [];

        $module_id = sanitize_key(str_replace('-', '_', (string) $module_id));
        if ($module_id === '') {
            return [];
        }

        if (array_key_exists($module_id, $cache)) {
            return $cache[$module_id];
        }

        if (!class_exists('Flavor_Widget_Registry')) {
            $cache[$module_id] = [];
            return $cache[$module_id];
        }

        $registry = Flavor_Widget_Registry::get_instance();
        if (!$registry || !method_exists($registry, 'initialize_widgets')) {
            $cache[$module_id] = [];
            return $cache[$module_id];
        }

        $registry->initialize_widgets();

        $widget_candidates = array_values(array_unique([
            $module_id,
            str_replace('_', '-', $module_id),
        ]));

        foreach ($widget_candidates as $widget_candidate) {
            $widget = $registry->get_widget($widget_candidate);
            if (!$widget || !method_exists($widget, 'get_widget_config')) {
                continue;
            }

            $config = (array) $widget->get_widget_config();
            $severity_slug = sanitize_key((string) ($config['severity_slug'] ?? ''));
            if ($severity_slug === '') {
                continue;
            }

            $cache[$module_id] = [
                'slug' => $severity_slug,
                'label' => (string) ($config['severity_label'] ?? $this->get_tool_severity_label($severity_slug)),
                'reason' => (string) ($config['severity_reason'] ?? ''),
            ];

            return $cache[$module_id];
        }

        $cache[$module_id] = [];
        return $cache[$module_id];
    }

    /**
     * Calcula severidad de una notificación del portal.
     *
     * @param string $type
     * @return string
     */
    private function get_portal_notification_severity($type) {
        return Flavor_Dashboard_Severity::from_notification_type((string) $type);
    }

    /**
     * Intenta inferir el modulo origen de una notificacion a partir de su enlace.
     *
     * @param array $notification
     * @return string
     */
    private function infer_notification_module_id($notification) {
        $link = (string) ($notification['link'] ?? '');
        if ($link === '') {
            return '';
        }

        $parts = wp_parse_url($link);
        $path = (string) ($parts['path'] ?? '');
        $query = [];

        if (!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        if (!empty($query['page'])) {
            $page = sanitize_key(str_replace('-', '_', (string) $query['page']));
            $page_map = [
                'eventos' => 'eventos',
                'participacion' => 'participacion',
                'socios' => 'socios',
                'incidencias' => 'incidencias',
                'energia_comunitaria' => 'energia_comunitaria',
                'energia_comunitaria_dashboard' => 'energia_comunitaria',
            ];

            if (isset($page_map[$page])) {
                return $page_map[$page];
            }
        }

        if (preg_match('#/mi-portal/([^/]+)/#', $path, $matches)) {
            return sanitize_key(str_replace('-', '_', (string) $matches[1]));
        }

        return '';
    }

    /**
     * Calcula severidad de una acción del portal según cercanía temporal.
     *
     * @param string $date
     * @return string
     */
    private function get_portal_action_severity_from_date($date) {
        return Flavor_Dashboard_Severity::from_date((string) $date, 'followup');
    }

    /**
     * Renderiza feed de actividad mejorado
     */
    private function render_activity_feed() {
        $activities = $this->get_user_activities();

        if (empty($activities)) {
            return '<div class="flavor-empty-state">
                <span class="flavor-empty-state__icon">📝</span>
                <p>' . __('No hay actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        ob_start();
        ?>
        <div class="flavor-activity-feed">
            <?php foreach ($activities as $activity) : ?>
                <div class="flavor-activity-item">
                    <div class="flavor-activity-item__icon"><?php echo $activity['icon']; ?></div>
                    <div class="flavor-activity-item__content">
                        <p class="flavor-activity-item__text"><?php echo esc_html($activity['text']); ?></p>
                        <time class="flavor-activity-item__time"><?php echo esc_html($activity['time']); ?></time>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene actividades del usuario desde el Activity Log real
     */
    private function get_user_activities() {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return [];
        }

        // Verificar si existe el Activity Log
        if ( ! class_exists( 'Flavor_Activity_Log' ) ) {
            return [];
        }

        $activity_log = Flavor_Activity_Log::get_instance();
        $resultado = $activity_log->obtener_actividad( [
            'usuario_id' => $user_id,
            'por_pagina' => 5,
            'pagina'     => 1,
        ] );

        if ( empty( $resultado['registros'] ) ) {
            return [];
        }

        // Mapeo de tipos a iconos
        $type_icons = [
            'info'        => '📝',
            'exito'       => '✅',
            'advertencia' => '⚠️',
            'error'       => '❌',
        ];

        $activities = [];
        foreach ( $resultado['registros'] as $registro ) {
            $tipo = isset( $registro->tipo ) ? $registro->tipo : 'info';
            $fecha = isset( $registro->fecha ) ? $registro->fecha : '';

            // Formatear tiempo relativo
            $tiempo_relativo = '';
            if ( $fecha ) {
                $timestamp = strtotime( $fecha );
                $tiempo_relativo = human_time_diff( $timestamp, current_time( 'timestamp' ) ) . ' ' . __( 'ago', FLAVOR_PLATFORM_TEXT_DOMAIN );
            }

            $activities[] = [
                'icon' => isset( $type_icons[ $tipo ] ) ? $type_icons[ $tipo ] : '📝',
                'text' => isset( $registro->titulo ) ? $registro->titulo : '',
                'time' => $tiempo_relativo,
            ];
        }

        return $activities;
    }

    /**
     * Renderiza widget de perfil
     */
    private function render_profile_widget($user) {
        $avatar = get_avatar($user->ID, 80);

        ob_start();
        ?>
        <div class="flavor-profile-widget">
            <div class="flavor-profile-widget__avatar">
                <?php echo $avatar; ?>
            </div>
            <div class="flavor-profile-widget__info">
                <h4 class="flavor-profile-widget__name"><?php echo esc_html($user->display_name); ?></h4>
                <p class="flavor-profile-widget__email"><?php echo esc_html($user->user_email); ?></p>
                <p class="flavor-profile-widget__context"><?php _e('Desde aquí gestionas tu cuenta y tu presencia en la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <a href="<?php echo admin_url('profile.php'); ?>" class="flavor-profile-widget__link">
                <?php _e('Abrir perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza próximas acciones
     */
    private function render_upcoming_actions() {
        $user_id = get_current_user_id();
        $acciones = [];

        // Obtener eventos próximos si el módulo existe
        $eventos_module = $this->resolve_portal_module_instance('eventos', flavor_get_runtime_class_name('Flavor_Chat_Eventos_Module'));
        if ($eventos_module && method_exists($eventos_module, 'get_proximos_eventos_usuario')) {
                $eventos = $eventos_module->get_proximos_eventos_usuario($user_id, 3);
                foreach ($eventos as $evento) {
                    $severity_slug = $this->get_portal_action_severity_from_date($evento['fecha_inicio'] ?? '');
                    $acciones[] = [
                        'tipo'   => 'evento',
                        'icono'  => '📅',
                        'titulo' => $evento['titulo'] ?? $evento['nombre'] ?? __('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'fecha'  => $evento['fecha_inicio'] ?? '',
                        'url'    => $evento['url'] ?? '',
                        'severity_slug' => $severity_slug,
                        'severity_label' => $this->get_tool_severity_label($severity_slug),
                    ];
                }
        }

        // Obtener reservas próximas si el módulo existe
        $reservas_module = $this->resolve_portal_module_instance('reservas', flavor_get_runtime_class_name('Flavor_Chat_Reservas_Module'));
        if ($reservas_module && method_exists($reservas_module, 'get_proximas_reservas_usuario')) {
                $reservas = $reservas_module->get_proximas_reservas_usuario($user_id, 3);
                foreach ($reservas as $reserva) {
                    $severity_slug = $this->get_portal_action_severity_from_date($reserva['fecha'] ?? '');
                    $acciones[] = [
                        'tipo'   => 'reserva',
                        'icono'  => '🏠',
                        'titulo' => $reserva['nombre_espacio'] ?? __('Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'fecha'  => $reserva['fecha'] ?? '',
                        'url'    => $reserva['url'] ?? '',
                        'severity_slug' => $severity_slug,
                        'severity_label' => $this->get_tool_severity_label($severity_slug),
                    ];
                }
        }

        $acciones = array_merge(
            $acciones,
            // Participación ciudadana
            $this->get_portal_participation_actions($user_id),
            $this->get_portal_tramite_actions($user_id),
            // Consumo y economía
            $this->get_portal_grupos_consumo_actions($user_id),
            $this->get_portal_energia_actions($user_id),
            // Formación y cultura
            $this->get_portal_talleres_actions($user_id),
            $this->get_portal_biblioteca_actions($user_id),
            $this->get_portal_cursos_actions($user_id),
            // Membresía y ayuda
            $this->get_portal_socios_actions($user_id),
            $this->get_portal_ayuda_vecinal_actions($user_id)
        );

        // Ordenar por fecha
        usort($acciones, function($a, $b) {
            return strtotime($a['fecha'] ?? 0) - strtotime($b['fecha'] ?? 0);
        });

        ob_start();
        ?>
        <div class="flavor-upcoming-actions">
            <?php if (empty($acciones)): ?>
                <p class="flavor-no-content"><?php _e('No hay acciones inmediatas. Tu nodo está al día por ahora.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
                <?php foreach (array_slice($acciones, 0, 5) as $accion): ?>
                    <div class="flavor-action-item flavor-action-item--<?php echo esc_attr($accion['severity_slug'] ?? 'stable'); ?>" data-severity="<?php echo esc_attr($accion['severity_slug'] ?? 'stable'); ?>">
                        <span class="flavor-action-icon"><?php echo esc_html($accion['icono']); ?></span>
                        <div class="flavor-action-content">
                            <div class="flavor-action-meta">
                                <span class="flavor-action-severity flavor-action-severity--<?php echo esc_attr($accion['severity_slug'] ?? 'stable'); ?>">
                                    <?php echo esc_html($accion['severity_label'] ?? __('Estable', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </span>
                            </div>
                            <?php if (!empty($accion['url'])): ?>
                                <a href="<?php echo esc_url($accion['url']); ?>"><?php echo esc_html($accion['titulo']); ?></a>
                            <?php else: ?>
                                <span><?php echo esc_html($accion['titulo']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($accion['fecha'])): ?>
                                <span class="flavor-action-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($accion['fecha']))); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Expone "Qué hacer ahora" para otras vistas del portal sin duplicar lógica.
     */
    public function render_shared_upcoming_actions() {
        if (!is_user_logged_in()) {
            return '';
        }

        return $this->render_upcoming_actions();
    }

    private function get_portal_native_notifications($user_id) {
        return array_merge(
            // Prioridad alta: avisos, anuncios, notificaciones del sistema
            $this->get_portal_avisos_notifications($user_id),
            $this->get_portal_announcement_notifications($user_id),
            $this->get_portal_user_notifications_summary($user_id),
            // Eventos y reservas
            $this->get_portal_event_notifications($user_id),
            $this->get_portal_reserva_notifications($user_id),
            // Participación ciudadana
            $this->get_portal_participation_notifications($user_id),
            $this->get_portal_incidencia_notifications($user_id),
            // Membresía y energía
            $this->get_portal_socios_notifications($user_id),
            $this->get_portal_energia_notifications($user_id),
            // Biblioteca y movilidad
            $this->get_portal_biblioteca_notifications($user_id),
            $this->get_portal_bicicletas_notifications($user_id),
            // Comunicación y social
            $this->get_portal_foros_notifications($user_id),
            $this->get_portal_podcast_notifications($user_id),
            $this->get_portal_social_notifications($user_id),
            $this->get_portal_chat_notifications($user_id),
            // Consumo y marketplace
            $this->get_portal_grupos_consumo_notifications($user_id),
            $this->get_portal_marketplace_notifications($user_id)
        );
    }

    private function get_portal_avisos_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $candidate_tables = [
            $wpdb->prefix . 'flavor_avisos_municipales',
            $wpdb->prefix . 'flavor_avisos_avisos',
        ];
        $tabla_avisos = '';

        foreach ($candidate_tables as $candidate_table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$candidate_table'") === $candidate_table) {
                $tabla_avisos = $candidate_table;
                break;
            }
        }

        if ($tabla_avisos === '') {
            return $notifications;
        }

        $urgente_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_avisos} LIKE 'urgente'") ? 'urgente' : 'prioridad';
        $published_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_avisos} LIKE 'publicado'") ? 'publicado' : 'estado';
        $start_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_avisos} LIKE 'fecha_inicio'") ? 'fecha_inicio' : '';
        $end_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_avisos} LIKE 'fecha_fin'") ? 'fecha_fin' : '';
        $title_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_avisos} LIKE 'titulo'") ? 'titulo' : 'nombre';
        $published_date_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_avisos} LIKE 'fecha_publicacion'") ? 'fecha_publicacion' : 'created_at';

        $where = [];
        if ($published_field === 'publicado') {
            $where[] = "{$published_field} = 1";
        } else {
            $where[] = "{$published_field} = 'publicado'";
        }
        if ($start_field !== '') {
            $where[] = "({$start_field} IS NULL OR {$start_field} <= NOW())";
        }
        if ($end_field !== '') {
            $where[] = "({$end_field} IS NULL OR {$end_field} >= NOW())";
        }

        $where_sql = implode(' AND ', $where);
        $urgente_sql = $urgente_field === 'urgente'
            ? "({$urgente_field} = 1 OR {$urgente_field} = '1')"
            : "({$urgente_field} = 'urgente')";

        $urgentes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_avisos}
             WHERE {$where_sql} AND {$urgente_sql}"
        );

        if ($urgentes > 0) {
            $aviso = $wpdb->get_row(
                "SELECT id, {$title_field} AS titulo, {$published_date_field} AS fecha_publicacion
                 FROM {$tabla_avisos}
                 WHERE {$where_sql} AND {$urgente_sql}
                 ORDER BY {$published_date_field} DESC
                 LIMIT 1",
                ARRAY_A
            );

            $reason = sprintf(
                _n('%d aviso urgente activo.', '%d avisos urgentes activos.', $urgentes, FLAVOR_PLATFORM_TEXT_DOMAIN),
                $urgentes
            );
            if (!empty($aviso['titulo'])) {
                $reason .= ' ' . sprintf(__('Último: %s.', FLAVOR_PLATFORM_TEXT_DOMAIN), $aviso['titulo']);
            }

            $notifications[] = [
                'module_id' => 'avisos_municipales',
                'type' => 'warning',
                'icon' => '📢',
                'text' => sprintf(
                    _n('%d aviso urgente', '%d avisos urgentes', $urgentes, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $urgentes
                ),
                'link' => Flavor_Platform_Helpers::get_action_url('avisos_municipales', '') . '?urgente=1',
                'severity_slug' => 'attention',
                'severity_label' => $this->get_tool_severity_label('attention'),
                'severity_reason' => $reason,
            ];

            return $notifications;
        }

        $activos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_avisos}
             WHERE {$where_sql}"
        );

        if ($activos > 0) {
            $notifications[] = [
                'module_id' => 'avisos_municipales',
                'type' => 'info',
                'icon' => '📢',
                'text' => sprintf(
                    _n('%d aviso activo', '%d avisos activos', $activos, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $activos
                ),
                'link' => Flavor_Platform_Helpers::get_action_url('avisos_municipales', ''),
                'severity_slug' => 'followup',
                'severity_label' => $this->get_tool_severity_label('followup'),
                'severity_reason' => __('Hay avisos activos que conviene revisar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return $notifications;
    }

    private function get_portal_announcement_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_tablon = $wpdb->prefix . 'board';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_tablon'") !== $tabla_tablon) {
            return $notifications;
        }

        $prioridad_alta = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_tablon}
             WHERE activo = 1
             AND (fecha_fin IS NULL OR fecha_fin > NOW())
             AND tipo = 'anuncio'
             AND prioridad IN ('alta', 'urgente')"
        );

        if ($prioridad_alta > 0) {
            $ultimo = $wpdb->get_row(
                "SELECT id, titulo, fecha_publicacion
                 FROM {$tabla_tablon}
                 WHERE activo = 1
                 AND (fecha_fin IS NULL OR fecha_fin > NOW())
                 AND tipo = 'anuncio'
                 AND prioridad IN ('alta', 'urgente')
                 ORDER BY fecha_publicacion DESC
                 LIMIT 1",
                ARRAY_A
            );

            $notifications[] = [
                'module_id' => 'anuncios',
                'type' => 'warning',
                'icon' => '📌',
                'text' => sprintf(
                    _n('%d anuncio prioritario', '%d anuncios prioritarios', $prioridad_alta, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $prioridad_alta
                ),
                'link' => Flavor_Platform_Helpers::get_action_url('comunidades', 'anuncios'),
                'severity_slug' => 'attention',
                'severity_label' => $this->get_tool_severity_label('attention'),
                'severity_reason' => !empty($ultimo['titulo'])
                    ? sprintf(__('Hay anuncios destacados en el tablón. Último: %s.', FLAVOR_PLATFORM_TEXT_DOMAIN), $ultimo['titulo'])
                    : __('Hay anuncios destacados en el tablón de red.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];

            return $notifications;
        }

        $anuncios_activos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_tablon}
             WHERE activo = 1
             AND (fecha_fin IS NULL OR fecha_fin > NOW())
             AND tipo = 'anuncio'"
        );

        if ($anuncios_activos > 0) {
            $notifications[] = [
                'module_id' => 'anuncios',
                'type' => 'info',
                'icon' => '📌',
                'text' => sprintf(
                    _n('%d anuncio activo en el tablón', '%d anuncios activos en el tablón', $anuncios_activos, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $anuncios_activos
                ),
                'link' => Flavor_Platform_Helpers::get_action_url('comunidades', 'anuncios'),
                'severity_slug' => 'followup',
                'severity_label' => $this->get_tool_severity_label('followup'),
                'severity_reason' => __('Hay publicaciones activas en el tablón de red.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return $notifications;
    }

    private function get_portal_user_notifications_summary($user_id) {
        global $wpdb;

        $notifications = [];
        $candidate_tables = [
            $wpdb->prefix . 'flavor_notifications',
            $wpdb->prefix . 'flavor_notificaciones',
        ];
        $tabla_notificaciones = '';

        foreach ($candidate_tables as $candidate_table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$candidate_table'") === $candidate_table) {
                $tabla_notificaciones = $candidate_table;
                break;
            }
        }

        if ($tabla_notificaciones === '') {
            return $notifications;
        }

        $user_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_notificaciones} LIKE 'user_id'") ? 'user_id' : 'usuario_id';
        $read_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_notificaciones} LIKE 'is_read'") ? 'is_read' : 'leida';
        $title_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_notificaciones} LIKE 'title'") ? 'title' : 'titulo';
        $created_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_notificaciones} LIKE 'created_at'") ? 'created_at' : 'fecha_creacion';
        $link_field = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_notificaciones} LIKE 'link'") ? 'link' : '';

        $unread_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_notificaciones}
             WHERE {$user_field} = %d AND {$read_field} = 0",
            $user_id
        ));

        if ($unread_count <= 0) {
            return $notifications;
        }

        $latest = $wpdb->get_row($wpdb->prepare(
            "SELECT {$title_field} AS titulo, {$created_field} AS fecha_creacion" . ($link_field !== '' ? ", {$link_field} AS enlace" : "") . "
             FROM {$tabla_notificaciones}
             WHERE {$user_field} = %d AND {$read_field} = 0
             ORDER BY {$created_field} DESC
             LIMIT 1",
            $user_id
        ), ARRAY_A);

        $severity_slug = $unread_count >= 5 ? 'attention' : 'followup';
        $notifications[] = [
            'module_id' => 'notificaciones',
            'type' => $severity_slug === 'attention' ? 'warning' : 'info',
            'icon' => '🔔',
            'text' => sprintf(
                _n('%d notificación pendiente', '%d notificaciones pendientes', $unread_count, FLAVOR_PLATFORM_TEXT_DOMAIN),
                $unread_count
            ),
            'link' => !empty($latest['enlace']) ? $latest['enlace'] : Flavor_Platform_Helpers::get_action_url('notificaciones', ''),
            'severity_slug' => $severity_slug,
            'severity_label' => $this->get_tool_severity_label($severity_slug),
            'severity_reason' => !empty($latest['titulo'])
                ? sprintf(__('Última pendiente: %s.', FLAVOR_PLATFORM_TEXT_DOMAIN), $latest['titulo'])
                : __('Tienes notificaciones pendientes de revisar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $notifications;
    }

    private function get_portal_event_notifications($user_id) {
        $eventos_module = $this->resolve_portal_module_instance('eventos', flavor_get_runtime_class_name('Flavor_Chat_Eventos_Module'));
        if (!$eventos_module || !method_exists($eventos_module, 'get_proximos_eventos_usuario')) {
            return [];
        }

        $eventos = (array) $eventos_module->get_proximos_eventos_usuario($user_id, 1);
        $evento = $eventos[0] ?? null;

        if (empty($evento)) {
            return [];
        }

        $fecha = (string) ($evento['fecha_inicio'] ?? '');
        $severity_slug = $this->get_portal_action_severity_from_date($fecha);
        if ($severity_slug === 'stable') {
            return [];
        }

        return [[
            'module_id' => 'eventos',
            'type' => $severity_slug === 'attention' ? 'warning' : 'info',
            'icon' => '📅',
            'text' => __('Tienes un evento cercano', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'link' => $evento['url'] ?? Flavor_Platform_Helpers::get_action_url('eventos', ''),
            'severity_slug' => $severity_slug,
            'severity_label' => $this->get_tool_severity_label($severity_slug),
            'severity_reason' => sprintf(
                __('%s empieza el %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $evento['titulo'] ?? $evento['nombre'] ?? __('Tu próximo evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                date_i18n(get_option('date_format') . ' · ' . get_option('time_format'), strtotime($fecha))
            ),
        ]];
    }

    private function get_portal_reserva_notifications($user_id) {
        $reservas_module = $this->resolve_portal_module_instance('reservas', flavor_get_runtime_class_name('Flavor_Chat_Reservas_Module'));
        if (!$reservas_module || !method_exists($reservas_module, 'get_proximas_reservas_usuario')) {
            return [];
        }

        $reservas = (array) $reservas_module->get_proximas_reservas_usuario($user_id, 1);
        $reserva = $reservas[0] ?? null;

        if (empty($reserva)) {
            return [];
        }

        $fecha = (string) ($reserva['fecha'] ?? '');
        $severity_slug = $this->get_portal_action_severity_from_date($fecha);
        if ($severity_slug === 'stable') {
            return [];
        }

        return [[
            'module_id' => 'reservas',
            'type' => $severity_slug === 'attention' ? 'warning' : 'info',
            'icon' => '🏠',
            'text' => __('Tienes una reserva próxima', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'link' => $reserva['url'] ?? Flavor_Platform_Helpers::get_action_url('reservas', ''),
            'severity_slug' => $severity_slug,
            'severity_label' => $this->get_tool_severity_label($severity_slug),
            'severity_reason' => sprintf(
                __('%s está prevista para el %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $reserva['nombre_espacio'] ?? __('Tu próxima reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                date_i18n(get_option('date_format') . ' · ' . get_option('time_format'), strtotime($fecha))
            ),
        ]];
    }

    private function get_portal_participation_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_votaciones = $wpdb->prefix . 'votaciones';
        $tabla_propuestas = $wpdb->prefix . 'propuestas';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_votaciones'") === $tabla_votaciones) {
            $votacion = $wpdb->get_row(
                "SELECT id, titulo, fecha_fin
                 FROM $tabla_votaciones
                 WHERE estado = 'activa' AND fecha_fin >= NOW()
                 ORDER BY fecha_fin ASC
                 LIMIT 1",
                ARRAY_A
            );

            if (!empty($votacion)) {
                $fecha_fin = (string) ($votacion['fecha_fin'] ?? '');
                $severity_slug = $this->get_portal_action_severity_from_date($fecha_fin);

                $notifications[] = [
                    'module_id' => 'participacion',
                    'type' => $severity_slug === 'attention' ? 'warning' : 'info',
                    'icon' => '🗳️',
                    'text' => __('Hay decisiones activas en marcha', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'link' => Flavor_Platform_Helpers::get_action_url('participacion', 'votaciones'),
                    'severity_slug' => $severity_slug,
                    'severity_label' => $this->get_tool_severity_label($severity_slug),
                    'severity_reason' => sprintf(
                        __('%s cierra el %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $votacion['titulo'] ?? __('La votación activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        date_i18n(get_option('date_format') . ' · ' . get_option('time_format'), strtotime($fecha_fin))
                    ),
                ];

                return $notifications;
            }
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_propuestas'") === $tabla_propuestas) {
            $propuestas_abiertas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_propuestas
                 WHERE estado IN ('abierta', 'publicada', 'en_debate', 'votacion')"
            );

            if ($propuestas_abiertas > 0) {
                $notifications[] = [
                    'module_id' => 'participacion',
                    'type' => 'info',
                    'icon' => '💬',
                    'text' => sprintf(
                        _n('%d propuesta abierta', '%d propuestas abiertas', $propuestas_abiertas, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $propuestas_abiertas
                    ),
                    'link' => Flavor_Platform_Helpers::get_action_url('participacion', 'propuestas'),
                    'severity_slug' => 'followup',
                    'severity_label' => $this->get_tool_severity_label('followup'),
                    'severity_reason' => __('Hay actividad participativa que conviene revisar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        return $notifications;
    }

    private function get_portal_incidencia_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_seguimiento = $wpdb->prefix . 'flavor_seguimiento';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_incidencias'") !== $tabla_incidencias) {
            return $notifications;
        }

        $mis_abiertas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_incidencias}
             WHERE usuario_id = %d
             AND estado NOT IN ('resuelta', 'cerrada', 'rechazada')",
            $user_id
        ));

        $actualizaciones_nuevas = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_seguimiento'") === $tabla_seguimiento) {
            $actualizaciones_nuevas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_seguimiento} s
                 INNER JOIN {$tabla_incidencias} i ON s.incidencia_id = i.id
                 WHERE i.usuario_id = %d
                 AND s.fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)
                 AND s.autor_id != %d",
                $user_id,
                $user_id
            ));
        }

        if ($mis_abiertas > 0 || $actualizaciones_nuevas > 0) {
            $reason_parts = [];
            if ($mis_abiertas > 0) {
                $reason_parts[] = sprintf(
                    _n('%d incidencia abierta', '%d incidencias abiertas', $mis_abiertas, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $mis_abiertas
                );
            }
            if ($actualizaciones_nuevas > 0) {
                $reason_parts[] = sprintf(
                    _n('%d actualización reciente', '%d actualizaciones recientes', $actualizaciones_nuevas, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $actualizaciones_nuevas
                );
            }

            $notifications[] = [
                'module_id' => 'incidencias',
                'type' => 'warning',
                'icon' => '⚠️',
                'text' => __('Hay incidencias que requieren atención', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'link' => Flavor_Platform_Helpers::get_action_url('incidencias', 'mis-incidencias'),
                'severity_slug' => 'attention',
                'severity_label' => $this->get_tool_severity_label('attention'),
                'severity_reason' => implode(' · ', $reason_parts),
            ];

            return $notifications;
        }

        $total_abiertas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_incidencias}
             WHERE estado NOT IN ('resuelta', 'cerrada', 'rechazada')"
        );

        if ($total_abiertas > 0) {
            $notifications[] = [
                'module_id' => 'incidencias',
                'type' => 'info',
                'icon' => '📍',
                'text' => sprintf(
                    _n('%d incidencia en la comunidad', '%d incidencias en la comunidad', $total_abiertas, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $total_abiertas
                ),
                'link' => Flavor_Platform_Helpers::get_action_url('incidencias', ''),
                'severity_slug' => 'followup',
                'severity_label' => $this->get_tool_severity_label('followup'),
                'severity_reason' => __('Hay actividad comunitaria de seguimiento en incidencias.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return $notifications;
    }

    private function get_portal_socios_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_socios = $wpdb->prefix . 'flavor_socios_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_socios'") !== $tabla_socios) {
            return $notifications;
        }

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT id, estado
             FROM {$tabla_socios}
             WHERE usuario_id = %d
             LIMIT 1",
            $user_id
        ), ARRAY_A);

        if (empty($socio)) {
            return $notifications;
        }

        $estado = sanitize_key((string) ($socio['estado'] ?? ''));
        $cuotas_pendientes = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_cuotas'") === $tabla_cuotas) {
            $cuotas_pendientes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_cuotas}
                 WHERE socio_id = %d AND estado IN ('pendiente', 'vencida')",
                (int) $socio['id']
            ));
        }

        if ($estado === 'suspendido' || $cuotas_pendientes > 0) {
            $notifications[] = [
                'module_id' => 'socios',
                'type' => 'warning',
                'icon' => '🪪',
                'text' => __('Tu vínculo de socio requiere atención', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'link' => Flavor_Platform_Helpers::get_action_url('socios', 'cuotas'),
                'severity_slug' => 'attention',
                'severity_label' => $this->get_tool_severity_label('attention'),
                'severity_reason' => $cuotas_pendientes > 0
                    ? sprintf(_n('%d cuota pendiente.', '%d cuotas pendientes.', $cuotas_pendientes, FLAVOR_PLATFORM_TEXT_DOMAIN), $cuotas_pendientes)
                    : __('Tu membresía está suspendida.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];

            return $notifications;
        }

        if ($estado === 'pendiente') {
            $notifications[] = [
                'module_id' => 'socios',
                'type' => 'info',
                'icon' => '🪪',
                'text' => __('Tu membresía sigue pendiente de revisión', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'link' => Flavor_Platform_Helpers::get_action_url('socios', 'mi-perfil'),
                'severity_slug' => 'followup',
                'severity_label' => $this->get_tool_severity_label('followup'),
                'severity_reason' => __('Conviene revisar tu estado y completar lo que falte.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return $notifications;
    }

    private function get_portal_energia_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_incidencias = $wpdb->prefix . 'flavor_energia_incidencias';
        $tabla_liquidaciones = $wpdb->prefix . 'flavor_energia_liquidaciones';

        $incidencias_abiertas = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_incidencias'") === $tabla_incidencias) {
            $incidencias_abiertas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_incidencias}
                 WHERE estado IN ('abierta', 'en_progreso')"
            );
        }

        if ($incidencias_abiertas > 0) {
            $notifications[] = [
                'module_id' => 'energia_comunitaria',
                'type' => 'warning',
                'icon' => '⚡',
                'text' => sprintf(
                    _n('%d incidencia energética abierta', '%d incidencias energéticas abiertas', $incidencias_abiertas, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $incidencias_abiertas
                ),
                'link' => Flavor_Platform_Helpers::get_action_url('energia_comunitaria', 'mantenimiento'),
                'severity_slug' => 'attention',
                'severity_label' => $this->get_tool_severity_label('attention'),
                'severity_reason' => __('Hay incidencias energéticas abiertas que requieren seguimiento operativo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];

            return $notifications;
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_liquidaciones'") === $tabla_liquidaciones) {
            $liquidaciones_pendientes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_liquidaciones}
                 WHERE estado IN ('generada', 'notificada')"
            );

            if ($liquidaciones_pendientes > 0) {
                $notifications[] = [
                    'module_id' => 'energia_comunitaria',
                    'type' => 'info',
                    'icon' => '💡',
                    'text' => sprintf(
                        _n('%d liquidación pendiente', '%d liquidaciones pendientes', $liquidaciones_pendientes, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $liquidaciones_pendientes
                    ),
                    'link' => Flavor_Platform_Helpers::get_action_url('energia_comunitaria', 'liquidaciones'),
                    'severity_slug' => 'followup',
                    'severity_label' => $this->get_tool_severity_label('followup'),
                    'severity_reason' => __('Hay liquidaciones energéticas pendientes de revisión o aceptación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        return $notifications;
    }

    /**
     * Notificaciones de Biblioteca: préstamos activos, vencidos, nuevas adquisiciones
     */
    private function get_portal_biblioteca_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_prestamos'") !== $tabla_prestamos) {
            return $notifications;
        }

        // Préstamos vencidos o por vencer
        $prestamos_vencidos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_prestamos}
             WHERE usuario_id = %d AND estado = 'activo'
             AND fecha_devolucion < NOW()",
            $user_id
        ));

        if ($prestamos_vencidos > 0) {
            $notifications[] = [
                'module_id' => 'biblioteca',
                'type' => 'warning',
                'icon' => '📚',
                'text' => sprintf(
                    _n('%d préstamo vencido', '%d préstamos vencidos', $prestamos_vencidos, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $prestamos_vencidos
                ),
                'link' => Flavor_Platform_Helpers::get_action_url('biblioteca', 'mis-prestamos'),
                'severity_slug' => 'attention',
                'severity_label' => $this->get_tool_severity_label('attention'),
                'severity_reason' => __('Tienes préstamos que deberías devolver.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
            return $notifications;
        }

        // Préstamos por vencer en 3 días
        $prestamos_por_vencer = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_prestamos}
             WHERE usuario_id = %d AND estado = 'activo'
             AND fecha_devolucion BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)",
            $user_id
        ));

        if ($prestamos_por_vencer > 0) {
            $notifications[] = [
                'module_id' => 'biblioteca',
                'type' => 'info',
                'icon' => '📖',
                'text' => sprintf(
                    _n('%d préstamo por vencer', '%d préstamos por vencer', $prestamos_por_vencer, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $prestamos_por_vencer
                ),
                'link' => Flavor_Platform_Helpers::get_action_url('biblioteca', 'mis-prestamos'),
                'severity_slug' => 'followup',
                'severity_label' => $this->get_tool_severity_label('followup'),
                'severity_reason' => __('Conviene revisar tus préstamos activos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Nuevas adquisiciones esta semana
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_libros'") === $tabla_libros) {
            $nuevos_libros = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_libros}
                 WHERE estado = 'disponible'
                 AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );

            if ($nuevos_libros > 0 && empty($notifications)) {
                $notifications[] = [
                    'module_id' => 'biblioteca',
                    'type' => 'info',
                    'icon' => '📗',
                    'text' => sprintf(
                        _n('%d libro nuevo disponible', '%d libros nuevos disponibles', $nuevos_libros, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $nuevos_libros
                    ),
                    'link' => Flavor_Platform_Helpers::get_action_url('biblioteca', 'catalogo'),
                    'severity_slug' => 'stable',
                    'severity_label' => $this->get_tool_severity_label('stable'),
                    'severity_reason' => __('Hay novedades en la biblioteca.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        return $notifications;
    }

    /**
     * Notificaciones de Bicicletas: reservas activas, disponibilidad
     */
    private function get_portal_bicicletas_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_reservas = $wpdb->prefix . 'flavor_bicicletas_reservas';
        $tabla_bicis = $wpdb->prefix . 'flavor_bicicletas';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") !== $tabla_reservas) {
            return $notifications;
        }

        // Reserva activa del usuario
        $reserva_activa = $wpdb->get_row($wpdb->prepare(
            "SELECT id, fecha_inicio, fecha_fin
             FROM {$tabla_reservas}
             WHERE usuario_id = %d AND estado = 'activa'
             ORDER BY fecha_inicio ASC
             LIMIT 1",
            $user_id
        ), ARRAY_A);

        if (!empty($reserva_activa)) {
            $fecha_fin = strtotime($reserva_activa['fecha_fin'] ?? '');
            $ahora = time();
            $horas_restantes = max(0, floor(($fecha_fin - $ahora) / 3600));

            $notifications[] = [
                'module_id' => 'bicicletas_compartidas',
                'type' => $horas_restantes <= 2 ? 'warning' : 'info',
                'icon' => '🚲',
                'text' => $horas_restantes <= 2
                    ? sprintf(__('Bici a devolver en %d hora(s)', FLAVOR_PLATFORM_TEXT_DOMAIN), $horas_restantes)
                    : __('Tienes una bicicleta reservada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'link' => Flavor_Platform_Helpers::get_action_url('bicicletas_compartidas', 'mis-reservas'),
                'severity_slug' => $horas_restantes <= 2 ? 'attention' : 'followup',
                'severity_label' => $this->get_tool_severity_label($horas_restantes <= 2 ? 'attention' : 'followup'),
                'severity_reason' => sprintf(__('Devolver antes de las %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    date_i18n(get_option('time_format'), $fecha_fin)),
            ];
            return $notifications;
        }

        // Bicis disponibles
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_bicis'") === $tabla_bicis) {
            $bicis_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_bicis} WHERE estado = 'disponible'"
            );

            if ($bicis_disponibles > 0) {
                $notifications[] = [
                    'module_id' => 'bicicletas_compartidas',
                    'type' => 'info',
                    'icon' => '🚴',
                    'text' => sprintf(
                        _n('%d bicicleta disponible', '%d bicicletas disponibles', $bicis_disponibles, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $bicis_disponibles
                    ),
                    'link' => Flavor_Platform_Helpers::get_action_url('bicicletas_compartidas', ''),
                    'severity_slug' => 'stable',
                    'severity_label' => $this->get_tool_severity_label('stable'),
                    'severity_reason' => __('Puedes reservar una bici cuando quieras.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        return $notifications;
    }

    /**
     * Notificaciones de Foros: temas nuevos, respuestas pendientes
     */
    private function get_portal_foros_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_hilos'") !== $tabla_hilos) {
            return $notifications;
        }

        // Respuestas nuevas en hilos del usuario
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_respuestas'") === $tabla_respuestas) {
            $respuestas_nuevas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_respuestas} r
                 INNER JOIN {$tabla_hilos} h ON r.hilo_id = h.id
                 WHERE h.autor_id = %d
                 AND r.autor_id != %d
                 AND r.fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                $user_id,
                $user_id
            ));

            if ($respuestas_nuevas > 0) {
                $notifications[] = [
                    'module_id' => 'foros',
                    'type' => 'info',
                    'icon' => '💬',
                    'text' => sprintf(
                        _n('%d respuesta nueva en tus temas', '%d respuestas nuevas en tus temas', $respuestas_nuevas, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $respuestas_nuevas
                    ),
                    'link' => Flavor_Platform_Helpers::get_action_url('foros', 'mis-temas'),
                    'severity_slug' => 'followup',
                    'severity_label' => $this->get_tool_severity_label('followup'),
                    'severity_reason' => __('Han respondido a tus publicaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
                return $notifications;
            }
        }

        // Hilos nuevos esta semana
        $hilos_nuevos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_hilos}
             WHERE estado = 'abierto'
             AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        if ($hilos_nuevos > 3) {
            $notifications[] = [
                'module_id' => 'foros',
                'type' => 'info',
                'icon' => '📢',
                'text' => sprintf(__('%d hilos nuevos esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN), $hilos_nuevos),
                'link' => Flavor_Platform_Helpers::get_action_url('foros', ''),
                'severity_slug' => 'stable',
                'severity_label' => $this->get_tool_severity_label('stable'),
                'severity_reason' => __('Hay debate activo en los foros.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return $notifications;
    }

    /**
     * Notificaciones de Podcast/Radio: episodios nuevos, programación
     */
    private function get_portal_podcast_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        // Episodios nuevos de podcast
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_episodios'") === $tabla_episodios) {
            $episodios_nuevos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_episodios}
                 WHERE estado = 'publicado'
                 AND fecha_publicacion > DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );

            if ($episodios_nuevos > 0) {
                $ultimo = $wpdb->get_row(
                    "SELECT titulo FROM {$tabla_episodios}
                     WHERE estado = 'publicado'
                     ORDER BY fecha_publicacion DESC
                     LIMIT 1",
                    ARRAY_A
                );

                $notifications[] = [
                    'module_id' => 'podcast',
                    'type' => 'info',
                    'icon' => '🎙️',
                    'text' => sprintf(
                        _n('%d episodio nuevo', '%d episodios nuevos', $episodios_nuevos, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $episodios_nuevos
                    ),
                    'link' => Flavor_Platform_Helpers::get_action_url('podcast', ''),
                    'severity_slug' => 'stable',
                    'severity_label' => $this->get_tool_severity_label('stable'),
                    'severity_reason' => !empty($ultimo['titulo'])
                        ? sprintf(__('Último: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $ultimo['titulo'])
                        : __('Hay contenido nuevo para escuchar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        // Programas de radio en directo
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_programas'") === $tabla_programas) {
            // Obtener día actual (1=Domingo en MySQL DAYOFWEEK, pero el módulo usa 1=Lunes)
            $dia_actual = (int) date('N'); // 1=Lunes, 7=Domingo
            $hora_actual = date('H:i:s');

            $programa_hoy = $wpdb->get_row($wpdb->prepare(
                "SELECT nombre, hora_inicio, duracion_minutos FROM {$tabla_programas}
                 WHERE estado = 'activo'
                 AND JSON_CONTAINS(dias_semana, %s)
                 AND hora_inicio <= %s
                 AND ADDTIME(hora_inicio, SEC_TO_TIME(duracion_minutos * 60)) >= %s
                 LIMIT 1",
                json_encode($dia_actual),
                $hora_actual,
                $hora_actual
            ), ARRAY_A);

            if (!empty($programa_hoy)) {
                $notifications[] = [
                    'module_id' => 'radio',
                    'type' => 'info',
                    'icon' => '📻',
                    'text' => sprintf(__('En directo: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $programa_hoy['nombre']),
                    'link' => Flavor_Platform_Helpers::get_action_url('radio', ''),
                    'severity_slug' => 'followup',
                    'severity_label' => $this->get_tool_severity_label('followup'),
                    'severity_reason' => __('Hay programación en vivo ahora.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        return $notifications;
    }

    /**
     * Notificaciones de Red Social: menciones, publicaciones de seguidos
     */
    private function get_portal_social_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_posts = $wpdb->prefix . 'flavor_social_posts';
        $tabla_follows = $wpdb->prefix . 'flavor_social_follows';
        $tabla_menciones = $wpdb->prefix . 'flavor_social_menciones';

        // Menciones nuevas
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_menciones'") === $tabla_menciones) {
            $menciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_menciones}
                 WHERE usuario_mencionado_id = %d
                 AND notificado = 0
                 AND fecha > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                $user_id
            ));

            if ($menciones > 0) {
                $notifications[] = [
                    'module_id' => 'red_social',
                    'type' => 'info',
                    'icon' => '@',
                    'text' => sprintf(
                        _n('%d mención nueva', '%d menciones nuevas', $menciones, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $menciones
                    ),
                    'link' => Flavor_Platform_Helpers::get_action_url('red_social', 'menciones'),
                    'severity_slug' => 'followup',
                    'severity_label' => $this->get_tool_severity_label('followup'),
                    'severity_reason' => __('Te han mencionado en publicaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
                return $notifications;
            }
        }

        // Publicaciones de personas seguidas
        if (
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_posts'") === $tabla_posts &&
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_follows'") === $tabla_follows
        ) {
            $posts_seguidos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_posts} p
                 INNER JOIN {$tabla_follows} f ON p.autor_id = f.seguido_id
                 WHERE f.seguidor_id = %d
                 AND p.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $user_id
            ));

            if ($posts_seguidos > 0) {
                $notifications[] = [
                    'module_id' => 'red_social',
                    'type' => 'info',
                    'icon' => '📰',
                    'text' => sprintf(
                        _n('%d publicación nueva de tus seguidos', '%d publicaciones nuevas de tus seguidos', $posts_seguidos, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $posts_seguidos
                    ),
                    'link' => Flavor_Platform_Helpers::get_action_url('red_social', ''),
                    'severity_slug' => 'stable',
                    'severity_label' => $this->get_tool_severity_label('stable'),
                    'severity_reason' => __('Hay actividad reciente en tu red.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        return $notifications;
    }

    /**
     * Notificaciones de Chat Grupos: mensajes sin leer
     */
    private function get_portal_chat_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_miembros';

        if (
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_mensajes'") !== $tabla_mensajes ||
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_miembros'") !== $tabla_miembros
        ) {
            return $notifications;
        }

        $mensajes_sin_leer = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_mensajes} m
             INNER JOIN {$tabla_miembros} mb ON m.grupo_id = mb.grupo_id
             WHERE mb.usuario_id = %d
             AND m.autor_id != %d
             AND m.created_at > mb.ultima_lectura",
            $user_id,
            $user_id
        ));

        if ($mensajes_sin_leer > 0) {
            $notifications[] = [
                'module_id' => 'chat_grupos',
                'type' => $mensajes_sin_leer >= 10 ? 'warning' : 'info',
                'icon' => '💬',
                'text' => sprintf(
                    _n('%d mensaje sin leer', '%d mensajes sin leer', $mensajes_sin_leer, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $mensajes_sin_leer
                ),
                'link' => Flavor_Platform_Helpers::get_action_url('chat_grupos', ''),
                'severity_slug' => $mensajes_sin_leer >= 10 ? 'attention' : 'followup',
                'severity_label' => $this->get_tool_severity_label($mensajes_sin_leer >= 10 ? 'attention' : 'followup'),
                'severity_reason' => __('Tienes conversaciones pendientes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return $notifications;
    }

    /**
     * Notificaciones de Grupos de Consumo: ciclo abierto, pedidos
     */
    private function get_portal_grupos_consumo_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_consumidores'") !== $tabla_consumidores) {
            return $notifications;
        }

        $consumidor_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_consumidores} WHERE usuario_id = %d LIMIT 1",
            $user_id
        ));

        if ($consumidor_id <= 0) {
            return $notifications;
        }

        // Ciclo abierto
        $ciclos = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);

        if (empty($ciclos)) {
            return $notifications;
        }

        $ciclo_id = (int) $ciclos[0];
        $fecha_cierre = (string) get_post_meta($ciclo_id, '_gc_fecha_cierre', true);
        $titulo_ciclo = get_the_title($ciclo_id) ?: __('Ciclo activo', FLAVOR_PLATFORM_TEXT_DOMAIN);

        // Verificar si ya tiene pedido
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_pedidos'") === $tabla_pedidos) {
            $tiene_pedido = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE usuario_id = %d AND ciclo_id = %d",
                $user_id,
                $ciclo_id
            )) > 0;

            if (!$tiene_pedido && $fecha_cierre) {
                $dias_restantes = max(0, floor((strtotime($fecha_cierre) - time()) / 86400));
                $severity_slug = $dias_restantes <= 2 ? 'attention' : 'followup';

                $notifications[] = [
                    'module_id' => 'grupos_consumo',
                    'type' => $dias_restantes <= 2 ? 'warning' : 'info',
                    'icon' => '🧺',
                    'text' => $dias_restantes <= 2
                        ? sprintf(__('Ciclo cierra en %d día(s)', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_restantes)
                        : sprintf(__('%s abierto para pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN), $titulo_ciclo),
                    'link' => Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'hacer-pedido'),
                    'severity_slug' => $severity_slug,
                    'severity_label' => $this->get_tool_severity_label($severity_slug),
                    'severity_reason' => sprintf(__('Cierra el %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        date_i18n(get_option('date_format'), strtotime($fecha_cierre))),
                ];
            }
        }

        return $notifications;
    }

    /**
     * Notificaciones de Marketplace: mensajes de anuncios, anuncios cercanos
     */
    private function get_portal_marketplace_notifications($user_id) {
        global $wpdb;

        $notifications = [];
        $tabla_mensajes = $wpdb->prefix . 'flavor_marketplace_mensajes';
        $tabla_anuncios = $wpdb->prefix . 'flavor_marketplace_anuncios';

        // Mensajes sin leer en mis anuncios
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_mensajes'") === $tabla_mensajes) {
            $mensajes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_mensajes} m
                 INNER JOIN {$tabla_anuncios} a ON m.anuncio_id = a.id
                 WHERE a.usuario_id = %d
                 AND m.remitente_id != %d
                 AND m.leido = 0",
                $user_id,
                $user_id
            ));

            if ($mensajes > 0) {
                $notifications[] = [
                    'module_id' => 'marketplace',
                    'type' => 'info',
                    'icon' => '🛒',
                    'text' => sprintf(
                        _n('%d mensaje en tus anuncios', '%d mensajes en tus anuncios', $mensajes, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $mensajes
                    ),
                    'link' => Flavor_Platform_Helpers::get_action_url('marketplace', 'mis-anuncios'),
                    'severity_slug' => 'followup',
                    'severity_label' => $this->get_tool_severity_label('followup'),
                    'severity_reason' => __('Personas interesadas en lo que ofreces.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        return $notifications;
    }

    private function get_portal_participation_actions($user_id) {
        global $wpdb;

        $acciones = [];
        $tabla_votaciones = $wpdb->prefix . 'votaciones';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_votaciones'") !== $tabla_votaciones) {
            return $acciones;
        }

        $votaciones = (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, titulo, fecha_fin
                 FROM $tabla_votaciones
                 WHERE estado = 'activa' AND fecha_fin >= %s
                 ORDER BY fecha_fin ASC
                 LIMIT 2",
                current_time('mysql')
            ),
            ARRAY_A
        );

        foreach ($votaciones as $votacion) {
            $fecha = (string) ($votacion['fecha_fin'] ?? '');
            $severity_slug = $this->get_portal_action_severity_from_date($fecha);
            $acciones[] = [
                'tipo'   => 'participacion',
                'icono'  => '🗳️',
                'titulo' => $votacion['titulo'] ?? __('Decisión activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha'  => $fecha,
                'url'    => Flavor_Platform_Helpers::get_action_url('participacion', 'votaciones'),
                'severity_slug' => $severity_slug,
                'severity_label' => $this->get_tool_severity_label($severity_slug),
            ];
        }

        return $acciones;
    }

    private function get_portal_tramite_actions($user_id) {
        global $wpdb;

        $acciones = [];
        $tabla_tramites = $wpdb->prefix . 'tramites';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_tramites'") !== $tabla_tramites) {
            return $acciones;
        }

        $tramites = (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, titulo, tipo, estado, created_at
                 FROM $tabla_tramites
                 WHERE usuario_id = %d
                 AND estado IN ('pendiente', 'en_revision', 'en_proceso')
                 ORDER BY created_at DESC
                 LIMIT 2",
                $user_id
            ),
            ARRAY_A
        );

        foreach ($tramites as $tramite) {
            $severity_slug = sanitize_key((string) ($tramite['estado'] ?? 'pendiente')) === 'pendiente' ? 'attention' : 'followup';
            $acciones[] = [
                'tipo'   => 'tramite',
                'icono'  => '📋',
                'titulo' => $tramite['titulo'] ?? __('Trámite pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha'  => $tramite['created_at'] ?? '',
                'url'    => Flavor_Platform_Helpers::get_action_url('tramites', 'mis-tramites'),
                'severity_slug' => $severity_slug,
                'severity_label' => $this->get_tool_severity_label($severity_slug),
            ];
        }

        return $acciones;
    }

    private function get_portal_grupos_consumo_actions($user_id) {
        global $wpdb;

        $acciones = [];
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        if (
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_consumidores'") !== $tabla_consumidores ||
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_pedidos'") !== $tabla_pedidos
        ) {
            return $acciones;
        }

        $consumidor_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_consumidores} WHERE usuario_id = %d LIMIT 1",
            $user_id
        ));

        if ($consumidor_id <= 0) {
            return $acciones;
        }

        $ciclos = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);

        if (empty($ciclos)) {
            return $acciones;
        }

        $ciclo_id = (int) $ciclos[0];
        $fecha_cierre = (string) get_post_meta($ciclo_id, '_gc_fecha_cierre', true);
        $fecha_entrega = (string) get_post_meta($ciclo_id, '_gc_fecha_entrega', true);
        $titulo_ciclo = get_the_title($ciclo_id) ?: __('Ciclo activo', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $tiene_pedido = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE usuario_id = %d AND ciclo_id = %d",
            $user_id,
            $ciclo_id
        )) > 0;

        $candidatas = [];
        if ($fecha_cierre) {
            $candidatas[] = ['tipo' => 'consumo-cierre', 'icono' => '🧺', 'titulo' => $titulo_ciclo, 'fecha' => $fecha_cierre];
        }
        if ($fecha_entrega && $tiene_pedido) {
            $candidatas[] = ['tipo' => 'consumo-entrega', 'icono' => '🥕', 'titulo' => $titulo_ciclo, 'fecha' => $fecha_entrega];
        }

        foreach ($candidatas as $candidata) {
            $severity_slug = $this->get_portal_action_severity_from_date($candidata['fecha'] ?? '');
            if ($severity_slug === 'stable') {
                continue;
            }

            $acciones[] = [
                'tipo'   => $candidata['tipo'],
                'icono'  => $candidata['icono'],
                'titulo' => $candidata['titulo'],
                'fecha'  => $candidata['fecha'],
                'url'    => Flavor_Platform_Helpers::get_action_url('grupos_consumo', ''),
                'severity_slug' => $severity_slug,
                'severity_label' => $this->get_tool_severity_label($severity_slug),
            ];
        }

        return array_slice($acciones, 0, 1);
    }

    private function get_portal_energia_actions($user_id) {
        global $wpdb;

        $acciones = [];
        $tabla_instalaciones = $wpdb->prefix . 'flavor_energia_instalaciones';
        $tabla_lecturas = $wpdb->prefix . 'flavor_energia_lecturas';
        $tabla_liquidaciones = $wpdb->prefix . 'flavor_energia_liquidaciones';

        $instalaciones_activas = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_instalaciones'") === $tabla_instalaciones) {
            $instalaciones_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_instalaciones} WHERE estado = 'activa'"
            );
        }

        if ($instalaciones_activas > 0 && $wpdb->get_var("SHOW TABLES LIKE '$tabla_lecturas'") === $tabla_lecturas) {
            $lecturas_mes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_lecturas}
                 WHERE DATE_FORMAT(fecha_lectura, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
            );

            if ($lecturas_mes === 0) {
                $acciones[] = [
                    'tipo'   => 'energia-lectura',
                    'icono'  => '📈',
                    'titulo' => __('Registrar lectura energética', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'fecha'  => current_time('mysql'),
                    'url'    => Flavor_Platform_Helpers::get_action_url('energia_comunitaria', 'registrar-lectura'),
                    'severity_slug' => 'attention',
                    'severity_label' => $this->get_tool_severity_label('attention'),
                ];
            }
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_liquidaciones'") === $tabla_liquidaciones) {
            $liquidacion = $wpdb->get_row(
                "SELECT referencia, fecha_notificacion, fecha_aceptacion, created_at, estado
                 FROM {$tabla_liquidaciones}
                 WHERE estado IN ('generada', 'notificada')
                 ORDER BY COALESCE(fecha_notificacion, created_at) DESC
                 LIMIT 1",
                ARRAY_A
            );

            if (!empty($liquidacion)) {
                $fecha_base = (string) ($liquidacion['fecha_notificacion'] ?: $liquidacion['created_at'] ?: current_time('mysql'));
                $severity_slug = ($liquidacion['estado'] ?? '') === 'generada' ? 'attention' : 'followup';
                $acciones[] = [
                    'tipo'   => 'energia-liquidacion',
                    'icono'  => '💶',
                    'titulo' => $liquidacion['referencia'] ?: __('Liquidación energética', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'fecha'  => $fecha_base,
                    'url'    => Flavor_Platform_Helpers::get_action_url('energia_comunitaria', 'liquidaciones'),
                    'severity_slug' => $severity_slug,
                    'severity_label' => $this->get_tool_severity_label($severity_slug),
                ];
            }
        }

        return array_slice($acciones, 0, 2);
    }

    /**
     * Acciones de Talleres: próximas inscripciones y sesiones
     */
    private function get_portal_talleres_actions($user_id) {
        global $wpdb;

        $acciones = [];
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_inscripciones'") !== $tabla_inscripciones) {
            return $acciones;
        }

        // Talleres inscritos con sesión próxima
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $inscripciones = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT i.id, t.titulo, MIN(s.fecha) as proxima_sesion
             FROM {$tabla_inscripciones} i
             INNER JOIN {$tabla_talleres} t ON i.taller_id = t.id
             LEFT JOIN {$tabla_sesiones} s ON t.id = s.taller_id AND s.fecha >= NOW()
             WHERE i.usuario_id = %d
             AND i.estado = 'confirmada'
             GROUP BY i.id, t.titulo
             HAVING proxima_sesion IS NOT NULL
             ORDER BY proxima_sesion ASC
             LIMIT 2",
            $user_id
        ), ARRAY_A);

        foreach ($inscripciones as $inscripcion) {
            $fecha = (string) ($inscripcion['proxima_sesion'] ?? '');
            $severity_slug = $this->get_portal_action_severity_from_date($fecha);
            $acciones[] = [
                'tipo'   => 'taller',
                'icono'  => '🎨',
                'titulo' => $inscripcion['titulo'] ?? __('Taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha'  => $fecha,
                'url'    => Flavor_Platform_Helpers::get_action_url('talleres', 'mis-inscripciones'),
                'severity_slug' => $severity_slug,
                'severity_label' => $this->get_tool_severity_label($severity_slug),
            ];
        }

        return $acciones;
    }

    /**
     * Acciones de Biblioteca: devoluciones pendientes
     */
    private function get_portal_biblioteca_actions($user_id) {
        global $wpdb;

        $acciones = [];
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_prestamos'") !== $tabla_prestamos) {
            return $acciones;
        }

        // Préstamos activos con fecha de devolución próxima
        $prestamos = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT p.id, p.fecha_devolucion, l.titulo
             FROM {$tabla_prestamos} p
             LEFT JOIN {$tabla_libros} l ON p.libro_id = l.id
             WHERE p.usuario_id = %d
             AND p.estado = 'activo'
             AND p.fecha_devolucion IS NOT NULL
             ORDER BY p.fecha_devolucion ASC
             LIMIT 2",
            $user_id
        ), ARRAY_A);

        foreach ($prestamos as $prestamo) {
            $fecha = (string) ($prestamo['fecha_devolucion'] ?? '');
            $severity_slug = $this->get_portal_action_severity_from_date($fecha);
            $acciones[] = [
                'tipo'   => 'biblioteca',
                'icono'  => '📚',
                'titulo' => sprintf(__('Devolver: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $prestamo['titulo'] ?? __('Libro', FLAVOR_PLATFORM_TEXT_DOMAIN)),
                'fecha'  => $fecha,
                'url'    => Flavor_Platform_Helpers::get_action_url('biblioteca', 'mis-prestamos'),
                'severity_slug' => $severity_slug,
                'severity_label' => $this->get_tool_severity_label($severity_slug),
            ];
        }

        return $acciones;
    }

    /**
     * Acciones de Cursos: próximas clases
     */
    private function get_portal_cursos_actions($user_id) {
        global $wpdb;

        $acciones = [];
        $tabla_matriculas = $wpdb->prefix . 'flavor_cursos_matriculas';
        $tabla_sesiones = $wpdb->prefix . 'flavor_cursos_sesiones';
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        if (
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_matriculas'") !== $tabla_matriculas ||
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_sesiones'") !== $tabla_sesiones
        ) {
            return $acciones;
        }

        // Próximas sesiones de cursos matriculados
        $sesiones = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.titulo, s.fecha_hora, c.nombre as curso_nombre
             FROM {$tabla_sesiones} s
             INNER JOIN {$tabla_cursos} c ON s.curso_id = c.id
             INNER JOIN {$tabla_matriculas} m ON c.id = m.curso_id
             WHERE m.usuario_id = %d
             AND m.estado = 'activa'
             AND s.fecha_hora >= NOW()
             ORDER BY s.fecha_hora ASC
             LIMIT 2",
            $user_id
        ), ARRAY_A);

        foreach ($sesiones as $sesion) {
            $fecha = (string) ($sesion['fecha_hora'] ?? '');
            $severity_slug = $this->get_portal_action_severity_from_date($fecha);
            $acciones[] = [
                'tipo'   => 'curso',
                'icono'  => '📖',
                'titulo' => $sesion['titulo'] ?? $sesion['curso_nombre'] ?? __('Clase', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha'  => $fecha,
                'url'    => Flavor_Platform_Helpers::get_action_url('cursos', 'mis-cursos'),
                'severity_slug' => $severity_slug,
                'severity_label' => $this->get_tool_severity_label($severity_slug),
            ];
        }

        return $acciones;
    }

    /**
     * Acciones de Socios: cuotas por vencer
     */
    private function get_portal_socios_actions($user_id) {
        global $wpdb;

        $acciones = [];
        $tabla_socios = $wpdb->prefix . 'flavor_socios_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_socios'") !== $tabla_socios) {
            return $acciones;
        }

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$tabla_socios} WHERE usuario_id = %d LIMIT 1",
            $user_id
        ), ARRAY_A);

        if (empty($socio)) {
            return $acciones;
        }

        // Cuotas pendientes
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_cuotas'") === $tabla_cuotas) {
            $cuotas = (array) $wpdb->get_results($wpdb->prepare(
                "SELECT id, concepto, fecha_vencimiento, importe
                 FROM {$tabla_cuotas}
                 WHERE socio_id = %d
                 AND estado IN ('pendiente', 'vencida')
                 ORDER BY fecha_vencimiento ASC
                 LIMIT 2",
                (int) $socio['id']
            ), ARRAY_A);

            foreach ($cuotas as $cuota) {
                $fecha = (string) ($cuota['fecha_vencimiento'] ?? '');
                $severity_slug = $this->get_portal_action_severity_from_date($fecha);
                $acciones[] = [
                    'tipo'   => 'cuota',
                    'icono'  => '💳',
                    'titulo' => $cuota['concepto'] ?? __('Cuota pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'fecha'  => $fecha,
                    'url'    => Flavor_Platform_Helpers::get_action_url('socios', 'cuotas'),
                    'severity_slug' => $severity_slug,
                    'severity_label' => $this->get_tool_severity_label($severity_slug),
                ];
            }
        }

        return $acciones;
    }

    /**
     * Acciones de Ayuda Vecinal: solicitudes pendientes de respuesta
     */
    private function get_portal_ayuda_vecinal_actions($user_id) {
        global $wpdb;

        $acciones = [];
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_solicitudes'") !== $tabla_solicitudes) {
            return $acciones;
        }

        // Solicitudes de ayuda abiertas del usuario
        $solicitudes = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, fecha_creacion
             FROM {$tabla_solicitudes}
             WHERE usuario_id = %d
             AND estado IN ('abierta', 'en_progreso')
             ORDER BY fecha_creacion DESC
             LIMIT 2",
            $user_id
        ), ARRAY_A);

        foreach ($solicitudes as $solicitud) {
            $fecha = (string) ($solicitud['fecha_creacion'] ?? '');
            $severity_slug = $this->get_portal_action_severity_from_date($fecha);
            $acciones[] = [
                'tipo'   => 'ayuda-vecinal',
                'icono'  => '🤝',
                'titulo' => $solicitud['titulo'] ?? __('Ayuda comprometida', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha'  => $fecha,
                'url'    => Flavor_Platform_Helpers::get_action_url('ayuda_vecinal', 'mis-compromisos'),
                'severity_slug' => $severity_slug,
                'severity_label' => $this->get_tool_severity_label($severity_slug),
            ];
        }

        return $acciones;
    }

    /**
     * Renderiza enlaces útiles
     */
    private function render_useful_links() {
        $links = [
            ['url' => home_url('/servicios/'), 'text' => __('Explorar servicios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔍'],
            ['url' => Flavor_Platform_Helpers::get_action_url('', ''), 'text' => __('Volver al portal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🧭'],
            ['url' => admin_url('profile.php'), 'text' => __('Abrir perfil', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '👤'],
        ];

        ob_start();
        ?>
        <ul class="flavor-useful-links">
            <?php foreach ($links as $link) : ?>
                <li class="flavor-useful-link">
                    <a href="<?php echo esc_url($link['url']); ?>">
                        <span class="flavor-useful-link__icon"><?php echo $link['icon']; ?></span>
                        <span class="flavor-useful-link__text"><?php echo esc_html($link['text']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza dashboard con tarjetas de estadísticas
     */
    public function render_dashboard_stats($atts) {
        // Requerir login
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">
                <p>' . __('Debes iniciar sesión para ver tus estadísticas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
                <a href="' . esc_url(wp_login_url($this->get_current_request_url())) . '" class="flavor-button flavor-button--primary">' . __('Iniciar Sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>
            </div>';
        }

        $atts = shortcode_atts([
            'columnas' => '4',
            'mostrar_titulo' => 'yes',
        ], $atts);

        // Obtener configuración de diseño
        $design_settings = flavor_get_main_settings();

        $modulos = $this->get_modulos_con_stats();

        if (empty($modulos)) {
            return '<p class="flavor-no-stats">' . __('No hay estadísticas disponibles en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-dashboard-stats">
            <?php if ($atts['mostrar_titulo'] === 'yes') : ?>
                <div class="flavor-dashboard-stats__header">
                    <h2 class="flavor-dashboard-stats__title"><?php _e('Tus Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p class="flavor-dashboard-stats__subtitle"><?php _e('Resumen de tu actividad en la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>

            <div class="flavor-stats-cards flavor-stats-cards--col-<?php echo esc_attr($atts['columnas']); ?>">
                <?php foreach ($modulos as $modulo) : ?>
                    <?php $this->render_stat_card($modulo); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una tarjeta de estadística
     */
    private function render_stat_card($modulo) {
        ?>
        <div
            class="flavor-stat-card flavor-stat-card--role-<?php echo esc_attr($modulo['role_slug'] ?? 'vertical'); ?>"
            data-modulo="<?php echo esc_attr($modulo['id']); ?>"
            data-severity="<?php echo esc_attr($modulo['severity']['slug'] ?? ''); ?>"
        >
            <div class="flavor-stat-card__header">
                <div class="flavor-stat-card__icon-wrapper">
                    <span class="flavor-stat-card__icon"><?php echo $modulo['icon']; ?></span>
                </div>
                <div class="flavor-stat-card__meta">
                    <span class="flavor-stat-card__badge"><?php echo esc_html($modulo['role_badge'] ?? $modulo['name']); ?></span>
                    <?php if (!empty($modulo['context_badge'])) : ?>
                        <span class="flavor-stat-card__context"><?php echo esc_html($modulo['context_badge']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flavor-stat-card__body">
                <div class="flavor-stat-card__module-name"><?php echo esc_html($modulo['summary_label'] ?? $modulo['name']); ?></div>
                <?php if (!empty($modulo['severity']['label'])) : ?>
                    <div class="flavor-stat-card__severity flavor-stat-card__severity--<?php echo esc_attr($modulo['severity']['slug']); ?>" title="<?php echo esc_attr($modulo['severity']['reason'] ?? ''); ?>">
                        <?php echo esc_html($modulo['severity']['label']); ?>
                    </div>
                <?php endif; ?>
                <div class="flavor-stat-card__value"><?php echo esc_html($modulo['stat_value']); ?></div>
                <div class="flavor-stat-card__label"><?php echo esc_html($modulo['stat_label']); ?></div>

                <?php if (!empty($modulo['secondary_stats'])) : ?>
                    <div class="flavor-stat-card__secondary">
                        <?php foreach ($modulo['secondary_stats'] as $stat) : ?>
                            <div class="flavor-stat-card__secondary-item">
                                <span class="flavor-stat-card__secondary-value"><?php echo esc_html($stat['value']); ?></span>
                                <span class="flavor-stat-card__secondary-label"><?php echo esc_html($stat['label']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flavor-stat-card__footer">
                <a href="<?php echo esc_url($modulo['url']); ?>" class="flavor-stat-card__link">
                    <?php echo esc_html(($modulo['role_slug'] ?? 'vertical') === 'base' ? __('Abrir ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Abrir módulo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?> <span class="flavor-stat-card__arrow">→</span>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene módulos con sus estadísticas formateadas para tarjetas
     */
    private function get_modulos_con_stats() {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $modulos_activos = $loader->get_loaded_modules();
        $modulos_con_stats = [];
        $user_id = get_current_user_id();

        foreach ($modulos_activos as $id => $instance) {
            // Verificar acceso
            $tiene_acceso = true;
            if (class_exists('Flavor_Module_Access_Control')) {
                $control = Flavor_Module_Access_Control::get_instance();
                $tiene_acceso = $control->user_can_access($id);
            }

            if (!$tiene_acceso) {
                continue;
            }

            $stat_data = $this->get_stat_card_data($id, $user_id);

            if ($stat_data) {
                $severity = $this->get_module_tool_native_severity($id);
                $modulos_con_stats[] = array_merge([
                    'id' => $id,
                    'name' => $instance->name ?? ucfirst($id),
                    'role_slug' => $this->get_portal_module_role_slug($instance),
                    'role_badge' => $this->get_portal_module_role_badge($instance),
                    'summary_label' => $this->get_portal_module_summary_label($id, $instance),
                    'context_badge' => $this->get_portal_module_context_badge($instance),
                    'severity' => $severity,
                    'icon' => $this->get_modulo_icon($id),
                    'url' => home_url('/' . str_replace('_', '-', $id) . '/'),
                ], $stat_data);
            }
        }

        return $modulos_con_stats;
    }

    /**
     * Obtiene datos de estadística formateados para una tarjeta
     */
    private function get_stat_card_data($modulo_id, $user_id) {
        global $wpdb;

        switch ($modulo_id) {
            case 'banco_tiempo':
                $tabla = $wpdb->prefix . 'flavor_banco_tiempo_usuarios';
                $saldo = $wpdb->get_var($wpdb->prepare(
                    "SELECT saldo_horas FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));

                $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
                $ofrecidos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_servicios WHERE ofertante_id = %d",
                    $user_id
                ));

                if ($saldo !== null) {
                    return [
                        'stat_value' => number_format($saldo, 1),
                        'stat_label' => __('Horas Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'secondary_stats' => [
                            ['value' => $ofrecidos, 'label' => __('Servicios ofrecidos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                        ],
                    ];
                }
                break;

            case 'talleres':
                $tabla = $wpdb->prefix . 'flavor_talleres_inscripciones';
                $inscritos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'confirmada'",
                    $user_id
                ));

                $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
                $organizados = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_talleres WHERE organizador_id = %d",
                    $user_id
                ));

                return [
                    'stat_value' => $inscritos,
                    'stat_label' => __('Talleres Inscritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $organizados, 'label' => __('Talleres organizados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'grupos_consumo':
                $tabla = $wpdb->prefix . 'flavor_gc_miembros';
                $grupos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'activo'",
                    $user_id
                ));

                $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
                $pedidos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_pedidos WHERE consumidor_id = %d",
                    $user_id
                ));

                return [
                    'stat_value' => $grupos,
                    'stat_label' => __('Grupos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $pedidos, 'label' => __('Pedidos realizados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'ayuda_vecinal':
                $tabla = $wpdb->prefix . 'flavor_ayuda_solicitudes';
                $solicitudes = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE solicitante_id = %d AND estado = 'abierta'",
                    $user_id
                ));

                $ayudas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE voluntario_id = %d AND estado = 'completada'",
                    $user_id
                ));

                return [
                    'stat_value' => $solicitudes,
                    'stat_label' => __('Solicitudes Activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $ayudas, 'label' => __('Ayudas prestadas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'eventos':
                $tabla = $wpdb->prefix . 'flavor_eventos_asistentes';
                $proximos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla ea
                     INNER JOIN {$wpdb->prefix}flavor_eventos e ON ea.evento_id = e.id
                     WHERE ea.usuario_id = %d AND ea.estado = 'confirmado' AND e.fecha >= CURDATE()",
                    $user_id
                ));

                $asistidos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla ea
                     INNER JOIN {$wpdb->prefix}flavor_eventos e ON ea.evento_id = e.id
                     WHERE ea.usuario_id = %d AND e.fecha < CURDATE()",
                    $user_id
                ));

                return [
                    'stat_value' => $proximos,
                    'stat_label' => __('Próximos Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $asistidos, 'label' => __('Eventos asistidos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'biblioteca':
                $tabla = $wpdb->prefix . 'flavor_biblioteca_prestamos';
                $prestados = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'prestado'",
                    $user_id
                ));

                $historial = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'devuelto'",
                    $user_id
                ));

                return [
                    'stat_value' => $prestados,
                    'stat_label' => __('Libros Prestados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $historial, 'label' => __('Libros leídos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'espacios_comunes':
                $tabla = $wpdb->prefix . 'flavor_espacios_reservas';
                $activas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha >= CURDATE() AND estado = 'confirmada'",
                    $user_id
                ));

                $totales = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));

                return [
                    'stat_value' => $activas,
                    'stat_label' => __('Reservas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $totales, 'label' => __('Total reservas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'huertos_urbanos':
                $tabla = $wpdb->prefix . 'flavor_huertos_parcelas';
                $parcelas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'asignada'",
                    $user_id
                ));

                $tabla_cosechas = $wpdb->prefix . 'flavor_huertos_cosechas';
                $cosechas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_cosechas WHERE usuario_id = %d",
                    $user_id
                ));

                return [
                    'stat_value' => $parcelas,
                    'stat_label' => __('Parcelas Asignadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $cosechas, 'label' => __('Cosechas registradas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'incidencias':
                $tabla = $wpdb->prefix . 'flavor_incidencias';
                $abiertas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado IN ('pendiente', 'en_progreso')",
                    $user_id
                ));

                $resueltas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'resuelta'",
                    $user_id
                ));

                return [
                    'stat_value' => $abiertas,
                    'stat_label' => __('Incidencias Abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $resueltas, 'label' => __('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'bicicletas_compartidas':
                $tabla = $wpdb->prefix . 'flavor_bicicletas_prestamos';
                $activo = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha_devolucion IS NULL",
                    $user_id
                ));

                $total_uso = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));

                return [
                    'stat_value' => $activo ? __('En uso', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'stat_label' => __('Estado Actual', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $total_uso, 'label' => __('Usos totales', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'carpooling':
                $tabla = $wpdb->prefix . 'flavor_carpooling_reservas';
                $proximos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'confirmada' AND fecha >= CURDATE()",
                    $user_id
                ));

                $completados = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha < CURDATE()",
                    $user_id
                ));

                return [
                    'stat_value' => $proximos,
                    'stat_label' => __('Viajes Próximos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $completados, 'label' => __('Viajes completados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'foros':
                $tabla_temas = $wpdb->prefix . 'flavor_foros_temas';
                $temas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_temas WHERE autor_id = %d",
                    $user_id
                ));

                $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';
                $respuestas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_respuestas WHERE autor_id = %d",
                    $user_id
                ));

                return [
                    'stat_value' => $temas,
                    'stat_label' => __('Temas Creados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $respuestas, 'label' => __('Respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'marketplace':
                $tabla = $wpdb->prefix . 'flavor_marketplace_anuncios';
                $activos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE vendedor_id = %d AND estado = 'publicado'",
                    $user_id
                ));

                $ventas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE vendedor_id = %d AND estado = 'vendido'",
                    $user_id
                ));

                return [
                    'stat_value' => $activos,
                    'stat_label' => __('Anuncios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $ventas, 'label' => __('Vendidos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'reciclaje':
                $tabla = $wpdb->prefix . 'flavor_reciclaje_puntos';
                $puntos = $wpdb->get_var($wpdb->prepare(
                    "SELECT puntos_acumulados FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));

                $registros = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_reciclaje_depositos WHERE usuario_id = %d",
                    $user_id
                ));

                return [
                    'stat_value' => $puntos ?? 0,
                    'stat_label' => __('Puntos Verdes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $registros, 'label' => __('Registros', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'cursos':
                $tabla = $wpdb->prefix . 'flavor_cursos_matriculas';
                $activos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE alumno_id = %d AND estado = 'activo'",
                    $user_id
                ));

                $completados = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE alumno_id = %d AND estado = 'completado'",
                    $user_id
                ));

                return [
                    'stat_value' => $activos,
                    'stat_label' => __('Cursos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $completados, 'label' => __('Completados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];

            case 'comunidades':
                $tabla = $wpdb->prefix . 'flavor_comunidades_miembros';
                $activas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'activo'",
                    $user_id
                ));

                return [
                    'stat_value' => $activas,
                    'stat_label' => __('Comunidades Activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [],
                ];

            case 'socios':
                $tabla = $wpdb->prefix . 'flavor_socios';
                $socio = $wpdb->get_row($wpdb->prepare(
                    "SELECT estado, numero_socio FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));

                if ($socio && $socio->estado === 'activo') {
                    return [
                        'stat_value' => '#' . $socio->numero_socio,
                        'stat_label' => __('Número de Socio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'secondary_stats' => [
                            ['value' => '✅', 'label' => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                        ],
                    ];
                }
                break;

            case 'parkings':
                $tabla = $wpdb->prefix . 'flavor_parkings_reservas';
                $activa = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha_inicio <= NOW() AND fecha_fin >= NOW()",
                    $user_id
                ));

                $total = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));

                return [
                    'stat_value' => $activa ? __('Reservada', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Sin reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'stat_label' => __('Plaza de Parking', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'secondary_stats' => [
                        ['value' => $total, 'label' => __('Reservas totales', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ],
                ];
        }

        return null;
    }

    /**
     * Renderiza grid de módulos
     */
    public function render_modulos_grid($atts = []) {
        $atts = wp_parse_args($atts, [
            'tipo' => 'servicios', // 'servicios' o 'portal'
            'columnas' => '3',
        ]);

        $modulos = $this->get_modulos_disponibles($atts['tipo']);

        if (empty($modulos)) {
            return '<p class="flavor-no-modulos">' . __('No hay módulos disponibles en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-modulos-grid flavor-modulos-grid--col-<?php echo esc_attr($atts['columnas']); ?>">
            <?php foreach ($modulos as $modulo) : ?>
                <div class="flavor-modulo-card" data-modulo="<?php echo esc_attr($modulo['id']); ?>">
                    <div class="flavor-modulo-card__icon">
                        <?php echo $modulo['icon']; ?>
                    </div>
                    <div class="flavor-modulo-card__content">
                        <h3 class="flavor-modulo-card__title"><?php echo esc_html($modulo['name']); ?></h3>
                        <p class="flavor-modulo-card__description"><?php echo esc_html($modulo['description']); ?></p>

                        <?php if (!empty($modulo['stats'])) : ?>
                            <div class="flavor-modulo-card__stats">
                                <?php foreach ($modulo['stats'] as $stat) : ?>
                                    <span class="flavor-stat"><?php echo esc_html($stat); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-modulo-card__actions">
                        <?php if ($modulo['tiene_acceso']) : ?>
                            <a href="<?php echo esc_url($modulo['url']); ?>" class="flavor-button flavor-button--primary">
                                <?php echo esc_html($modulo['action_text'] ?? __('Acceder', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </a>
                        <?php else : ?>
                            <span class="flavor-modulo-card__locked">
                                🔒 <?php _e('Requiere registro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene módulos disponibles según contexto
     */
    private function get_modulos_disponibles($tipo = 'servicios') {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $modulos_activos = $loader->get_loaded_modules();
        $modulos_disponibles = [];

        foreach ($modulos_activos as $id => $instance) {
            // Verificar acceso
            $tiene_acceso = true;
            if (class_exists('Flavor_Module_Access_Control')) {
                $control = Flavor_Module_Access_Control::get_instance();
                $tiene_acceso = $control->user_can_access($id);
            }

            // Para landing "servicios", mostrar todos los módulos
            // Para "portal", solo los que el usuario puede acceder
            if ($tipo === 'portal' && !$tiene_acceso) {
                continue;
            }

            $modulos_disponibles[] = [
                'id' => $id,
                'name' => $instance->name ?? ucfirst($id),
                'description' => $instance->description ?? '',
                'icon' => $this->get_modulo_icon($id),
                'url' => home_url('/' . str_replace('_', '-', $id) . '/'),
                'tiene_acceso' => $tiene_acceso,
                'stats' => $this->get_modulo_stats($id, $instance),
                'action_text' => $this->get_modulo_action_text($id),
            ];
        }

        return $modulos_disponibles;
    }

    /**
     * Obtiene el icono de un módulo
     */
    private function get_modulo_icon($modulo_id) {
        $icons = [
            'talleres' => '🎓',
            'ayuda_vecinal' => '🤝',
            'eventos' => '📅',
            'grupos_consumo' => '🥬',
            'banco_tiempo' => '⏰',
            'socios' => '👥',
            'foros' => '💬',
            'incidencias' => '🔧',
            'espacios_comunes' => '🏢',
            'huertos_urbanos' => '🌱',
            'biblioteca' => '📚',
            'bicicletas_compartidas' => '🚲',
            'carpooling' => '🚗',
            'reciclaje' => '♻️',
            'marketplace' => '🛒',
        ];

        return $icons[$modulo_id] ?? '📦';
    }

    /**
     * Obtiene una etiqueta corta por rol ecosistémico para tarjetas del portal.
     *
     * @param object $instance
     * @return string
     */
    private function get_portal_module_role_badge($instance) {
        if (!is_object($instance) || !method_exists($instance, 'get_ecosystem_metadata')) {
            return __('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        $role = $this->get_portal_module_role_slug($instance);

        switch ($role) {
            case 'base':
                return __('Base', FLAVOR_PLATFORM_TEXT_DOMAIN);
            case 'base-standalone':
                return __('Base local', FLAVOR_PLATFORM_TEXT_DOMAIN);
            case 'transversal':
                return __('Transversal', FLAVOR_PLATFORM_TEXT_DOMAIN);
            case 'vertical':
            default:
                return __('Operativo', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }
    }

    /**
     * Obtiene el slug del rol ecosistémico del módulo.
     *
     * @param object $instance
     * @return string
     */
    private function get_portal_module_role_slug($instance) {
        if (!is_object($instance) || !method_exists($instance, 'get_ecosystem_metadata')) {
            return 'vertical';
        }

        $ecosystem = (array) $instance->get_ecosystem_metadata();
        return sanitize_key((string) ($ecosystem['display_role'] ?? $ecosystem['module_role'] ?? 'vertical')) ?: 'vertical';
    }

    /**
     * Obtiene una lectura humana del módulo para el contexto del portal.
     *
     * @param string $module_id
     * @param object $instance
     * @return string
     */
    private function get_portal_module_summary_label($module_id, $instance) {
        $default_name = is_object($instance) && method_exists($instance, 'get_name')
            ? $instance->get_name()
            : ucfirst(str_replace('_', ' ', $module_id));

        if (!is_object($instance) || !method_exists($instance, 'get_ecosystem_metadata')) {
            return $default_name;
        }

        $ecosystem = (array) $instance->get_ecosystem_metadata();
        $role = $ecosystem['display_role'] ?? $ecosystem['module_role'] ?? 'vertical';

        $role_labels = [
            'base' => __('Base comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'base-standalone' => __('Base local', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'vertical' => __('Servicio activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transversal' => __('Capa de soporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        $contexts = [];
        if (method_exists($instance, 'get_dashboard_metadata')) {
            $dashboard = (array) $instance->get_dashboard_metadata();
            $contexts = array_values(array_filter((array) ($dashboard['client_contexts'] ?? [])));
        }

        if (!empty($contexts)) {
            $context = str_replace('_', ' ', $contexts[0]);
            $context = function_exists('mb_convert_case')
                ? mb_convert_case($context, MB_CASE_TITLE, 'UTF-8')
                : ucwords($context);

            return sprintf('%s · %s', $role_labels[$role] ?? __('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN), $context);
        }

        return sprintf('%s · %s', $role_labels[$role] ?? __('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN), $default_name);
    }

    /**
     * Devuelve un contexto corto y humano para la tarjeta del portal.
     *
     * @param object $instance
     * @return string
     */
    private function get_portal_module_context_badge($instance) {
        if (!is_object($instance) || !method_exists($instance, 'get_dashboard_metadata')) {
            return '';
        }

        $dashboard = (array) $instance->get_dashboard_metadata();
        $contexts = array_values(array_filter((array) ($dashboard['client_contexts'] ?? [])));

        if (empty($contexts)) {
            return '';
        }

        return $this->get_tool_context_label([$contexts[0]]);
    }

    /**
     * Obtiene estadísticas del módulo
     */
    private function get_modulo_stats($modulo_id, $instance) {
        $stats = [];

        // Si el usuario está logueado, obtener stats personalizadas
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();

            // Intentar obtener stats personalizadas del usuario
            if (method_exists($instance, 'get_user_stats')) {
                $user_stats = $instance->get_user_stats($user_id);
                if (!empty($user_stats)) {
                    return $user_stats;
                }
            }

            // Fallback: generar stats básicas según el módulo
            $stats = $this->get_modulo_stats_fallback($modulo_id, $user_id);
        } else {
            // Para usuarios no logueados, intentar stats públicas
            if (method_exists($instance, 'get_public_stats')) {
                return $instance->get_public_stats();
            }
        }

        return $stats;
    }

    /**
     * Obtiene stats básicas cuando el módulo no tiene get_user_stats()
     */
    private function get_modulo_stats_fallback($modulo_id, $user_id) {
        global $wpdb;
        $stats = [];

        switch ($modulo_id) {
            case 'banco_tiempo':
                $tabla = $wpdb->prefix . 'flavor_banco_tiempo_usuarios';
                $saldo = $wpdb->get_var($wpdb->prepare(
                    "SELECT saldo_horas FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));
                if ($saldo !== null) {
                    $stats[] = sprintf(__('%s horas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($saldo, 1));
                }
                break;

            case 'talleres':
                $tabla = $wpdb->prefix . 'flavor_talleres_inscripciones';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'confirmada'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d talleres inscritos', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'grupos_consumo':
                // Verificar si es miembro de algún grupo
                $tabla = $wpdb->prefix . 'flavor_gc_miembros';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'activo'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('Miembro de %d grupo(s)', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'ayuda_vecinal':
                $tabla = $wpdb->prefix . 'flavor_ayuda_solicitudes';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE solicitante_id = %d AND estado = 'abierta'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d solicitudes activas', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'eventos':
                $tabla = $wpdb->prefix . 'flavor_eventos_asistentes';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'confirmado'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d eventos próximos', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'socios':
                $tabla = $wpdb->prefix . 'flavor_socios';
                $socio = $wpdb->get_row($wpdb->prepare(
                    "SELECT estado FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));
                if ($socio) {
                    $estados = [
                        'activo' => '✅ ' . __('Socio Activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'pendiente' => '⏳ ' . __('Pendiente de aprobación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'baja' => '❌ ' . __('De baja', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ];
                    $stats[] = $estados[$socio->estado] ?? $socio->estado;
                }
                break;

            case 'biblioteca':
                $tabla = $wpdb->prefix . 'flavor_biblioteca_prestamos';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'prestado'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d libros prestados', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'espacios_comunes':
                $tabla = $wpdb->prefix . 'flavor_espacios_reservas';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha >= CURDATE() AND estado = 'confirmada'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d reservas activas', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'huertos_urbanos':
                $tabla = $wpdb->prefix . 'flavor_huertos_parcelas';
                $parcela = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'asignada'",
                    $user_id
                ));
                if ($parcela > 0) {
                    $stats[] = '🌱 ' . sprintf(__('%d parcela(s) asignadas', FLAVOR_PLATFORM_TEXT_DOMAIN), $parcela);
                }
                break;

            case 'incidencias':
                $tabla = $wpdb->prefix . 'flavor_incidencias';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado IN ('pendiente', 'en_progreso')",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d incidencias abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'bicicletas_compartidas':
                $tabla = $wpdb->prefix . 'flavor_bicicletas_prestamos';
                $activo = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha_devolucion IS NULL",
                    $user_id
                ));
                if ($activo > 0) {
                    $stats[] = '🚲 ' . __('Bicicleta en uso', FLAVOR_PLATFORM_TEXT_DOMAIN);
                }
                break;

            case 'carpooling':
                $tabla = $wpdb->prefix . 'flavor_carpooling_reservas';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'confirmada' AND fecha >= CURDATE()",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d viajes reservados', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'foros':
                $tabla = $wpdb->prefix . 'flavor_foros_temas';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE autor_id = %d",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d temas creados', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'marketplace':
                $tabla = $wpdb->prefix . 'flavor_marketplace_anuncios';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE vendedor_id = %d AND estado = 'publicado'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d anuncios activos', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'reciclaje':
                $tabla = $wpdb->prefix . 'flavor_reciclaje_puntos';
                $puntos = $wpdb->get_var($wpdb->prepare(
                    "SELECT puntos_acumulados FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));
                if ($puntos > 0) {
                    $stats[] = '♻️ ' . sprintf(__('%d puntos verdes', FLAVOR_PLATFORM_TEXT_DOMAIN), $puntos);
                }
                break;

            case 'cursos':
                $tabla = $wpdb->prefix . 'flavor_cursos_matriculas';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE alumno_id = %d AND estado = 'activo'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d cursos activos', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'comunidades':
                $tabla = $wpdb->prefix . 'flavor_comunidades_miembros';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'activo'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('Miembro de %d comunidad(es)', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'radio':
            case 'podcast':
                $tabla = $wpdb->prefix . 'flavor_' . $modulo_id . '_suscripciones';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d suscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN), $count);
                }
                break;

            case 'parkings':
                $tabla = $wpdb->prefix . 'flavor_parkings_reservas';
                $reserva = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha_inicio <= NOW() AND fecha_fin >= NOW()",
                    $user_id
                ));
                if ($reserva > 0) {
                    $stats[] = '🅿️ ' . __('Plaza reservada', FLAVOR_PLATFORM_TEXT_DOMAIN);
                }
                break;
        }

        return $stats;
    }

    /**
     * Obtiene texto de acción del módulo
     */
    private function get_modulo_action_text($modulo_id) {
        $texts = [
            'talleres' => __('Ver Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'ayuda_vecinal' => __('Pedir/Ofrecer Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos' => __('Ver Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'grupos_consumo' => __('Ver Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'banco_tiempo' => __('Ver Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $texts[$modulo_id] ?? __('Acceder', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Renderiza estadísticas generales
     */
    private function render_stats() {
        $stats = [
            'modulos_activos' => count($this->get_modulos_disponibles('servicios')),
            'usuarios_activos' => $this->get_usuarios_activos(),
            'actividad_reciente' => $this->get_actividad_total(),
        ];

        ob_start();
        ?>
        <div class="flavor-stats-bar">
            <div class="flavor-stat-item">
                <span class="flavor-stat-item__value"><?php echo esc_html($stats['modulos_activos']); ?></span>
                <span class="flavor-stat-item__label"><?php _e('Servicios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="flavor-stat-item">
                <span class="flavor-stat-item__value"><?php echo esc_html($stats['usuarios_activos']); ?></span>
                <span class="flavor-stat-item__label"><?php _e('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="flavor-stat-item">
                <span class="flavor-stat-item__value"><?php echo esc_html($stats['actividad_reciente']); ?></span>
                <span class="flavor-stat-item__label"><?php _e('Actividades este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza notificaciones del usuario
     */
    private function render_notificaciones() {
        // TODO: Integrar con sistema de notificaciones si existe
        ob_start();
        ?>
        <div class="flavor-notificaciones">
            <div class="flavor-notificacion flavor-notificacion--info">
                <span class="flavor-notificacion__icon">ℹ️</span>
                <div class="flavor-notificacion__content">
                    <p><?php _e('Bienvenido a tu portal. Explora los servicios disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza accesos rápidos
     */
    private function render_accesos_rapidos() {
        $accesos = $this->get_accesos_rapidos();

        ob_start();
        ?>
        <div class="flavor-accesos-rapidos">
            <?php foreach ($accesos as $acceso) : ?>
                <a href="<?php echo esc_url($acceso['url']); ?>" class="flavor-acceso-rapido">
                    <span class="flavor-acceso-rapido__icon"><?php echo $acceso['icon']; ?></span>
                    <span class="flavor-acceso-rapido__text"><?php echo esc_html($acceso['text']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene accesos rápidos del usuario
     */
    private function get_accesos_rapidos() {
        $accesos = [];

        // Accesos según módulos activos y permisos
        $loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;
        if (!$loader) {
            return $accesos;
        }

        $modulos = $loader->get_loaded_modules();

        if (isset($modulos['talleres'])) {
            $accesos[] = [
                'icon' => '➕',
                'text' => __('Crear Taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => home_url('/talleres/crear/'),
            ];
        }

        if (isset($modulos['ayuda_vecinal'])) {
            $accesos[] = [
                'icon' => '🆘',
                'text' => __('Solicitar Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => home_url('/ayuda-vecinal/solicitar/'),
            ];
        }

        if (isset($modulos['eventos'])) {
            $accesos[] = [
                'icon' => '📅',
                'text' => __('Crear Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => home_url('/eventos/crear/'),
            ];
        }

        return $accesos;
    }

    /**
     * Renderiza actividad reciente
     */
    private function render_actividad_reciente() {
        ob_start();
        ?>
        <div class="flavor-actividad-reciente">
            <p class="flavor-actividad-reciente__empty"><?php _e('No hay actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza estadísticas personales
     */
    private function render_mis_stats() {
        ob_start();
        ?>
        <div class="flavor-mis-stats">
            <div class="flavor-stat-personal">
                <span class="flavor-stat-personal__label"><?php _e('Participaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="flavor-stat-personal__value">0</span>
            </div>
            <div class="flavor-stat-personal">
                <span class="flavor-stat-personal__label"><?php _e('Contribuciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="flavor-stat-personal__value">0</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Helpers para stats
     */
    private function get_usuarios_activos() {
        $users = count_users();
        return $users['total_users'] ?? 0;
    }

    private function get_actividad_total() {
        // TODO: Calcular actividad real de los módulos
        return rand(50, 200);
    }

    /**
     * Obtiene los datos de una herramienta para el strip de favoritos
     *
     * @param string $tool_id ID de la herramienta
     * @return array Datos de la herramienta
     */
    private function get_tool_data_for_favorite($tool_id) {
        // Intentar obtener desde módulo cargado
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            $modules = $loader->get_loaded_modules();

            if (isset($modules[$tool_id])) {
                $instance = $modules[$tool_id];
                $config = $this->get_module_quick_action_config($tool_id, $instance);
                return [
                    'title' => $config['title'] ?? $instance->name ?? ucfirst(str_replace('_', ' ', $tool_id)),
                    'icon' => $config['icon'] ?? $this->get_modulo_icon($tool_id),
                    'url' => $config['url'] ?? Flavor_Platform_Helpers::get_action_url($tool_id, ''),
                ];
            }
        }

        // Fallback genérico
        return [
            'title' => ucfirst(str_replace('_', ' ', $tool_id)),
            'icon' => $this->get_modulo_icon($tool_id),
            'url' => Flavor_Platform_Helpers::get_action_url($tool_id, ''),
        ];
    }
}
