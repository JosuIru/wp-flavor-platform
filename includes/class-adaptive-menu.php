<?php
/**
 * Adaptive Menu System
 *
 * Menú que se adapta según el estado de login del usuario
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

class Flavor_Adaptive_Menu {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la URL actual para redirects de login en menús adaptativos.
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
        add_shortcode('flavor_adaptive_menu', [$this, 'render_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);

        // Hook para modificar menús de WordPress existentes
        add_filter('wp_nav_menu_items', [$this, 'modify_nav_menu'], 10, 2);
    }

    /**
     * Encola estilos
     */
    public function enqueue_styles() {
        wp_add_inline_style('flavor-portal', $this->get_menu_css());
    }

    /**
     * Renderiza el menú adaptativo
     */
    public function render_menu($atts) {
        $atts = shortcode_atts([
            'theme' => 'default', // default, minimal, transparent
            'position' => 'header', // header, sidebar
        ], $atts);

        ob_start();
        ?>
        <nav class="flavor-adaptive-menu flavor-adaptive-menu--<?php echo esc_attr($atts['theme']); ?> flavor-adaptive-menu--<?php echo esc_attr($atts['position']); ?>">
            <div class="flavor-adaptive-menu__container">
                <!-- Logo/Brand -->
                <div class="flavor-adaptive-menu__brand">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="flavor-adaptive-menu__logo">
                        <?php bloginfo('name'); ?>
                    </a>
                </div>

                <!-- Main Navigation -->
                <div class="flavor-adaptive-menu__nav">
                    <?php echo $this->get_main_nav_items(); ?>
                </div>

                <!-- User Section -->
                <div class="flavor-adaptive-menu__user">
                    <?php echo $this->get_user_section(); ?>
                </div>

                <!-- Mobile Toggle -->
                <button class="flavor-adaptive-menu__toggle" aria-label="<?php esc_attr_e('Toggle menu', 'flavor-chat-ia'); ?>">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene items de navegación principal
     */
    private function get_main_nav_items() {
        ob_start();
        ?>
        <ul class="flavor-adaptive-menu__items">
            <?php if (is_user_logged_in()) : ?>
                <!-- Usuario Logueado -->
                <li class="flavor-adaptive-menu__item">
                    <a href="<?php echo esc_url(home_url('/mi-portal/')); ?>" class="flavor-adaptive-menu__link">
                        <span class="flavor-adaptive-menu__icon">🏠</span>
                        <?php _e('Mi Portal', 'flavor-chat-ia'); ?>
                    </a>
                </li>
                <li class="flavor-adaptive-menu__item">
                    <a href="<?php echo esc_url(home_url('/servicios/')); ?>" class="flavor-adaptive-menu__link">
                        <span class="flavor-adaptive-menu__icon">🎯</span>
                        <?php _e('Servicios', 'flavor-chat-ia'); ?>
                    </a>
                </li>
                <?php
                // Notificaciones (si hay sistema activo)
                $unread_count = $this->get_unread_notifications_count();
                if ($unread_count > 0) :
                ?>
                <li class="flavor-adaptive-menu__item">
                    <a href="<?php echo esc_url(home_url('/mi-portal/#notificaciones')); ?>" class="flavor-adaptive-menu__link flavor-adaptive-menu__link--notifications">
                        <span class="flavor-adaptive-menu__icon">🔔</span>
                        <span class="flavor-adaptive-menu__badge"><?php echo esc_html($unread_count); ?></span>
                    </a>
                </li>
                <?php endif; ?>
            <?php else : ?>
                <!-- Usuario NO Logueado -->
                <li class="flavor-adaptive-menu__item">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="flavor-adaptive-menu__link">
                        <span class="flavor-adaptive-menu__icon">🏠</span>
                        <?php _e('Inicio', 'flavor-chat-ia'); ?>
                    </a>
                </li>
                <li class="flavor-adaptive-menu__item">
                    <a href="<?php echo esc_url(home_url('/servicios/')); ?>" class="flavor-adaptive-menu__link">
                        <span class="flavor-adaptive-menu__icon">🎯</span>
                        <?php _e('Servicios', 'flavor-chat-ia'); ?>
                    </a>
                </li>
                <li class="flavor-adaptive-menu__item">
                    <a href="<?php echo esc_url(home_url('/sobre-nosotros/')); ?>" class="flavor-adaptive-menu__link">
                        <span class="flavor-adaptive-menu__icon">ℹ️</span>
                        <?php _e('Sobre Nosotros', 'flavor-chat-ia'); ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene sección de usuario
     */
    private function get_user_section() {
        ob_start();

        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $avatar_url = get_avatar_url($current_user->ID, ['size' => 80]);
            ?>
            <div class="flavor-user-dropdown">
                <button class="flavor-user-dropdown__trigger" aria-haspopup="true" aria-expanded="false">
                    <img src="<?php echo esc_url($avatar_url); ?>"
                         alt="<?php echo esc_attr($current_user->display_name); ?>"
                         class="flavor-user-dropdown__avatar">
                    <span class="flavor-user-dropdown__name"><?php echo esc_html($current_user->display_name); ?></span>
                    <span class="flavor-user-dropdown__arrow">▼</span>
                </button>

                <div class="flavor-user-dropdown__menu">
                    <div class="flavor-user-dropdown__header">
                        <img src="<?php echo esc_url($avatar_url); ?>"
                             alt="<?php echo esc_attr($current_user->display_name); ?>"
                             class="flavor-user-dropdown__avatar-large">
                        <div class="flavor-user-dropdown__info">
                            <strong class="flavor-user-dropdown__displayname"><?php echo esc_html($current_user->display_name); ?></strong>
                            <span class="flavor-user-dropdown__email"><?php echo esc_html($current_user->user_email); ?></span>
                        </div>
                    </div>

                    <ul class="flavor-user-dropdown__items">
                        <li>
                            <a href="<?php echo esc_url(home_url('/mi-portal/')); ?>" class="flavor-user-dropdown__link">
                                <span class="flavor-user-dropdown__icon">🏠</span>
                                <?php _e('Mi Portal', 'flavor-chat-ia'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/mi-perfil/')); ?>" class="flavor-user-dropdown__link">
                                <span class="flavor-user-dropdown__icon">👤</span>
                                <?php _e('Mi Perfil', 'flavor-chat-ia'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/configuracion/')); ?>" class="flavor-user-dropdown__link">
                                <span class="flavor-user-dropdown__icon">⚙️</span>
                                <?php _e('Configuración', 'flavor-chat-ia'); ?>
                            </a>
                        </li>
                        <?php if (current_user_can('manage_options')) : ?>
                        <li>
                            <a href="<?php echo esc_url(admin_url()); ?>" class="flavor-user-dropdown__link">
                                <span class="flavor-user-dropdown__icon">🔧</span>
                                <?php _e('Panel Admin', 'flavor-chat-ia'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="flavor-user-dropdown__separator"></li>
                        <li>
                            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="flavor-user-dropdown__link flavor-user-dropdown__link--logout">
                                <span class="flavor-user-dropdown__icon">🚪</span>
                                <?php _e('Cerrar Sesión', 'flavor-chat-ia'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="flavor-auth-buttons">
                <a href="<?php echo esc_url(wp_login_url($this->get_current_request_url())); ?>" class="flavor-button flavor-button--secondary flavor-button--small">
                    <?php _e('Acceder', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="flavor-button flavor-button--primary flavor-button--small">
                    <?php _e('Registrarse', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    /**
     * Obtiene contador de notificaciones no leídas
     */
    private function get_unread_notifications_count() {
        // Por ahora retorna 0, se implementará con el sistema de notificaciones
        return apply_filters('flavor_unread_notifications_count', 0);
    }

    /**
     * Modifica menús de WordPress existentes
     */
    public function modify_nav_menu($items, $args) {
        // Solo modificar si es el menú principal
        if ($args->theme_location !== 'primary') {
            return $items;
        }

        // Añadir clase a items según estado de login
        if (is_user_logged_in()) {
            $items = str_replace('menu-item', 'menu-item menu-item--logged-in', $items);
        }

        return $items;
    }

    /**
     * CSS para el menú adaptativo
     */
    private function get_menu_css() {
        return '
        /* Adaptive Menu */
        .flavor-adaptive-menu {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .flavor-adaptive-menu__container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }
        .flavor-adaptive-menu__brand {
            font-size: 24px;
            font-weight: 700;
            color: var(--flavor-primary, #3b82f6);
        }
        .flavor-adaptive-menu__logo {
            text-decoration: none;
            color: inherit;
        }
        .flavor-adaptive-menu__nav {
            flex: 1;
            display: flex;
            justify-content: center;
        }
        .flavor-adaptive-menu__items {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 8px;
        }
        .flavor-adaptive-menu__link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
            position: relative;
        }
        .flavor-adaptive-menu__link:hover {
            background: #f3f4f6;
            color: var(--flavor-primary, #3b82f6);
        }
        .flavor-adaptive-menu__icon {
            font-size: 18px;
        }
        .flavor-adaptive-menu__badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }
        .flavor-adaptive-menu__toggle {
            display: none;
        }

        /* User Dropdown */
        .flavor-user-dropdown {
            position: relative;
        }
        .flavor-user-dropdown__trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 10px;
            transition: background 0.2s;
        }
        .flavor-user-dropdown__trigger:hover {
            background: #f3f4f6;
        }
        .flavor-user-dropdown__avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .flavor-user-dropdown__name {
            font-weight: 500;
            color: #374151;
        }
        .flavor-user-dropdown__arrow {
            font-size: 12px;
            color: #9ca3af;
            transition: transform 0.2s;
        }
        .flavor-user-dropdown__trigger[aria-expanded="true"] .flavor-user-dropdown__arrow {
            transform: rotate(180deg);
        }
        .flavor-user-dropdown__menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 280px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s;
        }
        .flavor-user-dropdown__trigger[aria-expanded="true"] + .flavor-user-dropdown__menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .flavor-user-dropdown__header {
            display: flex;
            gap: 12px;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .flavor-user-dropdown__avatar-large {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .flavor-user-dropdown__info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .flavor-user-dropdown__displayname {
            font-size: 16px;
            color: #111827;
        }
        .flavor-user-dropdown__email {
            font-size: 13px;
            color: #6b7280;
        }
        .flavor-user-dropdown__items {
            list-style: none;
            margin: 0;
            padding: 10px;
        }
        .flavor-user-dropdown__link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .flavor-user-dropdown__link:hover {
            background: #f3f4f6;
            color: var(--flavor-primary, #3b82f6);
        }
        .flavor-user-dropdown__link--logout {
            color: #ef4444;
        }
        .flavor-user-dropdown__link--logout:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        .flavor-user-dropdown__separator {
            height: 1px;
            background: #e5e7eb;
            margin: 10px 0;
        }

        /* Auth Buttons */
        .flavor-auth-buttons {
            display: flex;
            gap: 12px;
        }
        .flavor-button--small {
            padding: 8px 16px;
            font-size: 14px;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .flavor-adaptive-menu__nav {
                display: none;
            }
            .flavor-adaptive-menu__toggle {
                display: flex;
                flex-direction: column;
                gap: 4px;
                background: transparent;
                border: none;
                cursor: pointer;
                padding: 8px;
            }
            .flavor-adaptive-menu__toggle span {
                display: block;
                width: 24px;
                height: 3px;
                background: #374151;
                border-radius: 2px;
                transition: all 0.2s;
            }
            .flavor-user-dropdown__name {
                display: none;
            }
        }
        ';
    }
}

// Inicializar
Flavor_Adaptive_Menu::get_instance();

// JavaScript para el dropdown (inline en footer)
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.querySelector('.flavor-user-dropdown__trigger');
        if (trigger) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                const expanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !expanded);
            });

            document.addEventListener('click', function() {
                trigger.setAttribute('aria-expanded', 'false');
            });

            const menu = document.querySelector('.flavor-user-dropdown__menu');
            if (menu) {
                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        }
    });
    </script>
    <?php
}, 100);
