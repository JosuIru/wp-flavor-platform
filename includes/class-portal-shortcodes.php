<?php
/**
 * Shortcodes del Portal del Cliente
 *
 * Proporciona shortcodes para el portal de servicios y dashboard personalizado
 *
 * @package FlavorChatIA
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
    }

    /**
     * Encola estilos del portal
     */
    public function enqueue_styles() {
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        wp_enqueue_style(
            'flavor-portal',
            FLAVOR_CHAT_IA_URL . 'assets/css/portal' . $suffix . '.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );
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
            'titulo' => __('Servicios de la Comunidad', 'flavor-chat-ia'),
            'subtitulo' => __('Descubre todo lo que tu comunidad tiene para ofrecer', 'flavor-chat-ia'),
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
                        <h2><?php _e('¿Quieres acceder a más servicios?', 'flavor-chat-ia'); ?></h2>
                        <p><?php _e('Regístrate para participar activamente en tu comunidad', 'flavor-chat-ia'); ?></p>
                        <a href="<?php echo wp_registration_url(); ?>" class="flavor-button flavor-button--primary">
                            <?php _e('Crear Cuenta', 'flavor-chat-ia'); ?>
                        </a>
                        <a href="<?php echo wp_login_url(); ?>" class="flavor-button flavor-button--secondary">
                            <?php _e('Iniciar Sesión', 'flavor-chat-ia'); ?>
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

        $atts = shortcode_atts([
            'mostrar_actividad' => 'yes',
            'mostrar_notificaciones' => 'yes',
            'mostrar_breadcrumbs' => 'yes',
            'columnas' => '4',
        ], $atts);

        $current_user = wp_get_current_user();

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
                        <h1 class="flavor-portal__title">
                            <?php
                            $hora = (int) current_time('H');
                            if ($hora < 12) {
                                printf(__('Buenos días, %s', 'flavor-chat-ia'), esc_html($current_user->display_name));
                            } elseif ($hora < 20) {
                                printf(__('Buenas tardes, %s', 'flavor-chat-ia'), esc_html($current_user->display_name));
                            } else {
                                printf(__('Buenas noches, %s', 'flavor-chat-ia'), esc_html($current_user->display_name));
                            }
                            ?>
                        </h1>
                        <p class="flavor-portal__subtitle">
                            <?php _e('Tu centro de control comunitario', 'flavor-chat-ia'); ?>
                        </p>
                    </div>

                    <div class="flavor-portal__header-actions">
                        <?php echo $this->render_header_actions(); ?>
                    </div>
                </div>
            </div>

            <?php if ($atts['mostrar_notificaciones'] === 'yes') : ?>
                <!-- Notificaciones Destacadas -->
                <div class="flavor-portal__notifications-bar">
                    <?php echo $this->render_notifications_bar(); ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard de Estadísticas -->
            <div class="flavor-portal__section">
                <div class="flavor-section-header">
                    <h2 class="flavor-section-title"><?php _e('Resumen de Actividad', 'flavor-chat-ia'); ?></h2>
                    <a href="<?php echo home_url('/servicios/'); ?>" class="flavor-link-all">
                        <?php _e('Ver todos los servicios', 'flavor-chat-ia'); ?> →
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
                            <?php _e('Accesos Rápidos', 'flavor-chat-ia'); ?>
                        </h2>
                        <?php echo $this->render_quick_actions_enhanced(); ?>
                    </div>

                    <?php if ($atts['mostrar_actividad'] === 'yes') : ?>
                        <!-- Feed de Actividad -->
                        <div class="flavor-portal__section">
                            <h2 class="flavor-portal__section-title">
                                <span class="flavor-title-icon">📋</span>
                                <?php _e('Actividad Reciente', 'flavor-chat-ia'); ?>
                            </h2>
                            <?php echo $this->render_activity_feed(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <aside class="flavor-portal__sidebar">
                    <!-- Widget de Perfil -->
                    <div class="flavor-portal__widget flavor-portal__widget--profile">
                        <?php echo $this->render_profile_widget($current_user); ?>
                    </div>

                    <!-- Próximas Acciones -->
                    <div class="flavor-portal__widget">
                        <h3 class="flavor-portal__widget-title">
                            <?php _e('Próximas Acciones', 'flavor-chat-ia'); ?>
                        </h3>
                        <?php echo $this->render_upcoming_actions(); ?>
                    </div>

                    <!-- Enlaces Útiles -->
                    <div class="flavor-portal__widget">
                        <h3 class="flavor-portal__widget-title">
                            <?php _e('Enlaces Útiles', 'flavor-chat-ia'); ?>
                        </h3>
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
                    <?php _e('Acceso a Mi Portal', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-login-gate__text">
                    <?php _e('Inicia sesión para acceder a tu panel de control personalizado y gestionar todos tus servicios comunitarios.', 'flavor-chat-ia'); ?>
                </p>
                <div class="flavor-login-gate__actions">
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="flavor-button flavor-button--primary">
                        <?php _e('Iniciar Sesión', 'flavor-chat-ia'); ?>
                    </a>
                    <?php if (get_option('users_can_register')) : ?>
                        <a href="<?php echo wp_registration_url(); ?>" class="flavor-button flavor-button--secondary">
                            <?php _e('Crear Cuenta', 'flavor-chat-ia'); ?>
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
            <a href="<?php echo home_url('/servicios/'); ?>" class="flavor-header-action">
                <span class="flavor-header-action__icon">🔍</span>
                <span class="flavor-header-action__text"><?php _e('Explorar', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo admin_url('profile.php'); ?>" class="flavor-header-action">
                <span class="flavor-header-action__icon">⚙️</span>
                <span class="flavor-header-action__text"><?php _e('Ajustes', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza barra de notificaciones
     */
    private function render_notifications_bar() {
        // TODO: Integrar con sistema real de notificaciones
        $notifications = $this->get_user_notifications();

        if (empty($notifications)) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-notifications-bar">
            <?php foreach (array_slice($notifications, 0, 3) as $notification) : ?>
                <div class="flavor-notification-item flavor-notification-item--<?php echo esc_attr($notification['type']); ?>">
                    <span class="flavor-notification-item__icon"><?php echo $notification['icon']; ?></span>
                    <span class="flavor-notification-item__text"><?php echo esc_html($notification['text']); ?></span>
                    <?php if (!empty($notification['link'])) : ?>
                        <a href="<?php echo esc_url($notification['link']); ?>" class="flavor-notification-item__action">
                            <?php _e('Ver', 'flavor-chat-ia'); ?> →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene notificaciones del usuario
     */
    private function get_user_notifications() {
        // Ejemplo de notificaciones - integrar con sistema real
        return [
            [
                'type' => 'info',
                'icon' => 'ℹ️',
                'text' => __('Bienvenido a tu nuevo portal comunitario', 'flavor-chat-ia'),
                'link' => '',
            ],
        ];
    }

    /**
     * Renderiza accesos rápidos mejorados
     */
    private function render_quick_actions_enhanced() {
        $accesos = $this->get_quick_actions_smart();

        if (empty($accesos)) {
            return '<p class="flavor-no-content">' . __('No hay accesos rápidos disponibles.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-quick-actions-grid">
            <?php foreach ($accesos as $acceso) : ?>
                <a href="<?php echo esc_url($acceso['url']); ?>" class="flavor-quick-action-card">
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
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene accesos rápidos inteligentes basados en módulos activos
     */
    private function get_quick_actions_smart() {
        $accesos = [];
        $loader = class_exists('Flavor_Chat_Module_Loader') ? Flavor_Chat_Module_Loader::get_instance() : null;

        if (!$loader) {
            return $accesos;
        }

        $modulos = $loader->get_loaded_modules();

        // Mapeo de acciones rápidas por módulo
        $quick_actions_map = [
            'eventos' => [
                'icon' => '📅',
                'title' => __('Crear Evento', 'flavor-chat-ia'),
                'description' => __('Organiza un evento para la comunidad', 'flavor-chat-ia'),
                'url' => home_url('/eventos/crear/'),
            ],
            'talleres' => [
                'icon' => '🎓',
                'title' => __('Proponer Taller', 'flavor-chat-ia'),
                'description' => __('Comparte tus conocimientos', 'flavor-chat-ia'),
                'url' => home_url('/talleres/crear/'),
            ],
            'ayuda_vecinal' => [
                'icon' => '🤝',
                'title' => __('Solicitar Ayuda', 'flavor-chat-ia'),
                'description' => __('Pide ayuda a tus vecinos', 'flavor-chat-ia'),
                'url' => home_url('/ayuda-vecinal/solicitar/'),
            ],
            'banco_tiempo' => [
                'icon' => '⏰',
                'title' => __('Ofrecer Servicio', 'flavor-chat-ia'),
                'description' => __('Ofrece tu tiempo a la comunidad', 'flavor-chat-ia'),
                'url' => home_url('/banco-tiempo/ofrecer/'),
            ],
            'grupos_consumo' => [
                'icon' => '🥬',
                'title' => __('Ver Catálogo', 'flavor-chat-ia'),
                'description' => __('Explora productos locales', 'flavor-chat-ia'),
                'url' => home_url('/grupos-consumo/productos/'),
            ],
            'incidencias' => [
                'icon' => '🔧',
                'title' => __('Reportar Incidencia', 'flavor-chat-ia'),
                'description' => __('Comunica un problema', 'flavor-chat-ia'),
                'url' => home_url('/incidencias/crear/'),
            ],
        ];

        foreach ($modulos as $id => $instance) {
            if (isset($quick_actions_map[$id])) {
                // Verificar que la página existe
                $action = $quick_actions_map[$id];
                $page_slug = str_replace(home_url('/'), '', $action['url']);
                $page_slug = trim($page_slug, '/');

                if (get_page_by_path($page_slug)) {
                    $accesos[] = $action;
                }
            }
        }

        return array_slice($accesos, 0, 6); // Máximo 6 accesos rápidos
    }

    /**
     * Renderiza feed de actividad mejorado
     */
    private function render_activity_feed() {
        $activities = $this->get_user_activities();

        if (empty($activities)) {
            return '<div class="flavor-empty-state">
                <span class="flavor-empty-state__icon">📝</span>
                <p>' . __('No hay actividad reciente', 'flavor-chat-ia') . '</p>
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
     * Obtiene actividades del usuario
     */
    private function get_user_activities() {
        // TODO: Integrar con Activity Log real
        return [];
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
            </div>
            <a href="<?php echo admin_url('profile.php'); ?>" class="flavor-profile-widget__link">
                <?php _e('Editar perfil', 'flavor-chat-ia'); ?> →
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza próximas acciones
     */
    private function render_upcoming_actions() {
        // TODO: Implementar lógica real
        ob_start();
        ?>
        <div class="flavor-upcoming-actions">
            <p class="flavor-no-content"><?php _e('No tienes acciones pendientes', 'flavor-chat-ia'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza enlaces útiles
     */
    private function render_useful_links() {
        $links = [
            ['url' => home_url('/servicios/'), 'text' => __('Explorar Servicios', 'flavor-chat-ia'), 'icon' => '🔍'],
            ['url' => admin_url('profile.php'), 'text' => __('Mi Perfil', 'flavor-chat-ia'), 'icon' => '👤'],
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
                <p>' . __('Debes iniciar sesión para ver tus estadísticas.', 'flavor-chat-ia') . '</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="flavor-button flavor-button--primary">' . __('Iniciar Sesión', 'flavor-chat-ia') . '</a>
            </div>';
        }

        $atts = shortcode_atts([
            'columnas' => '4',
            'mostrar_titulo' => 'yes',
        ], $atts);

        // Obtener configuración de diseño
        $design_settings = get_option('flavor_chat_ia_settings', []);

        $modulos = $this->get_modulos_con_stats();

        if (empty($modulos)) {
            return '<p class="flavor-no-stats">' . __('No hay estadísticas disponibles en este momento.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-dashboard-stats">
            <?php if ($atts['mostrar_titulo'] === 'yes') : ?>
                <div class="flavor-dashboard-stats__header">
                    <h2 class="flavor-dashboard-stats__title"><?php _e('Tus Estadísticas', 'flavor-chat-ia'); ?></h2>
                    <p class="flavor-dashboard-stats__subtitle"><?php _e('Resumen de tu actividad en la comunidad', 'flavor-chat-ia'); ?></p>
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
        <div class="flavor-stat-card" data-modulo="<?php echo esc_attr($modulo['id']); ?>">
            <div class="flavor-stat-card__header">
                <div class="flavor-stat-card__icon-wrapper">
                    <span class="flavor-stat-card__icon"><?php echo $modulo['icon']; ?></span>
                </div>
                <span class="flavor-stat-card__badge"><?php echo esc_html($modulo['name']); ?></span>
            </div>

            <div class="flavor-stat-card__body">
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
                    <?php _e('Ver', 'flavor-chat-ia'); ?> <span class="flavor-stat-card__arrow">→</span>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene módulos con sus estadísticas formateadas para tarjetas
     */
    private function get_modulos_con_stats() {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
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
                $modulos_con_stats[] = array_merge([
                    'id' => $id,
                    'name' => $instance->name ?? ucfirst($id),
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
                        'stat_label' => __('Horas Disponibles', 'flavor-chat-ia'),
                        'secondary_stats' => [
                            ['value' => $ofrecidos, 'label' => __('Servicios ofrecidos', 'flavor-chat-ia')],
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
                    'stat_label' => __('Talleres Inscritos', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $organizados, 'label' => __('Talleres organizados', 'flavor-chat-ia')],
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
                    'stat_label' => __('Grupos Activos', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $pedidos, 'label' => __('Pedidos realizados', 'flavor-chat-ia')],
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
                    'stat_label' => __('Solicitudes Activas', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $ayudas, 'label' => __('Ayudas prestadas', 'flavor-chat-ia')],
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
                    'stat_label' => __('Próximos Eventos', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $asistidos, 'label' => __('Eventos asistidos', 'flavor-chat-ia')],
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
                    'stat_label' => __('Libros Prestados', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $historial, 'label' => __('Libros leídos', 'flavor-chat-ia')],
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
                    'stat_label' => __('Reservas Activas', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $totales, 'label' => __('Total reservas', 'flavor-chat-ia')],
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
                    'stat_label' => __('Parcelas Asignadas', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $cosechas, 'label' => __('Cosechas registradas', 'flavor-chat-ia')],
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
                    'stat_label' => __('Incidencias Abiertas', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $resueltas, 'label' => __('Resueltas', 'flavor-chat-ia')],
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
                    'stat_value' => $activo ? __('En uso', 'flavor-chat-ia') : __('Disponible', 'flavor-chat-ia'),
                    'stat_label' => __('Estado Actual', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $total_uso, 'label' => __('Usos totales', 'flavor-chat-ia')],
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
                    'stat_label' => __('Viajes Próximos', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $completados, 'label' => __('Viajes completados', 'flavor-chat-ia')],
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
                    'stat_label' => __('Temas Creados', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $respuestas, 'label' => __('Respuestas', 'flavor-chat-ia')],
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
                    'stat_label' => __('Anuncios Activos', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $ventas, 'label' => __('Vendidos', 'flavor-chat-ia')],
                    ],
                ];

            case 'reciclaje':
                $tabla = $wpdb->prefix . 'flavor_reciclaje_puntos';
                $puntos = $wpdb->get_var($wpdb->prepare(
                    "SELECT puntos_acumulados FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));

                $registros = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_reciclaje_registros WHERE usuario_id = %d",
                    $user_id
                ));

                return [
                    'stat_value' => $puntos ?? 0,
                    'stat_label' => __('Puntos Verdes', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $registros, 'label' => __('Registros', 'flavor-chat-ia')],
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
                    'stat_label' => __('Cursos Activos', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $completados, 'label' => __('Completados', 'flavor-chat-ia')],
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
                    'stat_label' => __('Comunidades Activas', 'flavor-chat-ia'),
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
                        'stat_label' => __('Número de Socio', 'flavor-chat-ia'),
                        'secondary_stats' => [
                            ['value' => '✅', 'label' => __('Activo', 'flavor-chat-ia')],
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
                    'stat_value' => $activa ? __('Reservada', 'flavor-chat-ia') : __('Sin reserva', 'flavor-chat-ia'),
                    'stat_label' => __('Plaza de Parking', 'flavor-chat-ia'),
                    'secondary_stats' => [
                        ['value' => $total, 'label' => __('Reservas totales', 'flavor-chat-ia')],
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
            return '<p class="flavor-no-modulos">' . __('No hay módulos disponibles en este momento.', 'flavor-chat-ia') . '</p>';
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
                                <?php echo esc_html($modulo['action_text'] ?? __('Acceder', 'flavor-chat-ia')); ?>
                            </a>
                        <?php else : ?>
                            <span class="flavor-modulo-card__locked">
                                🔒 <?php _e('Requiere registro', 'flavor-chat-ia'); ?>
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
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
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
                    $stats[] = sprintf(__('%s horas disponibles', 'flavor-chat-ia'), number_format($saldo, 1));
                }
                break;

            case 'talleres':
                $tabla = $wpdb->prefix . 'flavor_talleres_inscripciones';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'confirmada'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d talleres inscritos', 'flavor-chat-ia'), $count);
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
                    $stats[] = sprintf(__('Miembro de %d grupo(s)', 'flavor-chat-ia'), $count);
                }
                break;

            case 'ayuda_vecinal':
                $tabla = $wpdb->prefix . 'flavor_ayuda_solicitudes';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE solicitante_id = %d AND estado = 'abierta'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d solicitudes activas', 'flavor-chat-ia'), $count);
                }
                break;

            case 'eventos':
                $tabla = $wpdb->prefix . 'flavor_eventos_asistentes';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'confirmado'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d eventos próximos', 'flavor-chat-ia'), $count);
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
                        'activo' => '✅ ' . __('Socio Activo', 'flavor-chat-ia'),
                        'pendiente' => '⏳ ' . __('Pendiente de aprobación', 'flavor-chat-ia'),
                        'baja' => '❌ ' . __('De baja', 'flavor-chat-ia'),
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
                    $stats[] = sprintf(__('%d libros prestados', 'flavor-chat-ia'), $count);
                }
                break;

            case 'espacios_comunes':
                $tabla = $wpdb->prefix . 'flavor_espacios_reservas';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha >= CURDATE() AND estado = 'confirmada'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d reservas activas', 'flavor-chat-ia'), $count);
                }
                break;

            case 'huertos_urbanos':
                $tabla = $wpdb->prefix . 'flavor_huertos_parcelas';
                $parcela = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'asignada'",
                    $user_id
                ));
                if ($parcela > 0) {
                    $stats[] = '🌱 ' . sprintf(__('%d parcela(s) asignadas', 'flavor-chat-ia'), $parcela);
                }
                break;

            case 'incidencias':
                $tabla = $wpdb->prefix . 'flavor_incidencias';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado IN ('pendiente', 'en_progreso')",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d incidencias abiertas', 'flavor-chat-ia'), $count);
                }
                break;

            case 'bicicletas_compartidas':
                $tabla = $wpdb->prefix . 'flavor_bicicletas_prestamos';
                $activo = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha_devolucion IS NULL",
                    $user_id
                ));
                if ($activo > 0) {
                    $stats[] = '🚲 ' . __('Bicicleta en uso', 'flavor-chat-ia');
                }
                break;

            case 'carpooling':
                $tabla = $wpdb->prefix . 'flavor_carpooling_reservas';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'confirmada' AND fecha >= CURDATE()",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d viajes reservados', 'flavor-chat-ia'), $count);
                }
                break;

            case 'foros':
                $tabla = $wpdb->prefix . 'flavor_foros_temas';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE autor_id = %d",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d temas creados', 'flavor-chat-ia'), $count);
                }
                break;

            case 'marketplace':
                $tabla = $wpdb->prefix . 'flavor_marketplace_anuncios';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE vendedor_id = %d AND estado = 'publicado'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d anuncios activos', 'flavor-chat-ia'), $count);
                }
                break;

            case 'reciclaje':
                $tabla = $wpdb->prefix . 'flavor_reciclaje_puntos';
                $puntos = $wpdb->get_var($wpdb->prepare(
                    "SELECT puntos_acumulados FROM $tabla WHERE usuario_id = %d",
                    $user_id
                ));
                if ($puntos > 0) {
                    $stats[] = '♻️ ' . sprintf(__('%d puntos verdes', 'flavor-chat-ia'), $puntos);
                }
                break;

            case 'cursos':
                $tabla = $wpdb->prefix . 'flavor_cursos_matriculas';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE alumno_id = %d AND estado = 'activo'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('%d cursos activos', 'flavor-chat-ia'), $count);
                }
                break;

            case 'comunidades':
                $tabla = $wpdb->prefix . 'flavor_comunidades_miembros';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'activo'",
                    $user_id
                ));
                if ($count > 0) {
                    $stats[] = sprintf(__('Miembro de %d comunidad(es)', 'flavor-chat-ia'), $count);
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
                    $stats[] = sprintf(__('%d suscripciones', 'flavor-chat-ia'), $count);
                }
                break;

            case 'parkings':
                $tabla = $wpdb->prefix . 'flavor_parkings_reservas';
                $reserva = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND fecha_inicio <= NOW() AND fecha_fin >= NOW()",
                    $user_id
                ));
                if ($reserva > 0) {
                    $stats[] = '🅿️ ' . __('Plaza reservada', 'flavor-chat-ia');
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
            'talleres' => __('Ver Talleres', 'flavor-chat-ia'),
            'ayuda_vecinal' => __('Pedir/Ofrecer Ayuda', 'flavor-chat-ia'),
            'eventos' => __('Ver Eventos', 'flavor-chat-ia'),
            'grupos_consumo' => __('Ver Grupos', 'flavor-chat-ia'),
            'banco_tiempo' => __('Ver Servicios', 'flavor-chat-ia'),
        ];

        return $texts[$modulo_id] ?? __('Acceder', 'flavor-chat-ia');
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
                <span class="flavor-stat-item__label"><?php _e('Servicios Activos', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-stat-item">
                <span class="flavor-stat-item__value"><?php echo esc_html($stats['usuarios_activos']); ?></span>
                <span class="flavor-stat-item__label"><?php _e('Usuarios Activos', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-stat-item">
                <span class="flavor-stat-item__value"><?php echo esc_html($stats['actividad_reciente']); ?></span>
                <span class="flavor-stat-item__label"><?php _e('Actividades este mes', 'flavor-chat-ia'); ?></span>
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
                    <p><?php _e('Bienvenido a tu portal. Explora los servicios disponibles.', 'flavor-chat-ia'); ?></p>
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
        $loader = class_exists('Flavor_Chat_Module_Loader') ? Flavor_Chat_Module_Loader::get_instance() : null;
        if (!$loader) {
            return $accesos;
        }

        $modulos = $loader->get_loaded_modules();

        if (isset($modulos['talleres'])) {
            $accesos[] = [
                'icon' => '➕',
                'text' => __('Crear Taller', 'flavor-chat-ia'),
                'url' => home_url('/talleres/crear/'),
            ];
        }

        if (isset($modulos['ayuda_vecinal'])) {
            $accesos[] = [
                'icon' => '🆘',
                'text' => __('Solicitar Ayuda', 'flavor-chat-ia'),
                'url' => home_url('/ayuda-vecinal/solicitar/'),
            ];
        }

        if (isset($modulos['eventos'])) {
            $accesos[] = [
                'icon' => '📅',
                'text' => __('Crear Evento', 'flavor-chat-ia'),
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
            <p class="flavor-actividad-reciente__empty"><?php _e('No hay actividad reciente', 'flavor-chat-ia'); ?></p>
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
                <span class="flavor-stat-personal__label"><?php _e('Participaciones', 'flavor-chat-ia'); ?></span>
                <span class="flavor-stat-personal__value">0</span>
            </div>
            <div class="flavor-stat-personal">
                <span class="flavor-stat-personal__label"><?php _e('Contribuciones', 'flavor-chat-ia'); ?></span>
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
}
